<?php

namespace Masterfri\Stateful;

use Masterfri\Stateful\Contracts\Stateful;
use Illuminate\Support\Arr;

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
     * Example:
     * FSM::build([
     *     'states' => [
     *         'init' => ['initial' => true],
     *         'state1' => ['enter' => function($entity) { ... }, 'leave' => function($entity) { ... }],
     *         'state2',
     *         'state3',
     *         'end' => ['finite' => true, 'enter' => function($entity) { ... }],
     *     ],
     *     'transitions' => [
     *          ['initial', 'state1', 'signal1'],
     *          ['initial', 'state2', 'signal2'],
     *          ['state1', 'state3', 'signal3', 'condition' => function($entity, $signal) { ... }],
     *          ['state3', 'end', 'signal4', 'transit' => function($entity) { ... }],
     *     ]
     * ])
     * 
     * @param array $config
     * @return Masterfri\Stateful\FSM
     */ 
    public static function build(array $config)
    {
        $fsm = new static();
        $states = Arr::get($config, 'states', []);
        $transitions = Arr::get($config, 'transitions', []);
        
        foreach ($states as $name => $options) {
            if (is_string($options)) {
                $fsm->addState(new State($options));
            } else {
                if (Arr::get($options, 'initial', false)) {
                    $state = new State($name, State::INITIAL);
                } elseif (Arr::get($options, 'finite', false)) {
                    $state = new State($name, State::FINITE);
                } else {
                    $state = new State($name);
                }
                $fsm->addState($state);
                if ($fn = Arr::get($options, 'enter')) {
                    $state->entering($fn);
                }
                if ($fn = Arr::get($options, 'leave')) {
                    $state->leaving($fn);
                }
            }
        }
        
        foreach ($transitions as $options) {
            $from = array_shift($options);
            $to = array_shift($options);
            $signal = array_shift($options);
            $transition = new Transition($from, $to, $signal);
            $fsm->addTransition($transition);
            if ($fn = Arr::get($options, 'transit')) {
                $transition->transiting($fn);
            }
            if ($fn = Arr::get($options, 'condition')) {
                $transition->condition($fn);
            }
        }
        
        return $fsm;
    }
}