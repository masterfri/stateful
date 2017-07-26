<?php

namespace Masterfri\Stateful;

use Illuminate\Support\Arr;

class Builder
{
    /**
     * Build state machine based on provided configuration
     * Example:
     * $builder->create([
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
    public function create(array $config = [])
    {
        $fsm = new FSM();
        $states = Arr::get($config, 'states', []);
        $transitions = Arr::get($config, 'transitions', []);
        
        foreach ($states as $name => $options) {
            if (is_string($options)) {
                $fsm->addState($this->createState($options));
            } else {
                if (Arr::get($options, 'initial', false)) {
                    $state = $this->createInitialState($name);
                } elseif (Arr::get($options, 'finite', false)) {
                    $state = $this->createFiniteState($name);
                } else {
                    $state = $this->createState($name);
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
            $transition = $this->createTransition($from, $to, $signal);
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
    
    /**
     * Create a state
     * 
     * @param string $name
     * @param int $type
     * @return Masterfri\Stateful\State
     */
    public function createState($name, $type=State::MEDIATE)
    {
        return new State($name, $type);
    }
    
    /**
     * Create an initial state
     * 
     * @param string $name
     * @return Masterfri\Stateful\State
     */
    public function createInitialState($name)
    {
        return $this->createState($name, State::INITIAL);
    }
    
    /**
     * Create a finite state
     * 
     * @param string $name
     * @return Masterfri\Stateful\State
     */
    public function createFiniteState($name)
    {
        return $this->createState($name, State::FINITE);
    }
    
    /**
     * Create a transition
     * 
     * @param string $from
     * @param string $to
     * @param string $signal
     * @return Masterfri\Stateful\Transition
     */ 
    public function createTransition($from, $to, $signal)
    {
        return new Transition($from, $to, $signal);
    }
    
    /**
     * Create event mapping
     * 
     * @param string $event
     * @param string $signal
     * @param array $params
     * @return void
     */ 
    public function map($event, $signal, array $params = [])
    {
        $mapping = new EventMapping($event, $signal, $params);
        $mapping->bind();
    }
}