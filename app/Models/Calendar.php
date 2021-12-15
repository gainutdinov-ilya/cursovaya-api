<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    use HasFactory;

    protected $table = 'calendar';

    protected $fillable = [
        'doctor',
        'dateTime',
        'free'
    ];

    protected $casts = [
        'dateTime' => 'datetime',
        'free' => 'bool'
    ];

    function doctor(){
        return $this->belongsTo(User::class,'doctor')->get()->first();
    }

    public $timestamps = false;
}
