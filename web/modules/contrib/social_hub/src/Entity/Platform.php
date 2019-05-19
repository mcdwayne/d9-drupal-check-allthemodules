<?php

namespace Drupal\social_hub\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\social_hub\PlatformInterface;

/**
 * Defines the platform entity type.
 *
 * @ConfigEntityType(
 *   id = "platform",
 *   label = @Translation("Platform"),
 *   label_collection = @Translation("Platforms"),
 *   label_singular = @Translation("platform"),
 *   label_plural = @Translation("platforms"),
 *   label_count = @PluralTranslation(
 *     singular = "@count platform",
 *     plural = "@count platforms",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\social_hub\PlatformListBuilder",
 *     "form" = {
 *       "add" = "Drupal\social_hub\Form\PlatformForm",
 *       "edit" = "Drupal\social_hub\Form\PlatformForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "platform",
 *   admin_permission = "administer social_hub configuration",
 *   links = {
 *     "collection" = "/admin/config/services/social-hub/platform",
 *     "add-form" = "/admin/config/services/social-hub/platform/add",
 *     "edit-form" = "/admin/config/services/social-hub/platform/{platform}",
 *     "delete-form" =
 *   "/admin/config/services/social-hub/platform/{platform}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "plugins",
 *     "configuration",
 *     "status"
 *   }
 * )
 */
class Platform extends ConfigEntityBase implements PlatformInterface {

  /**
   * The platform ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The platform label.
   *
   * @var string
   */
  protected $label;

  /**
   * The integration plugins.
   *
   * @var array
   */
  protected $plugins;

  /**
   * The configuration for the integration plugins.
   *
   * @var array
   */
  protected $configuration;

  /**
   * The pluginCollection property.
   *
   * @var \Drupal\Core\Plugin\DefaultLazyPluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugins() {
    return array_filter(array_values($this->plugins ?? []));
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugins(array $plugins) {
    $this->plugins = $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return (bool) $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->configuration,
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollection() {
    $plugin_manager = \Drupal::service('plugin.manager.social_hub.platform');
    $this->pluginCollection = new DefaultLazyPluginCollection($plugin_manager, $this->getConfiguration());

    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'configuration' => $this->getPluginCollection(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $plugins = [], array $context = []) {
    $build = [
      '#theme' => 'platform',
      '#entity' => $this,
      '#content' => [],
    ];

    $context += [
      'platform' => $this,
    ];

    /** @var \Drupal\social_hub\PlatformIntegrationPluginInterface $plugin */
    foreach ($this->getPluginCollection() as $plugin) {
      if (!empty($plugins) && !in_array($plugin->getPluginId(), $plugins, TRUE)) {
        continue;
      }

      $build['#content'][] = $plugin->build($context);
    }

    BubbleableMetadata::createFromRenderArray($build)
      ->merge(BubbleableMetadata::createFromObject($this))
      ->applyTo($build);

    return $build;
  }

}
