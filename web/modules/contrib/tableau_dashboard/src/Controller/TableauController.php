<?php

namespace Drupal\tableau_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Qstraza\TableauPHP\TableauPHP;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class TableauController which serves as an REST API.
 *
 * @package Drupal\tableau_dashboard\Controller
 */
class TableauController extends ControllerBase {

  /**
   * Returns a ticket for currently logged in User.
   *
   * If user is not logged in,
   * exception is thrown. If user does not exist on Tableau Server, it will
   * return -1 as specified in the Tableau docs.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   New ticket.
   *
   * @throws \Exception
   */
  public function getTicket() {
    if (!\Drupal::currentUser()->Id()) {
      return new JsonResponse([
        'result' => 'error',
      ], 400);
    }

    $config = \Drupal::config("tableau_dashboard.settings");
    $tableau = new TableauPHP(
      $config->get('url'),
      $config->get('admin_user'),
      $config->get('admin_user_password'),
      $config->get('site_name')
    );
    $user = User::load(\Drupal::currentUser()->id());

    if (empty($user->get('field_tableau_username')->getValue())) {

      $result = \Drupal::service('tableau_dashboard.user')->search($user);

      if ($result == FALSE) {
        // If the user already exists but hasn't got an account id then we must
        // create on manually.
        \Drupal::service('tableau_dashboard.user')->insert($user);
      }
      else {
        // Save the tableau access details to the user.
        $user->set('field_tableau_user_id', $result['id']);
        $user->set('field_tableau_username', $result['name']);
        $user->save();
      }
    }
    $username = $user->get('field_tableau_username')->first()->value;
    $ticket = $tableau->getNewTicket($username);

    return new JsonResponse($ticket);
  }

}
