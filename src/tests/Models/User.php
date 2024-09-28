<?php

namespace CTanner\LaravelDeepSync\Tests\Models;

use CTanner\DeepSync\tests\Models\Post;
use CTanner\LaravelDeepSync\Attributes\SyncTo;
use CTanner\LaravelDeepSync\Observers\DeepSync;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([new DeepSync(User::class)])]
class User extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'is_active'
    ];

    /**
     * DeepSync properties and trigger values
     */
    protected $syncable = [
        'is_active' => 0
    ];

    #[SyncTo]
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
