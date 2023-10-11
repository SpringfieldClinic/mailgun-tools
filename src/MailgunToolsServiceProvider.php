<?php

namespace SpringfieldClinic\MailgunTools;

use Illuminate\Support\ServiceProvider;
use SpringfieldClinic\MailgunTools\Commands\CheckOptOut;

class MailgunToolsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->configPath() => config_path('mailgun-tools.php'),
            ], 'config');

            $this->commands([
                CheckOptOut::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'mailgun-tools');
    }

    protected function configPath(): string
    {
        return __DIR__ . '/../config/mailgun-tools.php';
    }
}
