<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Tests;

use Illuminate\Foundation\Application;

/**
 * Test case for the REST API suite. Flips the module into `api` HTTP mode at
 * environment-definition time (before the provider boots) so the route group is
 * actually registered. The rest of the suite keeps the safe-by-default
 * `headless` mode from the base {@see TestCase}.
 */
abstract class ApiTestCase extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('resource-library.http.mode', 'api');
    }
}
