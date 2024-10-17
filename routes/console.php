<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('setup:test', function () {
    $ip = Http::get('https://api.ipify.org')->throw()->body();

    $this->output->success('It works!');
    $this->info('You IP address is: '.$ip);

    return 0;
})->purpose('Check you local setup')->hourly();
