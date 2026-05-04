<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessages extends Model
{
    protected $fillable = [
        'full_name', 
        'email', 
        'phone_number', 
        'attached_file', 
        'category', 
        'subject', 
        'message',
        'is_read'
    ];
}
