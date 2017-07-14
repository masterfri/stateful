<?php

namespace Masterfri\Stateful;

use Closure;
use Masterfri\Stateful\Contracts\Stateful;

class Transition
{
    /**
     * Source state name
     * 
     * @var string
     */ 
    protected $from;

    /**
     * Destination state name
     * 
     * @var string
     */ 
    protected $to;
    
    /**
     * Type of signal that may trigger transition
     * 
     * @var string
     */ 
    protected $signal;
    
    /**
     * Function that is executed when transition is triggered
     * 
     * @var Closure
     */
    protected $onTransition;
    
    /**
     * Function that is used as condition for transition
     * 
     * @var Closure
     */
    protected $testFn;

    /**
     * Constructor
     * 
     * @param string $from
     * @param string $to
     * @param string $signal
     */ 
    public function __construct($from, $to, $signal)
    {
        $this->from = $from;
        $this->to = $to;
        $this->signal = $signal;
    }

    /**
     * Define transition function
     * 
     * @param Closure $callback
     * @return Masterfri\Stateful\Transition
     */ 
    public function transiting(Closure $callback)
    {
        $this->onTransition = $callback;
        return $this;
    }

    /**
     * Define transition condition
     * 
     * @param Closure $callback
     * @return Masterfri\Stateful\Transition
     */ 
    public function condition(Closure $callback)
    {
        $this->testFn = $callback;
        return $this;
    }

    /**
     * Get source state name
     * 
     * @return string
     */ 
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Get destination state name
     * 
     * @return string
     */ 
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Check if transition has to be triggered
     * 
     * @param Masterfri\Stateful\Signal $signal
     * @param Masterfri\Stateful\Contracts\Stateful $entity
     * @return bool
     */ 
    public function test(Signal $signal, Stateful $entity)
    {
        if ($entity->getCurrentState() == $this->from && $signal->getType() == $this->signal) {
            if ($this->testFn) {
                return call_user_func($this->testFn, $entity, $signal);
            }
            return true;
        }
        return false;
    }

    /**
     * Process transition
     * 
     * @param Masterfri\Stateful\Contracts\Stateful $entity
     * @return void
     */ 
    public function transit(Stateful $entity)
    {
        if ($this->onTransition) {
            call_user_func($this->onTransition, $entity);
        }
    }
}