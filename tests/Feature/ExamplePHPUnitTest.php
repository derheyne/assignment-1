<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ExamplePHPUnitTest extends TestCase
{
    public function test_it_can_access_the_database()
    {
        $user = User::factory()->create();

        $this->assertEquals($user->toArray(), User::findOrFail($user->id)->toArray());
    }
}
