<?php

namespace Drupal\efs;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\efs\Entity\ExtraFieldInterface;
use Drupal\field_ui\Form\EntityDisplayFormBase;

/**
 * Defines an interface for Extra Field Display plugins.
 */
interface ExtraFieldFormatterPluginInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Builds a render array for the field.
   *
   * @param array $build
   *   The entity render array.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param \Drupal\Core\Entity\EntityDisplayBase $display
   *   The display entity object.
   * @param string $view_mode
   *   The view mode.
   * @param \Drupal\efs\Entity\ExtraFieldInterface $extra_field
   *   The extra field entity.
   *
   * @return array
   *   The render array.
   */
  public function view(array $build, EntityInterface $entity, EntityDisplayBase $display, string $view_mode, ExtraFieldInterface $extra_field);

  /**
   * Returns a form to configure settings for the formatter.
   *
   * Invoked in field_group_field_ui_display_form_alter to allow
   * administrators to configure the formatter.
   * The field_group module takes care of handling submitted
   * form values.
   *
   * @param \Drupal\field_ui\Form\EntityDisplayFormBase $view_display
   *   The display entity object.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param \Drupal\efs\Entity\ExtraFieldInterface $extra_field
   *   The extra field entity.
   * @param string $field
   *   The field name.
   *
   * @return array
   *   The form elements for the formatter settings.
   */
  public function settingsForm(EntityDisplayFormBase $view_display, array $form, FormStateInterface $form_state, ExtraFieldInterface $extra_field, string $field);

  /**
   * Returns a short summary for the current formatter settings.
   *
   * If an empty result is returned, a UI can still be provided to display
   * a settings form in case the formatter has configurable settings.
   *
   * @param string $context
   *   The context to get the default settings for.
   *
   * @return array
   *   A short summary of the formatter settings.
   */
  public function settingsSummary(string $context);

  /**
   * Defines the default settings for this plugin.
   *
   * @param string $context
   *   The context to get the default settings for.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public static function defaultContextSettings(string $context);

  /**
   * Determines if the plugin is compatible in this context.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   A bundle id.
   *
   * @return bool
   *   True if applicable or false if not applicable.
   */
  public function isApplicable(string $entity_type_id, string $bundle);

}
