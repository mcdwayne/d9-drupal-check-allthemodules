<?php

namespace Drupal\user_hash\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Cache\Cache;

/**
 * Generate user hashes.
 *
 * @Action(
 *   id = "user_generate_user_hash_action",
 *   label = @Translation("Generate hash for the selected user(s)"),
 *   type = "user"
 * )
 */
class GenerateUserHash extends ActionBase {

  protected $cacheService;

  /**
   * Class constructor.
   */
  public function __construct($cacheService) {
    $this->cacheService = $cacheService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache.cache')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    if ($account !== FALSE) {
      $hash = \Drupal::service('user_hash.services')->generateHash();
      \Drupal::service('user.data')->set('user_hash', $account->id(), 'hash', $hash);
      Cache::invalidateTags(['user:' . $account->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE))
      ->andIf($object->hasPermission('use user_hash') ? AccessResult::allowed() : AccessResult::forbidden());

    return $return_as_object ? $access : $access->isAllowed();
  }

}
