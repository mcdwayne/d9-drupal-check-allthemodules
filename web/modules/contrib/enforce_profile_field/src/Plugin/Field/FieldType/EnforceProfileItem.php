<?php

namespace Drupal\enforce_profile_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\options\Plugin\Field\FieldType\ListItemBase;

/**
 * Defines the 'enforce_profile_field' entity field type.
 *
 * @FieldType(
 *   id = "enforce_profile_field",
 *   label = @Translation("Enforce profile"),
 *   description = @Translation("An entity field enforcing additional profile data."),
 *   category = @Translation("User"),
 *   default_widget = "options_select",
 *   default_formatter = "",
 * )
 */
class EnforceProfileItem extends ListItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'allowed_values_function' => 'enforce_profile_field_allowed_values',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      // Form mode used as a destination for the redirect.
      'form_mode' => '',
      // View modes that should invoke enforce profile field logic.
      'active_view_modes' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Machine name value'))
      ->addConstraint('Length', ['max' => FieldStorageConfig::NAME_MAX_LENGTH])
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => FieldStorageConfig::NAME_MAX_LENGTH,
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function allowedValuesDescription() {
    // Allowed values are provided programatically, so there is no need
    // to provide any description for a user.
    $description = '';

    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $form_mode_options = $this->getFormModes();

    $element['form_mode'] = [
      '#type' => 'select',
      '#title' => $this->t("User's form mode"),
      '#description' => $this->t('Select a user form mode to be utilized for additional field information extraction.'),
      '#options' => $form_mode_options,
      '#default_value' => $this->getSetting('form_mode'),
      '#required' => TRUE,
    ];

    $view_modes_options = $this->getViewModes();

    $element['active_view_modes'] = [
      '#type' => 'select',
      '#title' => $this->t("Enforced view modes"),
      '#description' => $this->t("Select view modes that require selected user's field to be filled in before allowing access to view them."),
      '#options' => $view_modes_options,
      '#default_value' => $this->getSetting('active_view_modes'),
      '#multiple' => TRUE,
      '#required' => FALSE,
    ];

    return $element;
  }

  /**
   * Get form modes by id.
   *
   * @param string $entity_type_id
   *   An entity type id.
   * @param string $bundle
   *   A bundle machine name.
   *
   * @return array
   *   An array of entity type's from modes by id.
   */
  private function getFormModes($entity_type_id = 'user', $bundle = 'user') {
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    // Get form modes of the user entity type.
    $options = $entity_display_repository->getFormModeOptionsByBundle($entity_type_id, $bundle);

    // Init options variable.
    $modes_by_id = [];

    // Extract key/value pairs.
    foreach ($options as $key => $label) {
      // Add in only valid options.
      if (is_string($key) && is_string($label)) {
        $modes_by_id[$key] = $label;
      }
    }

    return $modes_by_id;
  }

  /**
   * Get view modes by id.
   *
   * @return array
   *   An array of entity type's view modes by id.
   */
  private function getViewModes() {
    $entity_type_id = $this->getEntity()->getEntityTypeId();
    $entity_bundle = $this->getEntity()->bundle();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
    $entity_display_repository = \Drupal::service('entity_display.repository');
    // Get active view modes.
    $options = $entity_display_repository->getViewModeOptionsByBundle($entity_type_id, $entity_bundle);

    // Init options variable.
    $modes_by_id = [];

    // Extract key/value pairs.
    foreach ($options as $key => $label) {
      // Add in only valid options.
      if (is_string($key) && is_string($label)) {
        $modes_by_id[$key] = $label;
      }
    }

    return $modes_by_id;
  }

}
