<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctors extends Model
{
    use HasFactory;

    protected $table = 'doctors';

    protected $fillable = [
        'user',
        'speciality'
    ];

    function user(){
        return $this->belongsTo(User::class,'user')->first();
    }

    public $timestamps = false;
}
