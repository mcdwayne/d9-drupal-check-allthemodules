<?php

namespace Drupal\discussions\Plugin\GroupContentEnabler;

use Drupal\discussions\Entity\DiscussionType;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a content enabler for Discussions.
 *
 * @GroupContentEnabler(
 *   id = "group_discussion",
 *   label = @Translation("Group Discussion"),
 *   description = @Translation("Adds discussions to groups."),
 *   entity_type_id = "discussion",
 *   deriver = "Drupal\discussions\Plugin\GroupContentEnabler\GroupDiscussionDeriver"
 * )
 */
class GroupDiscussion extends GroupContentEnablerBase {

  /**
   * Retrieves the Discussion Type this plugin supports.
   *
   * @return \Drupal\discussions\DiscussionTypeInterface
   *   The Discussion Type this plugin supports.
   */
  protected function getDiscussionType() {
    return DiscussionType::load($this->getEntityBundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $account = \Drupal::currentUser();
    $type = $this->getEntityBundle();
    $operations = [];

    if ($group->hasPermission("create $type discussion", $account)) {
      $route_params = ['group' => $group->id(), 'discussion_type' => $this->getEntityBundle()];
      $operations["discussion-create-$type"] = [
        'title' => $this->t('Create Discussion'),
        'url' => new Url('entity.group_content.group_discussion_add_form', $route_params),
        'weight' => 30,
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    $permissions = [];

    // Unset unwanted permissions defined by the base plugin.
    $plugin_id = $this->getPluginId();
    unset($permissions["access $plugin_id overview"]);

    // Add permissions for managing discussions.
    $type = $this->getEntityBundle();
    $type_arg = ['%discussion_type' => $this->getDiscussionType()->label()];
    $defaults = [
      'title_args' => $type_arg,
      'description' => 'Only applies to %discussion_type discussions that belong to this group.',
      'description_args' => $type_arg,
    ];

    $permissions["view $type discussion"] = [
      'title' => '%discussion_type: View discussion',
    ] + $defaults;

    $permissions["create $type discussion"] = [
      'title' => '%discussion_type: Create new discussion',
      'description' => 'Allows you to create %discussion_type discussions that immediately belong to this group.',
      'description_args' => $type_arg,
    ] + $defaults;

    $permissions["edit own $type discussion"] = [
      'title' => '%discussion_type: Edit own discussion',
    ] + $defaults;

    $permissions["edit any $type discussion"] = [
      'title' => '%discussion_type: Edit any discussion',
    ] + $defaults;

    $permissions["delete own $type discussion"] = [
      'title' => '%discussion_type: Delete own discussion',
    ] + $defaults;

    $permissions["delete any $type discussion"] = [
      'title' => '%discussion_type: Delete any discussion',
    ] + $defaults;

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;

    // This string will be saved as part of the group type config entity. We do
    // not use a t() function here as it needs to be stored untranslated.
    $config['info_text']['value'] = '<p>By submitting this form you will add this content to the group.<br />It will then be subject to the access control settings that were configured for the group.<br/>Please fill out any available fields to describe the relation between the content and the group.</p>';
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

}
