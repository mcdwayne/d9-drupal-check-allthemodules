<?php

namespace Drupal\cloud\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CloudConfigCloudContextBundleDeriver.
 *
 * Responsible for defining plugin derivatives based on cloud_context.
 *
 * @package Drupal\cloud\Plugin\Derivative
 */
class CloudConfigCloudContextBundleDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a CloudConfigContextBundleDeriver instance.
   *
   * @param string $base_plugin_id
   *   The base plugin id.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Keep a copy of the original and flag it as the original.
    $this->derivatives[''] = ['base_plugin' => ''] + $base_plugin_definition;
    $cloud_config_entities = $this->entityTypeManager->getStorage('cloud_config')->loadByProperties(
      ['type' => [$base_plugin_definition['entity_bundle']]]
    );
    foreach ($cloud_config_entities as $cloud_config) {
      $this->derivatives[$cloud_config->id()] = $base_plugin_definition;
      $this->derivatives[$cloud_config->id()]['cloud_context'] = $cloud_config->getCloudContext();
    }
    $this->derivatives;

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
