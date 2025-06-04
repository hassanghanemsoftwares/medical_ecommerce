<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterEmail extends Model
{
    protected $fillable = [
        'email',
        'is_active',
        'subscribed_at',
    ];
}
