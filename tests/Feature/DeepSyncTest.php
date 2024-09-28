<?php

namespace CTanner\LaravelDeepSync\Tests\Feature;

use CTanner\LaravelDeepSync\Tests\Models\User;
use CTanner\LaravelDeepSync\Tests\Models\Post;
use CTanner\LaravelDeepSync\Tests\Models\Site;
use CTanner\LaravelDeepSync\Tests\Models\Tag;
use CTanner\LaravelDeepSync\Tests\TestCase;

class DeepSyncTest extends TestCase
{
    public function test_user_creation()
    {
        $user = User::factory()->create();

        $this->assertNotNull($user);
        $this->assertEquals(1, User::count());
    }

    public function test_single_level_cascade_delete()
    {
        $user = User::factory()->create();
        $post = Post::factory([
            'author_id' => $user->id
        ])->create();

        $this->assertEquals(1, User::count());
        $this->assertEquals(1, Post::count());

        $user->delete();

        $this->assertNull(User::find($user->id));
        $this->assertNull(Post::find($post->id));
    }

    public function test_multi_level_cascade_delete()
    {
        $user = User::factory()->create();
        $post = Post::factory([
            'author_id' => $user->id
        ])->create();
        $tag = Tag::factory()->create();

        $post->tags()->attach($tag->id);

        $this->assertEquals(1, User::count());
        $this->assertEquals(1, Post::count());
        $this->assertEquals(1, Tag::count());

        $user->delete();

        $this->assertNull(User::find($user->id));
        $this->assertNull(Post::find($post->id));
        $this->assertNull(Tag::find($tag->id));
    }

    public function test_single_level_state_change()
    {
        $user = User::factory()->create();
        $post = Post::factory([
            'author_id' => $user->id
        ])->create();

        $this->assertEquals(1, User::count());
        $this->assertEquals(1, Post::count());

        $user->update(['is_active' => 0]);

        $this->assertEquals(0, Post::find($post->id)->is_active);
        $this->assertEquals($user->is_active, Post::find($post->id)->is_active);
    }

    public function test_multi_inheritance()
    {
        // Create 2 users
        $users = User::factory(2)->create();

        // Create 2 posts, associate 1 to each user
        $post1 = Post::factory([
            'author_id' => $users[0]->id
        ])->create();
        $post2 = Post::factory([
            'author_id' => $users[1]->id
        ])->create();

        // Create 2 tags, associate tag #1 to both posts, tag #2 to only 1 post
        $tags = Tag::factory(2)->create();

        $post1->tags()->attach($tags[0]->id);
        $post2->tags()->attach($tags[0]->id);
        $post1->tags()->attach($tags[1]->id);

        $this->assertEquals(2, User::count());
        $this->assertEquals(2, Post::count());
        $this->assertEquals(2, Tag::count());
        $this->assertEquals(2, $post1->tags()->count());
        $this->assertEquals(1, $post2->tags()->count());

        /**
         * Deleting user[0] should also delete the associated:
         * - post1
         * - tag[1]
         */
        $users[0]->delete();

        $this->assertNull(Post::find($post1->id));
        $this->assertNull(Tag::find($tags[1]->id));

        /**
         * post2 and tag[0] should still be present in the system
         */
        $this->assertNotNull(Post::find($post2->id));
        $this->assertNotNull(Tag::find($tags[0]->id));

    }

    public function test_state_sync()
    {
        $sites = Site::factory(2)->create();
        $users = User::factory(2)->create();
        $post1 = Post::factory([
            'author_id' => $users[0]->id
        ])->create();
        $post2 = Post::factory([
            'author_id' => $users[1]->id
        ])->create();

        $users[0]->sites()->attach($sites[0]->id);
        $users[0]->sites()->attach($sites[1]->id);

        $users[1]->sites()->attach($sites[0]->id);

        $sites[0]->update(['is_active' => 0]);

        $this->assertEquals(0, User::find($users[1]->id)->is_active);
        $this->assertEquals(1, User::find($users[0]->id)->is_active);
        $this->assertEquals(0, Post::find($post2->id)->is_active);
        $this->assertEquals(1, Post::find($post1->id)->is_active);
    }
    
}