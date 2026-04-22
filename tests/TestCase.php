<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Helpers\AuthenticationHelper;
use Tests\Helpers\HttpMockHelper;
use Tests\Helpers\StorageHelper;

abstract class TestCase extends BaseTestCase
{
    use AuthenticationHelper;
    use HttpMockHelper;
    use StorageHelper;
}
