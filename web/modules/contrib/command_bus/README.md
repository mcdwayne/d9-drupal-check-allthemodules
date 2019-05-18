# Command Bus

Provides a ready-to-use command bus for developers. This module provides no 
interface but only an API for executing commands via a command bus. The command
bus comes in handy if your application utilizes a service layer. For more 
information about service layers check out this great talk: 
https://www.youtube.com/watch?v=ajhqScWECMo.

## Installation

Install as you would normally install a contributed Drupal module. See: 
https://www.drupal.org/docs/8/extending-drupal-8/installing-modules for further
information.

## Configuration

This module has no configuration or modifiable settings.

## Usage

#### Basic usage

- Create your own `Command` class and extend the 
`\Drupal\command_bus\Command\Command` class. The whole command class will be 
available in your `CommandHandler` class. Data needed for the command can be 
passed in the constructor and assigned to a public property for example.

```php
<?php

namespace Drupal\test\Command;

use Drupal\command_bus\Command\Command;

/**
 * Class TestCommand.
 *
 * @package Drupal\test\Command
 */
class CreateData extends Command {

  public $data;

  /**
   * CreateData constructor.
   *
   * @param mixed $data
   *   The command data.
   */
  public function __construct($data) {
    $this->data = $data;
  }

}
```

- Create you own `CommandHandler` class and extend the 
`\Drupal\command_bus\Command\Command` class. Please note: the handler class 
needs to live in the same namespace as your `Command` class and needs to be 
appended with 'Handler'. For example: `CreateData` (command) and 
`CreateDataHandler` (command handler).

```php
<?php

namespace Drupal\test\Command;

use Drupal\command_bus\Handler\CommandHandler;
use Drupal\command_bus\Validator\Violations;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class CreateDataHandler.
 *
 * @package Drupal\test\Command
 */
class CreateDataHandler extends CommandHandler {

  use StringTranslationTrait;

  /**
   * Handles the command.
   */
  public function handle() {
    // Retrieve the command via the getCommand() method.
    $command = $this->getCommand();
    // Retrieve data from your command by calling its public properties.
    $data = $command->data;

    // Add your execution logic here.
  }

  /**
   * Rolls back the command.
   *
   * @param \Drupal\command_bus\Validator\Violations $violations
   *   The violations.
   */
  public function rollback(Violations $violations) {
    // Add your rollback logic here. For example display violation messages.
    foreach ($violations->getViolations() as $violation) {
      drupal_set_message($violation->getMessage(), 'warning');
    }
  }

}
```

- Instantiate your command, retrieve the command bus service and execute your
command.

```php
$data = [
    'foo' => 'bar',
  ];
$command = new CreateData($data);
\Drupal::service('command_bus.default')->execute($command);
```

#### Command validation

The Command Bus provides methods to validate your command before and after 
executing. Validation is done in your `Command` class by setting validators via
the `addPreValidator()` and `addPostValidator()` methods:

- Create your validators. Create a validator class by extending the 
`\Drupal\command_bus\Validator\Validator` class. You can set a message on the 
public property `$message` for logging or displaying in your `rollback()` 
method.

```php
<?php

namespace Drupal\test\Command;

use Drupal\command_bus\Validator\Validator;
use Drupal\command_bus\Validator\Violations;

/**
 * Class ValidOutcomeValidator.
 *
 * @package Drupal\test\Command
 */
class ValidOutcomeValidator extends Validator {

  public $message = 'Incorrect outcome.';

  /**
   * Validates a value.
   *
   * @param mixed $value
   *   The value to be validated.
   * @param \Drupal\command_bus\Validator\Violations $violations
   *   The violations.
   */
  public function validate($value, Violations $violations) {
    if ($value !== 'success') {
      $violations->addViolation($this);
    }
  }

}
```

- Attach your validators to your `Command` class. Multiple validators are 
allowed by calling the method again with a different validator.

```php
<?php

namespace Drupal\test\Command;

use Drupal\command_bus\Command\Command;

/**
 * Class TestCommand.
 *
 * @package Drupal\test\Command
 */
class CreateData extends Command {

  public $data;

  /**
   * CreateData constructor.
   *
   * @param mixed $data
   *   The command data.
   */
  public function __construct($data) {
    $this->data = $data;

    $this->addPreValidator(new SomeValidator());
    $this->addPostValidator(new ValidOutcomeValidator());
  }

}
```

There are 2 types of validators: pre and post validators. If a pre validator 
results in a violation the command is not run. If the post validator fails a 
`rollback()` method is invoked in your `CommandHandler` class passing the 
violations as the argument.
