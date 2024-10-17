<?php

use Illuminate\Support\Facades\Http;

it('can access the internet', function () {
    expect(
        Http::get('https://yesno.wtf/api')->json('answer')
    )->toBeIn(['yes', 'no']);
});
