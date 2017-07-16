## Introduction

Stateful is yet another implementation of finite state machine for Laravel 5. 
It allows turn any class into stateful entity with minimal efforts. Basic concept of 
this particular implementation is that changes of state of entities are triggered by
signals, that can be sent to entity manually or can be mapped to events.

## Installation

Stateful can be installed via composer

```
composer require masterfri/stateful
```
If you want to map signals to events, you have to registed service provider in `config/app.php` as follow:

```
'providers' => [
    ...
    Masterfri\Stateful\Providers\StatefulServiceProvider::class,
]
```

Then you need to create `config/stateful.php` with content like this:

```
return [
  'event_mapping' => [
    'eloquent.saving: *' => 'save',
    ...
  ],
];
```
You can define as many relations between events and signals as you need. Different events 
can be bound to the same signal, as well as one event can trigger different signals.

## Defining of stateful entity

Stateful must implement interface `Masterfri\Stateful\Contracts\Stateful`. Trait 
`Masterfri\Stateful\Traits\StatefulTrait` contains all necessary methods to control your entity,
you only have to implement method `createStateMachine()` when your state machine is defined.

```
public function createStateMachine()
{
  return FSM::build([
    'states' => [ // list of states 
      'state1', // state can be defined without options
      'state2' => [ // or with options
        'initial' => true, // optional, defines that state is initial, there must be exactly one initial state
        'enter' => function($entity) {}, // optional, this function is executed when entity enters the state
        'leave' => function($entity) {}, // optional, this function is executed when entity leaves the state
        'finite' => true, // optional, defines that state is finite, there may be any amount of finite states
      ],
    ],
    'transitions' => [ // list of transitions
      [
        'source', // source state name
        'destination', // destination state name
        'signal', // type of signal that can trigger this transition
        'condition' => function($entity, $signal) {}, // optional, this finction is an additional condition that defines if transition has to be triggered
        'transit' => function($entity) {}, // optional, this function is executed when entity goes through the transition 
      ]
    ],
  ]);
}
```

Here is a simple example:

```
use Masterfri\Stateful\Contracts\Stateful;
use Masterfri\Stateful\Traits\StatefulTrait;
use Masterfri\Stateful\FSM;

class Order extends Model implements Stateful
{
  use StatefulTrait;
  ...
  public function createStateMachine()
  {
    return FSM::build([
      'states' => [
        'new' => ['initial' => true],
        'placed' => [
          'enter' => function($model) {
            $model->sendCustomerEmail('Your order has been placed', 'placed.tmpl');
          },
        ],
        'processed',
        'shipped' => [
          'enter' => function($model) {
            $model->sendCustomerEmail('Your order has been shipped', 'shipped.tmpl');
          },
        ],
        'completed' => ['finite' => true],
      ],
      'transitions' => [
        // Order is submitted by customer
        ['new', 'placed', 'submit'],
        // Manager processed the order
        ['placed', 'processed', 'process',
          'condition' => function($model) {
            return $model->canBeProcessed();
          }
        ],
        // Manager reviewed order and marked it as non-processable
        ['placed', 'completed', 'process',
          'condition' => function($model) {
            return !$model->canBeProcessed();
          },
          'transit' => function($model) {
            $model->sendCustomerEmail('Sorry your order can not be processed', 'rejected.tmpl');
            $model->markAsRejected();
          }
        ],
        // Manager received information from delivery service and filled out delivery information on the order
        ['processed', 'shipped', 'ship'],
        // Manager received feedback from customer
        ['shipped', 'completed', 'close']
      ]);
  }
```
