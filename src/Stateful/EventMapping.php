<?php

namespace Masterfri\Stateful;

use Masterfri\Stateful\Contracts\Stateful;
use Event;

class EventMapping
{
    /**
     * Event type
     * 
     * @var string
     */ 
    protected $event;

    /**
     * Signal type
     * 
     * @var string
     */ 
    protected $signal;
    
    /**
     * Signal params
     * 
     * @var array
     */ 
    protected $params;

    /**
     * Constructor
     * 
     * @param string $event
     * @param string $signal
     * @param array $params
     */ 
    public function __construct($event, $signal, array $params = [])
    {
        $this->event = $event;
        $this->signal = $signal;
        $this->params = $params;
    }

    /**
     * Bind signal to event
     * 
     * @return void
     */ 
    public function bind()
    {
        Event::listen($this->event, function($entity) {
            if ($entity instanceof Stateful) {
                $entity->sendSignal($this->signal, $this->params);
            }
        });
    }
}