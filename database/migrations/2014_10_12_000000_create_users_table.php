<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('surname');
            $table->string('second_name');
            $table->string('oms');
            $table->string('phone_number');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('doctors', function (Blueprint $table){
            $table->id();
            $table->foreignId('user')->constrained('users');
            $table->string('speciality');
        });
        Schema::create('calendar', function (Blueprint $table){
            $table->id();
            $table->foreignId('user')->constrained('users');
            $table->dateTime('dateTime');
            $table->boolean('free');
        });
        Schema::create('notes', function (Blueprint $table){
            $table->id();
            $table->foreignId('client')->constrained('users');
            $table->foreignId('calendar')->constrained('calendar');
        });
        Schema::create('roles', function (Blueprint $table){
            $table->id();
            $table->foreignId('user')->constrained('users');
            $table->string('role');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('calendar');
        Schema::dropIfExists('notes');
    }
}
