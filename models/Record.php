<?php

namespace Martin\Forms\Models;

use Backend\Facades\Backend;
use Winter\Storm\Database\Model;
use Martin\Forms\Models\MailOutbox;

class Record extends Model
{
    use \Winter\Storm\Database\Traits\SoftDelete;

    public $table = 'martin_forms_records';

    protected $dates = ['deleted_at'];

    public $attachMany = [
        'files' => ['System\Models\File', 'public' => false]
    ];

    public $hasMany = [
        'mail_outbox_items' => [MailOutbox::class, 'key' => 'record_id']
    ];

    public function getFormDataArrAttribute()
    {
        return (array) json_decode($this->form_data);
    }

    public function filterGroups()
    {
        return Record::orderBy('group')->groupBy('group')->lists('group', 'group');
    }

    public function getGroupsOptions()
    {
        return $this->filterGroups();
    }

    public function filesList()
    {
        return $this->files->map(function ($file) {
            return Backend::url('martin/forms/records/download', [$this->id, $file->id]);
        })->implode(',');
    }

    public function getMailDeliveryLabelAttribute()
    {
        $total = (int) ($this->mail_outbox_total ?? 0);
        $sent = (int) ($this->mail_outbox_sent ?? 0);
        $failed = (int) ($this->mail_outbox_failed ?? 0);
        $processing = (int) ($this->mail_outbox_processing ?? 0);
        $queued = (int) ($this->mail_outbox_queued ?? 0);
        $pending = (int) ($this->mail_outbox_pending ?? 0);

        if ($total === 0) {
            return 'Не отправлялось';
        }

        if ($sent === $total) {
            return "Доставлено {$sent}/{$total}";
        }

        if ($failed > 0) {
            return "Ошибки {$failed}/{$total}";
        }

        if ($processing > 0 || $queued > 0) {
            return "В очереди " . ($processing + $queued) . "/{$total}";
        }

        if ($pending > 0) {
            return "Ожидает {$pending}/{$total}";
        }

        return "Частично {$sent}/{$total}";
    }
}
