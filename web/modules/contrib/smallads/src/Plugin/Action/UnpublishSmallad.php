<?php

namespace Drupal\smallads\Plugin\Action;

use Drupal\smallads\Entity\SmalladInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets the ad scope to 0.
 *
 * @Action(
 *   id = "smallad_unpublish_action",
 *   label = @Translation("Unpublish small ad"),
 *   type = "smallad"
 * )
 */
class UnpublishSmallad extends ActionBase implements ContainerFactoryPluginInterface {

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
    foreach ($entities as $entity) {
      $entity->scope->value == SmalladInterface::SCOPE_PRIVATE;
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
    return $object->access('delete', $account, $return_as_object);
  }

}
