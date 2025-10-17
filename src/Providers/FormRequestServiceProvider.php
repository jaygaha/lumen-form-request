<?php

declare(strict_types=1);

namespace JayGaha\LumenFormRequest\Providers;

use Illuminate\Support\ServiceProvider;
use JayGaha\LumenFormRequest\Requests\FormRequest;
use JayGaha\LumenFormRequest\Console\MakeFormRequestCommand;

class FormRequestServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->app->resolving(FormRequest::class, function ($form, $app) {
            $form = FormRequest::createFrom($app['request'], $form);
            $form->setContainer($app);
        });

        $this->app->afterResolving(FormRequest::class, function (FormRequest $form) {
            $form->validate();
        });
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // Register the command to create a new FormRequest class.
        $this->commands([
            MakeFormRequestCommand::class,
        ]);
    }
}
