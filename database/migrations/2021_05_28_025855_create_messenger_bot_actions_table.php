<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RTippin\Messenger\Support\Helpers;

class CreateMessengerBotActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messenger_bot_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bot_id');
            Helpers::SchemaMorphType('owner', $table);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('bot_id')
                ->references('id')
                ->on('messenger_bots')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messenger_bot_actions');
    }
}
