<?php

namespace Drupal\arb_token\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Defines the arbitrary token config entity.
 *
 * @ConfigEntityType(
 *   id = "arb_token",
 *   label = @Translation("Arbitrary token"),
 *   handlers = {
 *     "list_builder" = "Drupal\arb_token\Entity\ArbitraryTokenListBuilder",
 *     "form" = {
 *       "add" = "Drupal\arb_token\Form\ArbitraryTokenForm",
 *       "edit" = "Drupal\arb_token\Form\ArbitraryTokenForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/tokens/manage/{arb_token}",
 *     "delete-form" = "/admin/config/system/tokens/manage/{arb_token}/delete",
 *     "collection" = "/admin/config/system/tokens",
 *   },
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_prefix = "arb_token",
 *   config_export = {
 *     "id",
 *     "label",
 *     "plugin",
 *     "configuration",
 *   },
 * )
 */
class ArbitraryToken extends ConfigEntityBase implements ConfigEntityInterface {

  /**
   * The name (plugin ID) of the action.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the action.
   *
   * @var string
   */
  protected $label;

  /**
   * The configuration of the action.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin collection that stores action plugins.
   *
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * Gets the token's plugin.
   *
   * @return \Drupal\arb_token\ArbitraryTokenPluginInterface
   *   The token's plugin.
   */
  public function getPlugin() {
    if ($this->plugin) {
      return $this->getPluginCollection()
        ->get($this->plugin)
        ->setToken($this);
    }
  }

  /**
   * Gets the plugin type.
   *
   * @return string
   *   The plugin type.
   */
  public function getType() {
    return $this->getPlugin()->getType();
  }

  /**
   * Provide information about the placeholder token.
   *
   * @see \hook_token_info()
   *
   * @return array
   *   An associative array declaring the token.
   */
  public function tokenInfo() {
    return $this->getPlugin()->tokenInfo();
  }

  /**
   * Provide replacement values for placeholder tokens.
   *
   * @see \hook_tokens()
   *
   * @return array
   *   An associative array of replacement values.
   */
  public function tokens($tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    /** @var \Drupal\arb_token\ArbitraryTokenPluginInterface $plugin */
    $plugin = $this->getPlugin();
    return $plugin->tokens($tokens, $data, $options, $bubbleable_metadata);
  }

  /**
   * Encapsulates the creation of the action's LazyPluginCollection.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection
   *   The action's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $this->pluginCollection = new DefaultSingleLazyPluginCollection(\Drupal::service('plugin.manager.arb_token'), $this->plugin, $this->configuration);
    }
    return $this->pluginCollection;
  }

}
