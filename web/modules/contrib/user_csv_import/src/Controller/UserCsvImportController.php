<?php

namespace Drupal\user_csv_import\Controller;

use Drupal\user\Entity\User;
use Drupal\file\Entity\File;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Provides methods to import CSV files and convert to users.
 */
class UserCsvImportController {

  /**
   * Show import page.
   */
  public static function importPage() {

    $form = \Drupal::formBuilder()->getForm('Drupal\user_csv_import\Form\UserCsvImportForm');

    return $form;

  }

  /**
   * Processes an uploaded CSV file, creating a new user for each row of values.
   *
   * @param \Drupal\file\Entity\File $file
   *   The File entity to process.
   *
   * @param array $config
   *   An array of configuration containing fields and roles.
   *
   * @return array
   *   An associative array of values from the filename keyed by new uid.
   */
  public static function processUpload(File $file, array $config) {

    // Open the uploaded file.
    $handle = fopen($file->destination, 'r');

    $created = [];
    $i = 0;
    $row_positions = [];

    // Iterate hover it and store the values to a new User.
    while ($row = fgetcsv($handle)) {

      // If is the first row, compare the header values with
      // selected fields if config.
      if ($i == 0) {

        // Iterate over the selected fields to find their position.
        foreach ($config['fields'] as $key => $value) {

          // Search in the file for the position of the selected fields.
          $row_positions[$key] = array_search($key, $row);

        }

      }
      else {

        if ($values = self::prepareRow($row, $config, $row_positions)) {

          // Create the user.
          if ($uid = self::createUser($values)) {

            $created[$uid] = $values;

          }
        }

      }

      $i++;

    }

    return $created;

  }

  /**
   * Prepares a new user from an upload row and current config.
   *
   * @param array $row
   *   A row from the currently uploaded file.
   *
   * @param array $config
   *   An array of configuration containing:
   *   - roles: an array of role ids to assign to the user
   *   - password: a password for the imported users extracted from settings
   *   - status: the status to be assigned to the new users extracted from settings
   *
   * @param array $fields_position
   *   An array with the position of the selected fields.
   *
   * @return array
   *   New user values suitable for User::create().
   */
  public static function prepareRow(array $row, array $config, array $fields_position) {

    // Prepare username.
    $preferred_username = (strtolower($row[0]));

    // Check if the username exists.
    $i = 0;

    while (self::usernameExists($i ? $preferred_username . $i : $preferred_username)) {

      $i++;

    }

    // If exists, assign a number to the name.
    $username = $i ? $preferred_username . $i : $preferred_username;

    $user_data = [
      'uid' => NULL,
      'name' => $username,
      'pass' => $config['password'],
      'status' => $config['status'],
      'created' => \Drupal::time()->getRequestTime(),
      'roles' => array_values($config['roles']),
    ];

    // Add selected fields with their values to user data for store un database.
    foreach ($fields_position as $index => $position) {

      $user_data[$index] = $row[$position];

    }

    return $user_data;

  }

  /**
   * Returns user whose name matches $username.
   *
   * @param string $username
   *   Username to check.
   *
   * @return array
   *   Users whose names match username.
   */
  private static function usernameExists($username) {

    return \Drupal::entityQuery('user')->condition('name', $username)->execute();

  }

  /**
   * Creates a new user from prepared values.
   *
   * @param array $values
   *   Values prepared from prepareRow().
   *
   * @return \Drupal\user\Entity\User
   */
  private function createUser($values) {

    $user = User::create($values);

    try {

      // If new user stores well, return the ID.
      if ($user->save()) {

        return $user->id();

      }

    }
    // Show error on user creation.
    catch (EntityStorageException $e) {

      drupal_set_message(t('Could not create user %fname %lname (username: %uname) (email: %email); exception: %e', [
        '%e' => $e->getMessage(),
        '%fname' => $values['field_name_first'],
        '%lname' => $values['field_name_last'],
        '%uname' => $values['name'],
        '%email' => $values['mail'],
      ]), 'error');

    }

    return FALSE;

  }

}
