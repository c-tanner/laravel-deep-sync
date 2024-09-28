<?php

namespace CTanner\LaravelDeepSync\Models;

use CTanner\LaravelDeepSync\Attributes\SyncFrom;
use CTanner\LaravelDeepSync\Observers\DeepSync;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    #[SyncFrom]
    public function posts(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
