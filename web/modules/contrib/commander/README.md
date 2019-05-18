# Commander module for Drupal 8

[![Build Status](https://travis-ci.org/mkudenko/commander.svg?branch=master)](https://travis-ci.org/mkudenko/commander)

Provides a command bus.

All you need to do is to create two classes, command and command handler, and pass the command object to the command bus.

## How to use the module

1. Create your command handler plugin. That is a class that knows how to execute your command. You can inject any services into your handler class.
```php
<?php

namespace Drupal\my_module\Plugin\CommandHandler;

use Drupal\commander\Contracts\CommandInterface;
use Drupal\commander\Plugin\CommandHandlerBase;

/**
 * @CommandHandler(
 *   id = "register_user_handler",
 *   label = @Translation("Register user handler"),
 * )
 */
class RegisterUserHandler extends CommandHandlerBase {

  public function execute(CommandInterface $command) {
    $data = $command->data;
    
    // Do cool stuff with $data. 

    return TRUE;
  }

}
```

2. Create your command class and pass any data to it that will be required for executing the command. Make sure to implement the `handlerPluginId` method which returns the plugin ID of your command handler.
```php
<?php

namespace Drupal\my_module\Commands;

use Drupal\commander\Contracts\CommandInterface;

class RegisterUser implements CommandInterface {

  public $data;

  public function __construct($data) {
    $this->data = $data;
  }
  
  public function handlerPluginId() {
    return 'register_user_handler';
  }

}
```


3. Send your command to the command bus for execution.
```php
<?php

$command = new RegisterUser($data);
\Drupal::service('commander.command_bus')->execute($command);
```

## Development on GitHub
[https://github.com/mkudenko/commander](https://github.com/mkudenko/commander)

