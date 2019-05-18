<?php

namespace Drupal\evergreen_node\Plugin\evergreen\Evergreen;

use Drupal\evergreen\EvergreenBase;

/**
 * Define the node integration for the evergreen module.
 *
 * @Evergreen(
 *   id = "node",
 *   label = @Translation("Content"),
 *   description = @Translation("Integrate nodes with the Evergreen module")
 * )
 */
class Node extends EvergreenBase {

  /**
   * {@inheritDoc}
   */
  public function getBundleOptions() {
    $entity_type = 'node';
    $bundle_options = [];
    $bundles = entity_get_bundles($entity_type);
    if ($bundles) {
      foreach ($bundles as $bundle => $bundle_details) {
        $bundle_options[$entity_type . '.' . $bundle] = $this->t('%bundle', ['%bundle' => $bundle_details['label']]);
      }
    }
    return $bundle_options;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'data' => $this->configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += [
      'data' => [],
    ];
    $this->configuration = $configuration['data'] + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function alterViewsData(array &$data) {
    if (!isset($data['evergreen_content']['table']['join'])) {
      $data['evergreen_content']['table']['join'] = [];
    }
    $data['evergreen_content']['table']['join']['node'] = [
      'table' => 'evergreen_content',
      'left_field' => 'nid',
      'field' => 'entity',
      'extra' => [
        [
          'field' => 'evergreen_entity_type',
          'value' => 'node',
        ],
      ],
    ];

    $data['node']['evergreen_content'] = [
      'title' => t('Evergreen content'),
      'help' => t('Show if the node has an associated evergreen content entity'),
      'field' => [
        'id' => 'node_evergreen_content',
      ],
    ];

    $data['node']['evergreen_expired'] = [
      'title' => t('Expiration status'),
      'help' => t('Show if the node has expired'),
      'field' => [
        'id' => 'node_evergreen_expired',
      ],
    ];

    $data['node']['is_evergreen'] = [
      'title' => t('Is Evergreen'),
      'help' => t('Show if the node is evergreen (does not expire)'),
      'field' => [
        'id' => 'node_is_evergreen',
      ],
    ];

    $data['node']['evergreen_expiration'] = [
      'title' => t('Expiration date'),
      'help' => t("Show if the node's expiration date"),
      'field' => [
        'id' => 'node_evergreen_expiration',
      ],
    ];

    // filters
    $data['node']['node_evergreen_enabled'] = [
      'title' => t('Evergreen enabled nodes'),
      'filter' => [
        'title' => t('Evergreen enabled nodes'),
        'help' => t('Filter to include only nodes enabled with the Evergreen module'),
        'field' => 'type',
        'id' => 'node_evergreen_enabled',
      ],
    ];

    $data['node']['node_is_evergreen'] = [
      'title' => t('Content is evergreen'),
      'filter' => [
        'title' => t('Evergreen nodes'),
        'help' => t('Filter to include (or exclude) nodes that do not expire'),
        'field' => 'type',
        'id' => 'node_is_evergreen',
      ],
    ];

    $data['node']['node_expired'] = [
      'title' => t('Content is expired'),
      'filter' => [
        'title' => t('Expired nodes'),
        'help' => t('Filter to include (or exclude) nodes that have expired'),
        'field' => 'type',
        'id' => 'node_expired',
      ],
    ];

  }

}
