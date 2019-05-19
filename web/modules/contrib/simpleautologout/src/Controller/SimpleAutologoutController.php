<?php

namespace Drupal\simpleautologout\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Returns responses for autologout module routes.
 */
class SimpleAutologoutController extends ControllerBase {

  /**
   * The database connection that is used to check current logged in sessions.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The session information for current user.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  public function __construct(Connection $connection, Session $session) {
    $this->connection = $connection;
    $this->session = $session;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('session')
    );
  }

  /**
   * Get the last active time for specific user.
   */
  public function getUserLastActiveTime() {
    $account = $this->currentUser();  
    $user_session = $this->session->getId();
    $session_id = Crypt::hashBase64($user_session);

    $timestamp = $this->connection->select('sessions', 's')
      ->fields('s', ['timestamp'])
      ->condition('s.uid', $account->id(), '=')
      ->condition('s.sid', $session_id, '=')
      ->execute()
      ->fetchAssoc();

    if(!empty($timestamp['timestamp'])) {
      return new JsonResponse([
        'session_active' => 'true',
      ]);
    }
    else {
      return new JsonResponse([
        'session_active' => 'false',
      ]);
    }
    
  }

  public function logOut() {
    user_logout();
    return new JsonResponse([
      'logout' => 'true',
    ]);
  }
}
