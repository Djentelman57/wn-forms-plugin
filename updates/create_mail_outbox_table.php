<?php

namespace Martin\Forms\Updates;

use Winter\Storm\Support\Facades\Schema;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;

class CreateMailOutboxTable extends Migration
{
    public function up()
    {
        Schema::create('martin_forms_mail_outbox', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('record_id')->unsigned()->nullable()->index();
            $table->string('mail_type', 32);
            $table->string('status', 20)->default('pending')->index();
            $table->integer('attempts')->unsigned()->default(0);
            $table->timestamp('queued_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->longText('payload');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('martin_forms_mail_outbox');
    }
}
