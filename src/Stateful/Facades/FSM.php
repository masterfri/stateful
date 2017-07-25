<?php

namespace Masterfri\Stateful\Facades;

use Illuminate\Support\Facades\Facade;

class FSM extends Facade
{
    protected static function getFacadeAccessor() 
    {
        return 'fsm';
    }
}