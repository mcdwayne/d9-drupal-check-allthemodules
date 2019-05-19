<?php

namespace Drupal\tmgmt_memory\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Disables a SegmentTranslation.
 *
 * @Action(
 *   id = "tmgmt_memory_segment_translation_disable",
 *   label = @Translation("Disable"),
 *   type = "tmgmt_memory_segment_translation",
 * )
 */
class DisableSegmentTranslation extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    /** @var \Drupal\tmgmt_memory\SegmentTranslationInterface $entity */
    foreach ($entities as $entity) {
      $entity->setState(FALSE);
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
    $this->executeMultiple(array($object));
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIfHasPermission($account, 'administer tmgmt');
    return $return_as_object ? $result : $result->isAllowed();
  }

}
