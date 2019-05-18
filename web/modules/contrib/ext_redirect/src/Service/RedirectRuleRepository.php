<?php

namespace Drupal\ext_redirect\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\ext_redirect\Entity\RedirectRule;

/**
 * Class RedirectRuleRepository.
 */
class RedirectRuleRepository implements RedirectRuleRepositoryInterface {

  /**
   * @var EntityStorageInterface
   */
  protected $storage;

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Constructs a new RedirectRuleRepository object.
   */
  public function __construct(EntityTypeManagerInterface $typeManager, Connection $database) {
    $this->storage = $typeManager->getStorage('redirect_rule');
    $this->database = $database;
  }

  /**
   * Gets redirect rule for host alias without specified path.
   *
   * @param $host string host name like alias.com
   *
   * @return RedirectRule|null
   *    Redirect rule entity or null if no rule found
   */
  public function getRuleForHostWithoutPath($host) {
    // If the host starts with "www", we have to remove that, cause the redirect
    // is stored in the database without "www.".
    $host = ltrim($host, 'www.');
    $query = $this->storage->getQuery('AND');

    $or = $query->orConditionGroup()
      ->notExists('source_path')
      ->condition('source_path', '*');

    $query->condition('source_site', $host)
      ->condition($or)
      ->range(0, 1);

    $result = $query->execute();

    if (empty($result)) {
      return NULL;
    }

    return RedirectRule::load(reset($result));
  }

  /**
   * Gets available redirect rules for specified host.
   *
   * @param $host string host name like alias.com
   *
   * @return array
   *    An array of RedirectRule entity. Empty array if nothing found.
   */
  public function getHostRules($host) {
    // If the host starts with "www", we have to remove that, cause the redirect
    // is stored in the dababase without "www.".
    $host = ltrim($host, 'www.');
    $query = $this->storage->getQuery('AND');

    // We need to include the "any" host here. Otherwise a host/* rule will
    // always be found and executed before the "any" rule.
    $or = $query->orConditionGroup()
      ->condition('source_site', $host)
      ->condition('source_site', 'any');

    $query->condition($or)
      ->exists('source_path');

    $query->sort('weight');
    $rids = $query->execute();

    if (!$rids) {
      return [];
    }

    $rules = [];

    foreach($rids as $rid) {
      $rules[] = RedirectRule::load($rid);
    }

    return $rules;
  }

  /**
   * Gets available redirect rules for any host .
   *
   * @return array
   *    An array of RedirectRule entity. Empty array if nothing found.
   */
  public function getGlobalRules() {
    $query = $this->storage->getQuery('AND');
    $rids = $query->condition('source_site', 'any')
      ->exists('source_path')
      ->sort('weight')
      ->execute();

    if (!$rids) {
      return [];
    }

    $rules = [];

    foreach($rids as $rid) {
      $rules[] = RedirectRule::load($rid);
    }

    return $rules;
  }
}
