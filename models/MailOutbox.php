<?php

namespace Martin\Forms\Models;

use Winter\Storm\Database\Model;

class MailOutbox extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    public $table = 'martin_forms_mail_outbox';

    protected $guarded = [];

    protected $dates = [
        'queued_at',
        'sent_at',
        'created_at',
        'updated_at',
    ];

    public $belongsTo = [
        'record' => [Record::class],
    ];
}
