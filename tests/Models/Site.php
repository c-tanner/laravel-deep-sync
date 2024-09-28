<?php

namespace CTanner\LaravelDeepSync\Tests\Models;

use CTanner\LaravelDeepSync\Tests\Models\User;
use CTanner\LaravelDeepSync\Attributes\SyncTo;
use CTanner\LaravelDeepSync\Observers\DeepSync;
use CTanner\LaravelDeepSync\Tests\Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([DeepSync::class])]
class Site extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'is_active'
    ];

    /**
     * Properties that trigger DeepSync
     */
    public $syncable = ['is_active'];

    #[SyncTo]
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'site_users');
    }

    protected static function newFactory()
    {
        return SiteFactory::new();
    }
}
