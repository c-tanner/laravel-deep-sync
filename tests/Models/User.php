<?php

namespace CTanner\LaravelDeepSync\Tests\Models;

use CTanner\LaravelDeepSync\Attributes\SyncFrom;
use CTanner\LaravelDeepSync\Tests\Models\Post;
use CTanner\LaravelDeepSync\Attributes\SyncTo;
use CTanner\LaravelDeepSync\Observers\DeepSync;
use CTanner\LaravelDeepSync\Tests\Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([DeepSync::class])]
class User extends Model
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

    #[SyncFrom]
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class, 'site_users');
    }

    #[SyncTo]
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
