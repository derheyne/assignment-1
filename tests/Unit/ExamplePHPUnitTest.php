<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExamplePHPUnitTest extends TestCase
{
    public function test_it_can_access_the_internet()
    {
        $this->assertTrue(
            in_array(Http::get('https://yesno.wtf/api')->json('answer'), ['yes', 'no'])
        );
    }
}
