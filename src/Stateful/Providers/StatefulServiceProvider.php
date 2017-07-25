<?php

namespace Masterfri\Stateful\Providers;

use Illuminate\Support\ServiceProvider;
use Masterfri\Stateful\EventMapping;
use Masterfri\Stateful\Builder;
use Illuminate\Support\Facades\App;

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
    
    /**
     * Register service
     * 
     * return void
     */ 
    public function register() 
    {
        App::bind('fsm', function() {
            return new Builder();
        });
    }
}