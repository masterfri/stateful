<?php

namespace Masterfri\Stateful\Providers;

use Illuminate\Support\ServiceProvider;
use Masterfri\Stateful\EventMapping;

class StatefulServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application service
     *
     * @return void
     */
    public function boot()
    {
        $mappings = config('stateful.event_mapping', []);
        foreach ($mappings as $event => $signal) {
            $mapping = new EventMapping($event, $signal);
            $mapping->bind();
        }
    }
}