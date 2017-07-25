<?php

namespace Masterfri\Stateful;

use Masterfri\Stateful\Contracts\Stateful;

class FSM
{
    /**
     * List of states
     * 
     * @var Illuminate\Support\Collection
     */
    protected $states;
    
    /**
     * List of transitions
     * 
     * @var Illuminate\Support\Collection
     */ 
    protected $transitions;
    
    /**
     * Initial state
     * 
     * @var Masterfri\Stateful\State
     */ 
    protected $initial;

    /**
     * Constructor
     */ 
    public function __construct()
    {
        $this->states = collect([]);
        $this->transitions = collect([]);
    }

    /**
     * Add state to machine
     * 
     * @param Masterfri\Stateful\State $state
     * @return Masterfri\Stateful\State
     */ 
    public function addState(State $state)
    {
        if ($state->getType() == State::INITIAL) {
            if ($this->initial) {
                throw new Exceptions\StatefulException("Multiple initial states are not allowed");
            }
            $this->initial = $state;
        }
        $this->states->offsetSet($state->getName(), $state);
        return $state;
    }

    /**
     * Remove state by its name
     * 
     * @param string $name
     * @return Masterfri\Stateful\FSM
     */ 
    public function removeState($name)
    {
        if ($this->states->offsetExists($name)) {
            if ($this->states->get($name)->getType() == State::INITIAL) {
                $this->initial = null;
            }
            $this->states->forget($name);
            $this->transitions = $this->transitions->filter(function($t) use($name) {
                return $t->getFrom() != $name && $t->getTo() != $name;
            });
        }
        return $this;
    }

    /**
     * Add transition between states
     * 
     * @param Masterfri\Stateful\Transition $transition
     * @return Masterfri\Stateful\Transition
     */ 
    public function addTransition(Transition $transition)
    {
        $from = $transition->getFrom();
        $to = $transition->getTo();
        if (!$this->states->offsetExists($from)) {
            throw new Exceptions\StatefulException("State '{$from}' does not exist");
        }
        if (!$this->states->offsetExists($to)) {
            throw new Exceptions\StatefulException("State '{$to}' does not exist");
        }
        if ($this->states->get($from)->getType() == State::FINITE) {
            throw new Exceptions\StatefulException("Transitions from finite states are not allowed");
        }
        $this->transitions->push($transition);
        return $transition;
    }

    /**
     * Remove particular transition
     * 
     * @param Masterfri\Stateful\Transition $transition
     * @return Masterfri\Stateful\FSM
     */ 
    public function removeTransition(Transition $transition)
    {
        $this->transitions = $this->transitions->filter(function($t) use($transition) {
            return $t != $transition;
        });
        return $this;
    }

    /**
     * Process a signal
     * 
     * @param Masterfri\Stateful\Signal $signal
     * @param Masterfri\Stateful\Contracts\Stateful $entity
     * @return bool returns true if entity state was changed, otherwise returns false
     */ 
    public function signal(Signal $signal, Stateful $entity)
    {
        foreach ($this->transitions as $transition) {
            if ($transition->test($signal, $entity)) {
                $from = $this->states->get($transition->getFrom());
                $to = $this->states->get($transition->getTo());
                $from->leave($entity);
                $transition->transit($entity);
                $to->enter($entity);
                return true;
            }
        }
        return false;
    }

    /**
     * Get nitial state
     * 
     * @return Masterfri\Stateful\State
     */ 
    public function getInitialState()
    {
        if (!$this->initial) {
            throw new Exceptions\StatefulException("Initial state has not been defined");
        }
        
        return $this->initial;
    }
    
    /**
     * Build state machine based on provided configuration
     * 
     * @deprecated
     * @param array $config
     * @return Masterfri\Stateful\FSM
     */ 
    public static function build(array $config)
    {
        return Facades\FSM::create($config);
    }
}