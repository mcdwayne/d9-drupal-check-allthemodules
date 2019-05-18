# Eventor module for Drupal 8

[![Build Status](https://travis-ci.org/mkudenko/eventor.svg?branch=master)](https://travis-ci.org/mkudenko/eventor)

Simplifies dispatching and handling events. No need to keep track of underscored event names.

The main idea is to give your event class a readable name. The method name for handling that event in your event listener must be prefixed by `when`.

Examples:

Event: `UserHasRegistered`
Method: `whenUserHasRegistered`

Event: `EmailWasSent`
Method: `whenEmailWasSent`

## How to use the module

1. Create your event class and pass any data to it that will be required for handling the event.
```php
<?php

namespace Drupal\my_module\Events;

use Symfony\Component\EventDispatcher\Event;

class UserHasRegistered extends Event {

  public $user;

  public function __construct($user) {
    $this->user = $user;
  }

}
```

2. Create your event listener by extending the EventListener class provided by the module. The method name for handling the event must follow the pattern `when{EventClassName}`.
```php
<?php

namespace Drupal\my_module\EventSubscriber;

use Drupal\eventor\EventListener;
use Drupal\my_module\Events\UserHasRegistered;

/**
 * Class AdminNotifier.
 */
class AdminNotifier extends EventListener {

  public function whenUserHasRegistered(UserHasRegistered $event) {
    $registeredUser = $event->user; 
    
    // Notify site admin.
  }

}
```

3. Register your event listener as a service in `my_module.services.yml`.
```yaml
services:
  my_module.admin_notifier:
    class: Drupal\my_module\EventSubscriber\AdminNotifier
    arguments: []
    tags:
      - { name: event_subscriber }
```

4. Dispatch your event.
```php
<?php

$event = new UserHasRegistered($user);
\Drupal::service('eventor.event_dispatcher')->dispatch($event);
```

## Development on GitHub
[https://github.com/mkudenko/eventor](https://github.com/mkudenko/eventor)

