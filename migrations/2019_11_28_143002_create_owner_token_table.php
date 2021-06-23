<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOwnerTokenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('owner_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->string('owner_type');
            $table->unsignedInteger('owner_id');
            $table->string("token");
            $table->dateTime('limited_at')->nullable();
            $table->timestamps();

            $table->index(['owner_type','owner_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('owner_tokens');
    }
}
