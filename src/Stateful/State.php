<?php

namespace Masterfri\Stateful;

use Closure;
use Masterfri\Stateful\Contracts\Stateful;

class State
{
    const INITIAL = 1;
    const MEDIATE = 2;
    const FINITE = 3;

    /**
     * State name
     * 
     * @var string
     */ 
    protected $name;

    /**
     * State type
     * 
     * @var int
     */ 
    protected $type;
    
    /**
     * Function that is executed when entity enters the state
     * 
     * @var Closure
     */ 
    protected $onEnter;
    
    /**
     * Function that is executed when entity leaves the state
     * 
     * @var Closure
     */ 
    protected $onLeave;

    /**
     * Constructor
     * 
     * @param string $name
     * @param int $type
     */ 
    public function __construct($name, $type=self::MEDIATE)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * Define enter state function
     * 
     * @param Closure $callback
     * @return Masterfri\Stateful\State
     */ 
    public function entering(Closure $callback)
    {
        $this->onEnter = $callback;
        return $this;
    }

    /**
     * Define leave state function
     * 
     * @param Closure $callback
     * @return Masterfri\Stateful\State
     */ 
    public function leaving(Closure $callback)
    {
        $this->onLeave = $callback;
        return $this;
    }

    /**
     * Get state name
     * 
     * @return string
     */ 
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get state type
     * 
     * @return int
     */ 
    public function getType()
    {
        return $this->type;
    }

    /**
     * Enter the state
     * 
     * @param Masterfri\Stateful\Contracts\Stateful $entity
     * @return void
     */ 
    public function enter(Stateful $entity)
    {
        $entity->changeState($this->name);
        if ($this->onEnter) {
            call_user_func($this->onEnter, $entity);
        }
    }

    /**
     * Leave the state
     * 
     * @param Masterfri\Stateful\Contracts\Stateful $entity
     * @return void
     */ 
    public function leave(Stateful $entity)
    {
        if ($this->onLeave) {
            call_user_func($this->onLeave, $entity);
        }
    }
}