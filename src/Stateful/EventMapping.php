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
     * Constructor
     * 
     * @param string $event
     * @param string $signal
     */ 
    public function __construct($event, $signal)
    {
        $this->event = $event;
        $this->signal = $signal;
    }

    /**
     * Bind signal to event
     * 
     * @return void
     */ 
    public function bind()
    {
        Event::listen($this->event, function($model) {
            if ($model instanceof Stateful) {
                $model->sendSignal($this->signal);
            }
        });
    }
}