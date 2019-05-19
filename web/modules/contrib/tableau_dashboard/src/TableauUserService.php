<?php

namespace Drupal\tableau_dashboard;

use Drupal\tableau_dashboard\TableauServiceInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class TableauUserService implements TableauUserServiceInterface {

  /**
   * @var \Qstraza\TableauPHP\TableauPHP
   */
  protected $tableau;

  /**
   * @var \Drupal\tableau_dashboard\TableauServiceInterface
   */
  protected $tableauService;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(TableauServiceInterface $tableauService, ConfigFactoryInterface $configFactory) {
    $this->tableauService = $tableauService;
    $this->configFactory = $configFactory;

    $this->tableau = $this->tableauService->getTableauObject();
  }

  /**
   * Search for a user.
   *
   * @param $user
   *
   * @return bool
   */
  public function search($user) {
    // Get the username of the Drupal user.
    $username = $user->get('name')->first()->value;

    // Ask tableau about this user.
    $results = $this->tableau->getUser($username);

    if (count($results['users']) > 0) {
      // We have a result so return it.
      return $results['users']['user'][0];
    }

    // Return false.
    return FALSE;
  }

  /**
   * Insert a user.
   *
   * @param $user
   */
  public function insert($user) {
    // When user is created in Drupal, it needs to be created in Tableau server as
    // well.
    $username = $user->get('name')->first()->value;

    try {
      $config = \Drupal::config("tableau_dashboard.settings");
      $tableau_user_role = $config->get('user_role');
      $tableau_group_id = $config->get('group_id');

      $response = $this->tableau->addUser($username, $tableau_user_role, NULL);
      $user_id = $response['user']['id'];

      if ($tableau_group_id) {
        // Group ID is set, so user needs to be enrolled in to the group.
        $this->tableau->addUserToGroup($user_id, $tableau_group_id);
      }

      $user->set('field_tableau_user_id', $user_id);
      $user->set('field_tableau_username', $username);
      $user->save();

      \Drupal::logger('tableau_dashboard')->info('User %username on Tableau created.',
        [
          '%username' => $username,
        ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('tableau_dashboard')->error('Error creating user %username on Tableau. Message: %msg',
        [
          '%username' => $username,
          '%msg' => $e->getMessage(),
        ]);
    }
  }

}
