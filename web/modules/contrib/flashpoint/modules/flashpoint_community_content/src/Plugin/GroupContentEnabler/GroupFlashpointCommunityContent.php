<?php

namespace Drupal\flashpoint_community_content\Plugin\GroupContentEnabler;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\flashpoint_community_content\Entity\FlashpointCommunityContentType;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a content enabler for community content.
 *
 * @GroupContentEnabler(
 *   id = "group_flashpoint_community_content",
 *   label = @Translation("Group Community Content"),
 *   description = @Translation("Adds community content to groups both publicly and privately."),
 *   entity_type_id = "flashpoint_community_content",
 *   entity_access = TRUE,
 *   reference_label = @Translation("Title"),
 *   reference_description = @Translation("The title of the flashpoint_community_content to add to the group"),
 *   deriver = "Drupal\flashpoint_community_content\Plugin\GroupContentEnabler\GroupFlashpointCommunityContentDeriver"
 * )
 */
class GroupFlashpointCommunityContent extends GroupContentEnablerBase {

  /**
   * Retrieves the flashpoint_community_content type this plugin supports.
   *
   * @return \Drupal\flashpoint_community_content\Entity\FlashpointCommunityContentTypeInterface
   *   The flashpoint_community_content type this plugin supports.
   */
  protected function getFlashpointCommunityContentType() {
    return FlashpointCommunityContentType::load($this->getEntityBundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $plugin_id = $this->getPluginId();
    $type = $this->getEntityBundle();
    $operations = [];

    if ($group->hasPermission("create $plugin_id entity", $account)) {
      $route_params = ['group' => $group->id(), 'plugin_id' => $plugin_id];
      $operations["group-flashpoint_community_content-create-$type"] = [
        'title' => $this->t('Create @type', ['@type' => $this->getFlashpointCommunityContentType()->label()]),
        'url' => new Url('entity.group_content.create_form', $route_params),
        'weight' => 30,
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetEntityPermissions() {
    $permissions = parent::getTargetEntityPermissions();
    $plugin_id = $this->getPluginId();

    // Add a 'view unpublished' permission by re-using most of the 'view' one.
    $original = $permissions["view $plugin_id entity"];
    $permissions["view unpublished $plugin_id entity"] = [
      'title' => str_replace('View ', 'View unpublished ', $original['title']),
    ] + $original;

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other content enabler plugins.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $dependencies['config'][] = 'flashpoint_community_content.type.' . $this->getEntityBundle();
    return $dependencies;
  }

}
