<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'surname',
        'second_name',
        'oms',
        'phone_number',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function setPassword($password){
        $this->password = $password;
    }

    public function role(){
        return $this->hasOne(Roles::class,'user')->first();
    }

    public function doctor(){
        return $this->hasOne( Doctors::class, 'user')->first();
    }

    public function notes(){
        return $this->hasMany( Notes::class, 'client')->get();
    }

    public function isAdmin(){
        return $this->role()->role == 'admin';
    }

    public function isDoctor(){
        return $this->role()->role == 'doctor';
    }

    public function isClient(){
        return $this->role()->role == 'client';
    }

    public function isPersonal(){
        return $this->role()->role == 'perosnal';
    }
}
