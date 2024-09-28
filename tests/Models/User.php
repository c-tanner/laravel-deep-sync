<?php

namespace CTanner\LaravelDeepSync\Tests\Models;

use CTanner\LaravelDeepSync\tests\Models\Post;
use CTanner\LaravelDeepSync\Attributes\SyncTo;
use CTanner\LaravelDeepSync\Observers\DeepSync;
use CTanner\LaravelDeepSync\Tests\Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
