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

Stateful must implement interface `Masterfri\Stateful\Contracts\Stateful`. You can find a simple example below:

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
        ['new', 'placed', 'save'],
        // Manager processed the order
        ['placed', 'processed', 'save',
          'condition' => function($model) {
            return $model->canBeProcessed();
          }
        ],
        // Manager reviewed order and marked it as non-processable
        ['placed', 'completed', 'save',
          'condition' => function($model) {
            return !$model->canBeProcessed();
          },
          'transit' => function($model) {
            $model->sendCustomerEmail('Sorry your order can not be processed', 'rejected.tmpl');
            $model->markAsRejected();
          }
        ],
        // Manager received information from delivery service and filled out delivery information on the order
        ['processed', 'shipped', 'save',
          'condition' => function($model) {
            return $model->deliveryCompleted();
          }
        ],
        // Manager received feedback from customer
        ['shipped', 'completed', 'save',
          'condition' => function($model) {
            return $model->feedbackReceived();
          }
        ]
      ]);
  }
```
