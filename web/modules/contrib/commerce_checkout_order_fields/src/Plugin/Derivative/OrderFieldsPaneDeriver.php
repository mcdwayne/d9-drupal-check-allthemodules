<?php

namespace Drupal\commerce_checkout_order_fields\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderFieldsPaneDeriver extends DeriverBase implements ContainerDeriverInterface {


  /**
   * The base plugin ID this derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a new OrderFieldsPaneDeriver object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct($base_plugin_id, EntityDisplayRepositoryInterface $entity_display_repository) {
    $this->basePluginId = $base_plugin_id;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $form_modes = $this->entityDisplayRepository->getFormModes('commerce_order');
    // Do not generate a derivative for default.
    unset($form_modes['default']);
    foreach ($form_modes as $form_mode_id => $form_mode_info) {
      $this->derivatives[$form_mode_id] = [
        'label' => sprintf('Order fields: %s', $form_mode_info['label']),
      ] + $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
