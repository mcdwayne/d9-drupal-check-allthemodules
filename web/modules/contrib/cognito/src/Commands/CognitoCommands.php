<?php

namespace Drupal\cognito\Commands;

use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Helper\Table;

/**
 * Cognito commands.
 */
class CognitoCommands extends DrushCommands {

  /**
   * Diff users from a Cognito file with users in Drupal.
   *
   * @param $cognitoUserFile
   *   The file downloaded from Cognito.
   *
   * @command cognito:diff-users
   * @aliases diff-users
   */
  public function report($cognitoUserFile) {
    if ($cognitoUserFile[0] !== '/') {
      $cognitoUserFile = getcwd() . '/' . $cognitoUserFile;
    }
    if (!file_exists($cognitoUserFile)) {
      $this->logger()->warning('Unable to find file: ' . $cognitoUserFile);
      return;
    }

    $cognitoUserEmails = [];
    $json = json_decode(file_get_contents($cognitoUserFile), TRUE);
    foreach ($json as $row) {
      foreach ($row['Attributes'] as $attribute) {
        if ($attribute['Name'] === 'email') {
          $cognitoUserEmails[] = $attribute['Value'];
        }
      }
    }

    $query = \Drupal::database()->select('users_field_data', 'u')
      ->fields('u', ['mail']);
    $drupalUserEmails = array_keys($query->execute()->fetchAllAssoc('mail', \PDO::FETCH_ASSOC));

    $notInCognito = array_diff($drupalUserEmails, $cognitoUserEmails);
    $this->output()->writeln(sprintf('Showing %s users in Drupal that are not in Cognito', count($notInCognito)));
    $table = new Table($this->output());
    $table->setRows($this->wrap($notInCognito));
    $table->render();

    $notInDrupal = array_diff($cognitoUserEmails, $drupalUserEmails);
    $this->output()->writeln(sprintf('Showing %s users in Cognito that are not in Drupal', count($notInDrupal)));
    $table = new Table($this->output());

    $table->setRows($this->wrap($notInDrupal));
    $table->render();
  }

  /**
   * Wraps each value in an array.
   *
   * @param array $array
   *   Wraps each value in the array for rendering.
   *
   * @return array
   *   An array.
   */
  protected function wrap(array $array) {
    return array_map(function ($value) {
      return [$value];
    }, $array);
  }

}
