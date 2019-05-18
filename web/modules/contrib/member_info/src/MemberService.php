<?php
/**
 * @file
 */
namespace Drupal\member;

use Drupal\Core\Database\Connection;

class MemberService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public function getMemberInfo($user) {
    $member = [
      'name' => empty($user->get('realname')->value) ? $user->get('name')->value : $user->get('realname')->value,
      'depart' => empty($user->get('depart')->value) ? '-' : taxonomy_term_load($user->get('depart')->value)->label(),
      'company' => empty($user->get('company')->value) ? '-' : taxonomy_term_load($user->get('company')->value)->label(),
    ];

    return $member;
  }
}

