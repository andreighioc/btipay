<?php

namespace AndreiGhioc\BtiPay\Tests;

use AndreiGhioc\BtiPay\BtiPayServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            BtiPayServiceProvider::class,
        ];
    }
}
