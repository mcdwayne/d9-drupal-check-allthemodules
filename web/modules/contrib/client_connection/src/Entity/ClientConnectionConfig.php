<?php

namespace Drupal\client_connection\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Defines the Client Connection Configuration entity.
 *
 * @ConfigEntityType(
 *   id = "client_connection_config",
 *   label = @Translation("Client Connection Configuration"),
 *   label_singular = @Translation("Client Connection Configuration"),
 *   label_plural = @Translation("Client Connection Configurations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count client connection configuration",
 *     plural = "@count client connection configurations"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\client_connection\Entity\Storage\ClientConnectionConfigStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\client_connection\Entity\ListBuilder\ClientConnectionConfigListBuilder",
 *     "form" = {
 *       "specific" = "Drupal\client_connection\Form\SpecificClientConnectionConfigForm",
 *       "add" = "Drupal\client_connection\Form\ClientConnectionConfigForm",
 *       "edit" = "Drupal\client_connection\Form\ClientConnectionConfigForm",
 *       "delete" = "Drupal\client_connection\Form\ClientConnectionConfigDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\client_connection\Entity\Routing\ClientConnectionConfigHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "client_connection_config",
 *   admin_permission = "administer client connection configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/config/client_connection/manage",
 *
 *     "canonical" = "/admin/config/client_connection/manage/{client_connection_config}",
 *     "edit-form" = "/admin/config/client_connection/manage/{client_connection_config}/edit",
 *     "delete-form" = "/admin/config/client_connection/manage/{client_connection_config}/delete"
 *   },
 *   config_export = {
 *      "id",
 *     "label",
 *     "channels",
 *     "configuration",
 *     "pluginId",
 *     "instanceId"
 *   },
 *   lookup_keys = {
 *     "id",
 *     "channels",
 *     "pluginId",
 *     "instanceId"
 *   }
 * )
 */
class ClientConnectionConfig extends ConfigEntityBase implements ClientConnectionConfigInterface {

  /**
   * The Client Connection Configuration ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Client Connection Configuration label.
   *
   * @var string
   */
  protected $label;

  /**
   * Channel IDs used to specify when this connection should be used.
   *
   * @var string[]
   */
  protected $channels = [];

  /**
   * The configuration of the client connection.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The plugin collection that stores connection plugins.
   *
   * @var \Drupal\Component\Plugin\LazyPluginCollection
   */
  protected $pluginCollection;

  /**
   * The plugin ID of the connection.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The instance ID of the connection.
   *
   * @var string
   */
  protected $instanceId;

  /**
   * Encapsulates the creation of the LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The plugin collection.
   */
  protected function getPluginCollection() {
    if (is_null($this->pluginCollection)) {
      $this->pluginCollection = new DefaultSingleLazyPluginCollection(
        \Drupal::service('plugin.manager.client_connection'),
        $this->pluginId,
        $this->configuration
      );
    }
    return $this->pluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getChannels() {
    return $this->channels;
  }

  /**
   * {@inheritdoc}
   */
  public function addChannel($channel) {
    if (!in_array($channel, $this->channels)) {
      $this->channels[] = $channel;
      asort($this->channels);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeChannel($channel) {
    $key = array_search($channel, $this->channels);
    if (!is_bool($key)) {
      unset($this->channels[$key]);
      asort($this->channels);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['configuration' => $this->getPluginCollection()];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return $this->getPlugin()->getPluginDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    /** @var \Drupal\client_connection\Plugin\ClientConnection\ClientConnectionInterface $plugin */
    $plugin = $this->getPluginCollection()->get($this->pluginId);
    $plugin->setContext($this->entityTypeId, new Context(new ContextDefinition('entity:' . $this->entityTypeId), $this));
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    return $this->set('pluginId', $plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function setInstanceId($instance_id) {
    return $this->set('instanceId', $instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceId() {
    return $this->instanceId;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return parent::label()?:$this->getPlugin()->label();
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    // Invoke the setters to clear related properties.
    if ($property_name == 'pluginId') {
      $this->pluginId = $value;
      $this->getPluginCollection()->addInstanceId($value, NULL);
    }

    return parent::set($property_name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    $this->pluginCollection = NULL;
    return parent::__sleep();
  }

}
