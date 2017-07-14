<?php

namespace Masterfri\Stateful\Traits;

use Masterfri\Stateful\Signal;
use Masterfri\Stateful\Exceptions\StatefulException;

trait StatefulTrait
{
    /**
     * List of already created state machines
     * 
     * @var array
     */ 
    protected static $readyStateMachines = [];
    
    /**
     * Name of attribute that defines entity state
     * 
     * @var string
     */
    protected $state_attribute = 'state';

    /**
     * Send signal for state changing
     * 
     * @param string $signal
     * @param array $params additional parameters
     */
    public function sendSignal($signal, $params=[])
    {
        $this->getStateMachine()->signal(new Signal($signal, $params), $this);
    }

    /**
     * Initialize entity state
     * 
     * @return void
     */ 
    public function enterInitialState()
    {
        $fsm = $this->getStateMachine();
        $fsm->getInitialState()->enter($this);
    }

    /**
     * Get current state of entity
     * 
     * @return string
     */
    public function getCurrentState()
    {
        $state = $this->{$this->state_attribute};
        if (empty($state)) {
            $this->enterInitialState();
        }
        return $state;
    }

    /**
     * Change entity state
     * 
     * @param string $state
     * @return void
     */
    public function changeState($state)
    {
        $this->{$this->state_attribute} = $state;
    }

    /**
     * Get state machine for this entity class
     * 
     * @return Masterfri\Stateful\FSM
     */
    public function getStateMachine()
    {
        $class = get_class($this);
        if (!isset(self::$readyStateMachines[$class])) {
            self::$readyStateMachines[$class] = $this->createStateMachine();
        }
        return self::$readyStateMachines[$class];
    }

    /**
     * Create state machine instance for this entity
     * @return Masterfri\Stateful\FSM
     */
    protected function createStateMachine()
    {
        throw new StatefulException('Method createStateMachine() must be overridden');
    }
}