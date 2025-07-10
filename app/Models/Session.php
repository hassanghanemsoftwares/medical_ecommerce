<?php

namespace App\Models;

use App\Services\UserSessionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class Session extends Model
{
    protected $table = 'sessions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'device_id',
        'user_id',
        'token_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
        'is_active',
    ];

    public function token()
    {
        return $this->belongsTo(PersonalAccessToken::class, 'token_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this session corresponds to the current device/session
     *
     * @param Request $request
     * @return bool
     */
    public function isCurrentSession(Request $request): bool
    {
        $service = app(UserSessionService::class);

        $result = $service->getSessionFromDevice($request);

        if (!$result['result'] || !$result['session']) {
            return false;
        }

        return $this->id === $result['session']->id;
    }
}
