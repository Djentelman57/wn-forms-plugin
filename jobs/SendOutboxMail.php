<?php

namespace Martin\Forms\Jobs;

use Throwable;
use Martin\Forms\Models\Record;
use Martin\Forms\Models\MailOutbox;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Winter\Storm\Database\Collection;
use Illuminate\Queue\InteractsWithQueue;
use Martin\Forms\Classes\Mails\Notification;
use Martin\Forms\Classes\Mails\AutoResponse;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendOutboxMail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 5;

    public $backoff = [60, 300, 900, 3600];

    /** @var int */
    protected $outboxId;

    public function __construct(int $outboxId)
    {
        $this->outboxId = $outboxId;
    }

    public function handle()
    {
        $outbox = MailOutbox::find($this->outboxId);

        if (!$outbox || $outbox->status === MailOutbox::STATUS_SENT) {
            return;
        }

        $payload = json_decode($outbox->payload, true) ?: [];
        $outbox->status = MailOutbox::STATUS_PROCESSING;
        $outbox->attempts = $this->attempts();
        $outbox->save();

        $record = $this->resolveRecord($outbox, $payload);
        $post = $payload['post'] ?? [];
        $properties = $payload['properties'] ?? [];

        if ($outbox->mail_type === 'notification') {
            $notification = new Notification($properties, $post, $record, $this->resolveFiles($record));
            $notification->send();
        } elseif ($outbox->mail_type === 'autoresponse') {
            $autoresponse = new AutoResponse($properties, $post, $record);
            $autoresponse->send();
        }

        $outbox->status = MailOutbox::STATUS_SENT;
        $outbox->last_error = null;
        $outbox->queued_at = null;
        $outbox->sent_at = now();
        $outbox->save();
    }

    public function failed(Throwable $exception)
    {
        $outbox = MailOutbox::find($this->outboxId);

        if (!$outbox) {
            return;
        }

        $outbox->status = MailOutbox::STATUS_FAILED;
        $outbox->attempts = $this->attempts();
        $outbox->queued_at = null;
        $outbox->last_error = substr($exception->getMessage(), 0, 65535);
        $outbox->save();
    }

    protected function resolveRecord(MailOutbox $outbox, array $payload): Record
    {
        if (!empty($outbox->record_id)) {
            $record = Record::with('files')->find($outbox->record_id);
            if ($record) {
                return $record;
            }
        }

        $record = new Record;
        $record->id = $payload['record']['id'] ?? null;
        $record->ip = $payload['record']['ip'] ?? null;
        $record->created_at = $payload['record']['created_at'] ?? now();

        return $record;
    }

    protected function resolveFiles(Record $record): Collection
    {
        if ($record->exists) {
            return $record->files;
        }

        return new Collection();
    }
}
