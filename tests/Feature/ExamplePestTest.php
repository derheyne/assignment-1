<?php

use App\Models\User;

it('can access the database', function () {
    $user = User::factory()->create();

    expect(
        User::findOrFail($user->id)->toArray()
    )->toEqual($user->toArray());
});
