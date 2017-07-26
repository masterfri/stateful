<?php

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Contracts\Console\Kernel;
use Masterfri\Stateful\Contracts\Stateful;
use Masterfri\Stateful\Traits\StatefulTrait;
use Masterfri\Stateful\Facades\FSM;
use Masterfri\Stateful\Signal;
use Masterfri\Stateful\Exceptions\StatefulException;
use Masterfri\Stateful\EventMapping;

class StatefulTest extends TestCase
{
    /**
     * Boot application
     */ 
    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }
    
    /**
     * Workflow test
     */
    public function testWorkflow()
    {
        $e = new SimpleStateful();
        $e->enterInitialState();
        
        $this->assertEquals($e->state, 'initial');
        $this->assertEquals($e->status, 'assigning');
        
        $e->sendSignal('save');
        
        $this->assertEquals($e->state, 'assigned');
        $this->assertEquals($e->status, 'implementation');
        
        $e->sendSignal('save');
        
        $this->assertEquals($e->state, 'assigned');
        
        $e->done = true;
        $e->sendSignal('save');
        
        $this->assertEquals($e->state, 'implemented');
        $this->assertEquals($e->status, 'review');
        
        $e->sendSignal('save');
        
        $this->assertEquals($e->state, 'assigned');
        $this->assertEquals($e->status, 'failed');
        
        $e->sendSignal('save');
        
        $this->assertEquals($e->state, 'implemented');
        
        $e->valid = true;
        $e->sendSignal('save');
        
        $this->assertEquals($e->state, 'reviewed');
        $this->assertEquals($e->status, 'success');
        
        $e->sendSignal('save');
        
        $this->assertEquals($e->state, 'completed');
        $this->assertEquals($e->status, 'done');
        
        $e->sendSignal('save');
        
        $this->assertEquals($e->state, 'completed');
    }
    
    /**
     * Exception test: undefined destination state
     */
    public function testException1()
    {
        $this->expectException(StatefulException::class);
        $e = new InvalidStateful1();
        $e->enterInitialState();
    }
    
    /**
     * Exception test: undefined source state
     */
    public function testException2()
    {
        $this->expectException(StatefulException::class);
        $e = new InvalidStateful2();
        $e->enterInitialState();
    }
    
    /**
     * Exception test: transition from finite state
     */
    public function testException3()
    {
        $this->expectException(StatefulException::class);
        $e = new InvalidStateful3();
        $e->enterInitialState();
    }
    
    /**
     * Exception test: no initial state
     */
    public function testException4()
    {
        $this->expectException(StatefulException::class);
        $e = new InvalidStateful4();
        $e->enterInitialState();
    }
    
    /**
     * Event mapping test
     */
    public function testEvents()
    {
        FSM::map('statefultest:implement', 'implement');
        FSM::map('statefultest:success', 'complete', ['success' => true]);
        FSM::map('statefultest:fail', 'complete', ['success' => false]);
            
        $e = new SimpleStateful2();
        $e->enterInitialState();
        
        event('statefultest:implement', [$e]);
        $this->assertEquals($e->state, 'implemented');

        event('statefultest:fail', [$e]);
        $this->assertEquals($e->state, 'implemented');

        event('statefultest:success', [$e]);
        $this->assertEquals($e->state, 'completed');
    }
}

class SimpleStateful implements Stateful
{
    use StatefulTrait;

    public $valid = false;
    public $done = false;
    public $status;
    public $state;

    public function __construct()
    {
    }

    public function createStateMachine()
    {
        $fsm = FSM::create();
        
        $fsm->addState(FSM::createInitialState('initial'))->entering(function($model) {
            $model->status = 'assigning';
        });
        $fsm->addState(FSM::createState('assigned'));
        $fsm->addState(FSM::createState('implemented'));
        $fsm->addState(FSM::createState('reviewed'));
        $fsm->addState(FSM::createFiniteState('completed'));
        
        $fsm->addTransition(FSM::createTransition('initial', 'assigned', 'save'))->transiting(function($model) {
            $model->status = 'implementation';
        });
        $fsm->addTransition(FSM::createTransition('assigned', 'implemented', 'save'))->condition(function($model) {
            return $model->done;
        })->transiting(function($model) {
            $model->status = 'review';
        });
        $fsm->addTransition(FSM::createTransition('implemented', 'reviewed', 'save'))->condition(function($model) {
            return $model->valid;
        })->transiting(function($model) {
            $model->status = 'success';
        });
        $fsm->addTransition(FSM::createTransition('implemented', 'assigned', 'save'))->condition(function($model) {
            return !$model->valid;
        })->transiting(function($model) {
            $model->status = 'failed';
        });
        $fsm->addTransition(FSM::createTransition('reviewed', 'completed', 'save'))->transiting(function($model) {
            $model->status = 'done';
        });
        
        return $fsm;
    }
}

class InvalidStateful implements Stateful
{
    use StatefulTrait;

    public function __construct()
    {
    }

    public function createStateMachine()
    {
        $fsm = FSM::create();
        $fsm->addState(FSM::createInitialState('initial'));
        $fsm->addState(FSM::createFiniteState('completed'));
        return $fsm;
    }
}

class InvalidStateful1 extends InvalidStateful
{
    public function createStateMachine()
    {
        $fsm = parent::createStateMachine();
        $fsm->addTransition(FSM::createTransition('initial', 'assigned', 'save'));
    }
}

class InvalidStateful2 extends InvalidStateful
{
    public function createStateMachine()
    {
        $fsm = parent::createStateMachine();
        $fsm->addTransition(FSM::createTransition('assigned', 'completed', 'save'));
    }
}

class InvalidStateful3 extends InvalidStateful
{
    public function createStateMachine()
    {
        $fsm = parent::createStateMachine();
        $fsm->addTransition(FSM::createTransition('completed', 'initial', 'save'));
    }
}

class InvalidStateful4 extends InvalidStateful
{
    public function createStateMachine()
    {
        $fsm = parent::createStateMachine();
        $fsm->removeState('initial');
        return $fsm;
    }
}

class SimpleStateful2 implements Stateful
{
    use StatefulTrait;

    public $state;

    public function __construct()
    {
    }

    public function createStateMachine()
    {
        $fsm = FSM::create();
        
        $fsm->addState(FSM::createInitialState('initial'));
        $fsm->addState(FSM::createState('implemented'));
        $fsm->addState(FSM::createFiniteState('completed'));
        
        $fsm->addTransition(FSM::createTransition('initial', 'implemented', 'implement'));
        $fsm->addTransition(FSM::createTransition('implemented', 'completed', 'complete'))->condition(function($e, $s) {
            return $s->getArgument('success') === true;
        });

        return $fsm;
    }
}