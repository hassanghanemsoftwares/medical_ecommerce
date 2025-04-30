<?php

namespace App\Models;

use App\Mail\CustomPasswordResetMail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Jenssegers\Agent\Agent;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Sanctum\PersonalAccessToken;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public static function booted()
    {
        static::deleting(function ($user) {
            $user->tokens()->delete();
        });
    }

    public function tokens(): MorphMany
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    }
    /**
     * Configure Spatie activity logging.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('user');
    }

    /**
     * Customize the log message.
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }


    /**
     * Example: Manual logging for login event
     */
    public function logLogin()
    {
        activity('user')
            ->causedBy($this)
            ->performedOn($this)
            ->withProperties($this->parseUserAgent())
            ->log('User logged in');
    }

    /**
     * Teams relationship
     */
    public function teams()
    {
        $teamIds = DB::table('model_has_roles')
            ->where('model_id', $this->id)
            ->where('model_type', self::class)
            ->pluck('team_id')
            ->unique();

        return Team::whereIn('id', $teamIds)->get();
    }

    public function hasAccessToTeam($teamId): bool
    {
        return DB::table('model_has_roles')
            ->where('model_id', $this->id)
            ->where('model_type', self::class)
            ->where('team_id', $teamId)
            ->exists();
    }

    public function sendPasswordResetNotification($token)
    {
        Mail::to($this->email)->send(new CustomPasswordResetMail($token, $this->email));
    }

    public function parseUserAgent()
    {
        $agent = new Agent();
        return [
            'device' => $agent->device(),
            'platform' => $agent->platform(),
            'browser' => $agent->browser(),
        ];
    }
}
