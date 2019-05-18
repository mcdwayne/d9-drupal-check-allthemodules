<?php

namespace Drupal\hp\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Defines the FormStrategy entity for Human Presence.
 *
 * The 'rendered' tag for the List cache is necessary since CAPTCHAs have to be
 * re-rendered once they are modified. Invalidating the render cache ensures
 * we always display the correct CAPTCHA for every form.
 *
 * @ConfigEntityType(
 *   id = "hp_form_strategy",
 *   label = @Translation("Protected form"),
 *   label_collection = @Translation("Protected forms"),
 *   label_singular = @Translation("Protected form"),
 *   label_plural = @Translation("Protected forms"),
 *   label_count = @PluralTranslation(
 *     singular = "@count protected form",
 *     plural = "@count protected forms",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\hp\FormStrategyListBuilder",
 *     "form" = {
 *       "add" = "Drupal\hp\Form\FormStrategyForm",
 *       "edit" = "Drupal\hp\Form\FormStrategyForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "hp_form_strategy",
 *   admin_permission = "administer human presence",
 *   list_cache_tags = {
 *    "rendered"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "plugin",
 *     "configuration",
 *     "regexp",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/development/human-presence/protected-forms/add",
 *     "edit-form" = "/admin/config/development/human-presence/protected-forms/{hp_form_strategy}",
 *     "delete-form" = "/admin/config/development/human-presence/protected-forms/{hp_form_strategy}/delete",
 *     "collection" =  "/admin/config/development/human-presence/protected-forms"
 *   }
 * )
 */
class FormStrategy extends ConfigEntityBase implements FormStrategyInterface {

  /**
   * The entity ID which is the same as the form ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin collection that holds the form strategy plugin.
   *
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
   */
  protected $pluginCollection;

  /**
   * The plugin configuration.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The form ID regexp to use instead of $id.
   *
   * @var string
   */
  protected $regexp;

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    $instance_id = $this->getPluginId();
    if (!$this->pluginCollection) {
      $configuration = $this->getPluginConfiguration();
      $manager = \Drupal::service('plugin.manager.hp_form_strategy');
      $this->pluginCollection = new DefaultSingleLazyPluginCollection($manager, $instance_id, $configuration);
    }
    /** @var FormStrategyInterface $hp_form_strategy_plugin */
    return $this->pluginCollection->get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->plugin = $plugin_id;
    $this->configuration = [];
    $this->pluginCollection = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginConfiguration(array $configuration) {
    $this->configuration = $configuration;
    $this->pluginCollection = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegexp() {
    return $this->regexp;
  }

  /**
   * {@inheritdoc}
   */
  public function setRegexp($regexp) {
    $this->regexp = $regexp;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['config:hp.hp_form_strategy']);
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    parent::save();
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['config:hp.hp_form_strategy']);
  }

}
