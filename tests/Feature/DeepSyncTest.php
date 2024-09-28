<?php

namespace CTanner\LaravelDeepSync\Tests\Feature;

use CTanner\LaravelDeepSync\Tests\Models\User;
use CTanner\LaravelDeepSync\Tests\Models\Post;
use CTanner\LaravelDeepSync\Tests\Models\Tag;
use CTanner\LaravelDeepSync\Tests\TestCase;

class DeepSyncTest extends TestCase
{
    public function test_user_creation()
    {
        $user = User::create([
            'name' => 'Username'
        ]);
        $this->assertNotNull($user);
        $this->assertEquals(1, User::count());
    }
}