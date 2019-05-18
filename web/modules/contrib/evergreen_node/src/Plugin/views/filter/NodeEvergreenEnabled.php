<?php

namespace Drupal\evergreen_node\Plugin\views\filter;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\views\Plugin\views\filter\Bundle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\evergreen\EvergreenServiceInterface;

/**
 * Filter class which allows filtering by entity bundles.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("node_evergreen_enabled")
 */
class NodeEvergreenEnabled extends Bundle {

  /**
   * Evergreen service.
   *
   * @var Drupal\evergreen\EvergreenService
   */
  protected $evergreen;

  /**
   * Constructs an NodeEvergreenEnabled object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $bundle_info_service, EvergreenServiceInterface $evergreen) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $bundle_info_service);

    $this->entityManager = $entity_manager;
    $this->bundleInfoService = $bundle_info_service;
    $this->evergreen = $evergreen;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('evergreen')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    // the Bundle filter uses this to load the options on the option form for
    // this filter. By overriding it, we can specify that we only want
    // configured node bundles. This will then get used in the ::query()
    // method later.
    if (!isset($this->valueOptions)) {
      $configured_types = $this->evergreen->getConfiguredEntityTypes($this->entityTypeId);
      $configured_bundles = array_map(function ($type) {
        return $type['bundle'];
      }, $configured_types);

      $types = $this->bundleInfoService->getBundleInfo($this->entityTypeId);
      $this->valueTitle = $this->t('@entity types', ['@entity' => $this->entityType->getLabel()]);

      $options = [];
      foreach ($types as $type => $info) {
        if (in_array($type, $configured_bundles)) {
          $options[$type] = $info['label'];
        }
      }

      asort($options);
      $this->valueOptions = $options;
    }

    return $this->valueOptions;
  }

}
