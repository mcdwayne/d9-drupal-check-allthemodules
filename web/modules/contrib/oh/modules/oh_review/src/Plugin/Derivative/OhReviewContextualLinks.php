<?php

namespace Drupal\oh_review\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\oh_regular\OhRegularInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides contextual links for OH review.
 */
class OhReviewContextualLinks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * OH regular service.
   *
   * @var \Drupal\oh_regular\OhRegularInterface
   */
  protected $ohRegular;

  /**
   * Construct OhRegularSubscriber service.
   *
   * @param \Drupal\oh_regular\OhRegularInterface $ohRegular
   *   OH regular service.
   */
  public function __construct(OhRegularInterface $ohRegular) {
    $this->ohRegular = $ohRegular;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('oh_regular.mapping')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $mapping = $this->ohRegular->getAllMapping();
    $entityTypes = array_keys($mapping);
    foreach ($entityTypes as $entityType) {
      $routeName = $id = sprintf('entity.%s.oh_review', $entityType);
      $this->derivatives[$id] = $base_plugin_definition;
      $this->derivatives[$id]['route_name'] = $routeName;
      $this->derivatives[$id]['group'] = $entityType;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
