<?php

use App\Models\Roles;
use App\Models\User;
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
            $table->foreignId('user')->constrained('users')->onDelete('cascade');
            $table->string('speciality');
        });
        Schema::create('calendar', function (Blueprint $table){
            $table->id();
            $table->foreignId('doctor')->constrained('users')->onDelete('cascade');
            $table->dateTime('dateTime');
            $table->boolean('free');
        });
        Schema::create('notes', function (Blueprint $table){
            $table->id();
            $table->foreignId('client')->constrained('users')->onDelete('cascade');
            $table->foreignId('calendar')->constrained('calendar')->onDelete('cascade');
        });
        Schema::create('roles', function (Blueprint $table){
            $table->id();
            $table->foreignId('user')->constrained('users')->onDelete('cascade');
            $table->string('role');
        });
        $user = User::create([
            'name' => "Администратор",
            'email' => "admin@admin.com",
            'phone_number' => "0",
            'surname' => "Администратор",
            'second_name' => "Администратор",
            'oms' => "0000000000",
            'password' => bcrypt("1234567"),
        ]);

        Roles::create([
            'user' => $user->id,
            'role' => 'admin'
        ]);
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
