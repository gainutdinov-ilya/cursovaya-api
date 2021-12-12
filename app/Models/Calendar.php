<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    use HasFactory;

    protected $table = 'calendar';

    protected $fillable = [
        'doctor'
    ];

    protected $casts = [
        'dateTime' => 'datetime',
        'free' => 'bool'
    ];

    function doctor(){
        $this->belongsTo(User::class,'doctor')->first();
    }

    public $timestamps = false;
}
