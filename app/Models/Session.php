<?php

namespace App\Models;

use App\Services\UserSessionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cookie;


class Session extends Model
{
    protected $table = 'sessions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'token_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity'
    ];

    public function token()
    {
        return $this->belongsTo(PersonalAccessToken::class, 'token_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCurrentSession(): bool
    {
        $service = app(UserSessionService::class);
        $result = $service->getSessionFromCookie();

        if (!$result['result'] || !$result['session']) {
            return false;
        }

        return $this->id === $result['session']->id;
    }
}
