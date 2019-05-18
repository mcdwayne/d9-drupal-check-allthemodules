<?php


namespace Drupal\locker\Commands;

use Drush\Commands\DrushCommands;

class LockerCliService implements LockerCliServiceInterface{

  /**
   * A Drush 9.x command.
   *
   * @var \Drupal\locker\Commands\LockerCommands
   */
  protected $command;

  /**
   * Set the drush 9.x command.
   *
   * @param \Drupal\locker\Commands\LockerCommands $command
   *   A Drush 9.x command.
   */
  public function setCommand(DrushCommands $command) {
    $this->command = $command;
  }

  /**
   * Constructs a LockerCliService object.
   */
  public function __construct() {

  }


  /**
   * {@inheritdoc}
   */
  public function locker_drush_command() {
    $items = [];

    $items['lock'] = [
      'description' => "Lock site.",
      'arguments' => [
        'passphrase'    => "Enter your desired passphrase to unlock the site.",
        'username'      => "Enter your desired username to unlock the site.",
        'password'      => "Enter your desired password to unlock the site.",
      ],
      'examples' => [
        'drush lock u username password' => 'Lock your Drupal site with username = username & password = password.',
        'drush lock passphrase' => 'Lock your Drupal site with passphrase = passphrase.',
      ],
      'aliases' => ['lock'],
    ];

    $items['unlock'] = [
      'description' => "Unlock your Drupal site.",
      'examples' => [
        'drush unlock' => 'Unlock your Drupal site.',
      ],
      'aliases' => ['unlock'],
    ];
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function drush_locker_lock($passphrase = NULL, $user = NULL,
    $pass = NULL
  ) {
    $config = \Drupal::service('config.factory')->getEditable('locker.settings');
    if (empty($passphrase)) {
      $options = array(
        '1' => t('Passphrase'),
        '2' => t('Username/password'),
      );
      $choiced_option = drush_choice($options, t('
    Locker module - usage:

    drush lock u username password	- to lock site with username and password
    drush lock passphrase 		- to lock site only with passphrase
    drush unlock			- to unlock site

Please choose a option.'));

      if ($choiced_option == 0) {
        drupal_set_message(
          t('Error! Process cancelled.')
        );
      } else if ($choiced_option == 1) {
        $passphrase = drush_prompt(t('Please choose a passphrase'));
      } else if ($choiced_option == 2) {
        $passphrase = 'u';
        $user = drush_prompt(t('Please choose a username'));
        $pass = drush_prompt(t('Please choose a password'));
      }
    }

    if (isset($passphrase)) {
      if (empty($passphrase)) {
        drupal_set_message(
          t('Error! No passphrase found.')
        );
      } else if ($passphrase == 'u') {
        $passmd5 = md5($pass);
        $config
          ->set('locker_user', $user)
          ->set('locker_password', $passmd5);
        $locker_access_options = 'user_pass';
      } else {
        $config
          ->set('locker_passphrase', md5($passphrase));
        $locker_access_options = 'passphrase';
      }
    }

    if (!empty($locker_access_options)) {
      $config->set('locker_access_options', $locker_access_options);
      $config->set('locker_custom_url', 'unlock.html');
      $config->set('locker_site_locked', 'yes')->save();
      drupal_set_message(
        t('Successfully locked.')
      );
      $query = \Drupal::database()->delete('sessions');
      $query->execute();
    }
    drupal_flush_all_caches();
  }

  /**
   * {@inheritdoc}
   */
  public function drush_locker_unlock() {
    $config = \Drupal::service('config.factory')->getEditable('locker.settings');
    $config->set('locker_site_locked', '')->save();
    $config->delete();
    unset($_SESSION['locker_unlocked']);
    drupal_set_message(
      t('Site successfully unlocked.')
    );
    drupal_flush_all_caches();
  }


  public function drush_locker_generate_commands() {
    // Drush 8.x.
    $commands = $this->drush_locker_generate_commands_drush8();
    $filepath = DRUPAL_ROOT . '/' . drupal_get_path('module', 'locker') . '/drush/locker.drush.inc';
    file_put_contents($filepath, $commands);
    $this->drush_print("$filepath updated.");

    // Drush 9.x.
    $commands = $this->drush_locker_generate_commands_drush9();
    $filepath = DRUPAL_ROOT . '/' . drupal_get_path('module', 'locker') . '/src/Commands/LockerCommands.php';
    file_put_contents($filepath, $commands);
    $this->drush_print("$filepath updated.");
  }

  /**
   * Generate locker.drush.inc for Drush 8.x.
   *
   * @return string
   *   locker.drush.inc for Drush 8.x.
   *
   * @see drush/locker.drush.inc
   */
  protected function drush_locker_generate_commands_drush8() {
    $items = $this->locker_drush_command();
    $functions = [];
    foreach ($items as $command_key => $command_item) {

      // Command name.
      $functions[] = "
      /******************************************************************************/
      // drush $command_key. DO NOT EDIT.
      /******************************************************************************/";

      // Validate.
      $validate_method = 'drush_' . str_replace('-', '_', $command_key) . '_validate';
      $validate_hook = 'drush_' . str_replace('-', '_', $command_key) . '_validate';
      if (method_exists($this, $validate_method)) {
        $functions[] = "
/**
 * Implements drush_hook_COMMAND_validate().
 */
function $validate_hook() {
  return call_user_func_array([\Drupal::service('locker.cli_service'), '$validate_method'], func_get_args());
}";
      }

      // Commands.
      $command_method = 'drush_' . str_replace('-', '_', $command_key);
      $command_hook = 'drush_' . str_replace('-', '_', $command_key);
      if (method_exists($this, $command_method)) {
        $functions[] = "
/**
 * Implements drush_hook_COMMAND().
 */
function $command_hook() {
  return call_user_func_array([\Drupal::service('locker.cli_service'), '$command_method'], func_get_args());
}";
      }
    }

    // Build commands.
    $commands = Variable::export($this->locker_drush_command());
    // Remove [datatypes] which are only needed for Drush 9.x.
    $commands = preg_replace('/\[(boolean)\]\s+/', '', $commands);
    $commands = trim(preg_replace('/^/m', '  ', $commands));

    // Include.
    $functions = implode(PHP_EOL, $functions) . PHP_EOL;

    return "<?php

// @codingStandardsIgnoreFile

/**
 * This is file was generated using Drush. DO NOT EDIT. 
 *
 * @see drush locker-generate-commands
 * @see \Drupal\locker\Commands\DrushCliServiceBase::generate_commands_drush8
 */

/**
 * Implements hook_drush_command().
 */
function locker_drush_command() {
  return $commands;
}
$functions
";
  }

  /**
   * Generate LockerCommands class for Drush 9.x.
   *
   * @return string
   *   LockerCommands class for Drush 9.x.
   *
   * @see \Drupal\locker\Commands\LockerCommands
   */
  protected function drush_locker_generate_commands_drush9() {
    $items = $this->locker_drush_command();

    $methods = [];
    foreach ($items as $command_key => $command_item) {
      $command_name = str_replace('-', ':', $command_key);

      // Set defaults.
      $command_item += [
        'arguments' => [],
        'examples' => [],
        'aliases' => [],
      ];

      // Command name.
      $methods[] = "
  /****************************************************************************/
  // drush $command_name. DO NOT EDIT.
  /****************************************************************************/";

      // Validate.
      $validate_method = 'drush_' . str_replace('-', '_', $command_key) . '_validate';
      if (method_exists($this, $validate_method)) {
        $methods[] = "
  /**
   * @hook validate $command_name
   */
  public function $validate_method(CommandData \$commandData) {
    \$arguments = \$commandData->arguments();
    array_shift(\$arguments);
    call_user_func_array([\$this->cliService, '$validate_method'], \$arguments);
  }";
      }

      // Command.
      $command_method = 'drush_' . str_replace('-', '_', $command_key);
      if (method_exists($this, $command_method)) {
        $command_params = [];
        $command_arguments = [];

        $command_annotations = [];
        // command.
        $command_annotations[] = "@command $command_name";
        // params.
        foreach ($command_item['arguments'] as $argument_name => $argument_description) {
          $command_annotations[] = "@param \$$argument_name $argument_description";
          $command_params[] = "\$$argument_name = NULL";
          $command_arguments[] = "\$$argument_name";
        }
        // options.
        $command_options = [];
        foreach ($command_item['options'] as $option_name => $option_description) {
          $option_default = NULL;
          // Parse [datatype] from option description.
          if (preg_match('/\[(boolean)\]\s+/', $option_description, $match)) {
            $option_description = preg_replace('/\[(boolean)\]\s+/', '', $option_description);
            switch ($match[1]) {
              case 'boolean':
                $option_default = FALSE;
                break;
            }
          }

          $command_annotations[] = "@option $option_name $option_description";
          $command_options[$option_name] = $option_default;
        }
        if ($command_options) {
          $command_options = Variable::export($command_options);
          $command_options = preg_replace('/\s+/', ' ', $command_options);
          $command_options = preg_replace('/array\(\s+/', '[', $command_options);
          $command_options = preg_replace('/, \)/', ']', $command_options);
          $command_params[] = "array \$options = $command_options";
        }

        // usage.
        foreach ($command_item['examples'] as $example_name => $example_description) {
          $command_annotations[] = "@usage $example_name";
          $command_annotations[] = "  $example_description";
        }
        // aliases.
        if ($command_item['aliases']) {
          $command_annotations[] = "@aliases " . implode(',', $command_item['aliases']);
        }

        $command_annotations = '   * ' . implode(PHP_EOL . '   * ', $command_annotations);
        $command_params = implode(', ', $command_params);
        $command_arguments = implode(', ', $command_arguments);

        $methods[] = "
  /**
   * {$command_item['description']}
   *
$command_annotations
   */
  public function $command_method($command_params) {
    \$this->cliService->$command_method($command_arguments);
  }";
      }
    }

    // Class.
    $methods = implode(PHP_EOL, $methods) . PHP_EOL;

    return "<?php
// @codingStandardsIgnoreFile

/**
 * This is file was generated using Drush. DO NOT EDIT. 
 *
 * @see drush locker-generate-commands
 * @see \Drupal\locker\Commands\DrushCliServiceBase::generate_commands_drush9
 */
namespace Drupal\locker\Commands;

use Consolidation\AnnotatedCommand\CommandData;

/**
 * locker commands for Drush 9.x.
 */
class LockerCommands extends LockerCommandsBase {
$methods
}";
  }
}