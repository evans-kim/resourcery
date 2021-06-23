<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string("title");
            $table->smallInteger("level")->default(1);
            $table->timestamps();
        });

        Schema::create('players', function (Blueprint $table) {
            $table->unsignedInteger('role_id');
            $table->string('player_type');
            $table->unsignedInteger('player_id');

            $table->foreign('role_id')
                ->references('id')->on('roles')
                ->onDelete('cascade');

            $table->index(['player_type','player_id']);
        });

        DB::table('roles')->insert(['title'=>'ADMIN','level'=>127]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('players');
        Schema::dropIfExists('roles');
    }
}
