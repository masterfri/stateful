<?php

namespace Masterfri\Stateful\Contracts;

use Masterfri\Stateful\Signal;

interface Stateful
{
    /**
     * Get current state of entity
     * 
     * @return string
     */
    public function getCurrentState();

    /**
     * Change entity state
     * 
     * @param string $state
     * @return void
     */
    public function changeState($state);
}