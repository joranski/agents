<?php

namespace Joranski\Agents;

use Illuminate\Support\ServiceProvider;
use Joranski\Agents\Commands\AgentsInstall;
use Joranski\Agents\Commands\GitPull;
use Joranski\Agents\Commands\GitPush;

class AgentsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AgentsInstall::class,
                GitPull::class,
                GitPush::class,
            ]);
        }
    }
}
