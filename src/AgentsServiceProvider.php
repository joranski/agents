<?php

namespace Joranski\Agents;

use Illuminate\Support\ServiceProvider;
use Joranski\Agents\Commands\AgentsInstall;
use Joranski\Agents\Commands\AgentsUpdate;
use Joranski\Agents\Commands\GitPull;
use Joranski\Agents\Commands\GitPush;

class AgentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->pointBoostAtCentralSkills();
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AgentsInstall::class,
                AgentsUpdate::class,
                GitPull::class,
                GitPush::class,
            ]);
        }
    }

    /**
     * Have Laravel Boost write Cursor's skills (and Claude Code's, once
     * `.claude/skills` is linked to it) into `.agents/skills`, so every agent
     * shares one skills directory. Values set in config/boost.php win.
     */
    private function pointBoostAtCentralSkills(): void
    {
        $config = $this->app['config'];

        if ($config->get('boost.agents.cursor.skills_path') === null) {
            $config->set('boost.agents.cursor.skills_path', '.agents/skills');
        }

        if ($config->get('boost.agents.claude_code.skills_path') === null && is_link($this->app->basePath('.claude/skills'))) {
            $config->set('boost.agents.claude_code.skills_path', '.agents/skills');
        }
    }
}
