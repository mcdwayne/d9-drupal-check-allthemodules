<?php

namespace Drupal\flashpoint_course_content\Plugin\GroupContentEnabler;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\flashpoint_course_content\Entity\FlashpointCourseContentType;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a content enabler for course content.
 *
 * @GroupContentEnabler(
 *   id = "group_flashpoint_course_content",
 *   label = @Translation("Group Course Content"),
 *   description = @Translation("Adds course content to groups both publicly and privately."),
 *   entity_type_id = "flashpoint_course_content",
 *   entity_access = TRUE,
 *   reference_label = @Translation("Title"),
 *   reference_description = @Translation("The title of the flashpoint_course_content to add to the group"),
 *   deriver = "Drupal\flashpoint_course_content\Plugin\GroupContentEnabler\GroupFlashpointCourseContentDeriver"
 * )
 */
class GroupFlashpointCourseContent extends GroupContentEnablerBase {

  /**
   * Retrieves the flashpoint_course_content type this plugin supports.
   *
   * @return \Drupal\flashpoint_course_content\Entity\FlashpointCourseContentTypeInterface
   *   The flashpoint_course_content type this plugin supports.
   */
  protected function getFlashpointCourseContentType() {
    return FlashpointCourseContentType::load($this->getEntityBundle());
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
      $operations["group-flashpoint_course_content-create-$type"] = [
        'title' => $this->t('Create @type', ['@type' => $this->getFlashpointCourseContentType()->label()]),
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
    $dependencies['config'][] = 'flashpoint_course_content.type.' . $this->getEntityBundle();
    return $dependencies;
  }

}
