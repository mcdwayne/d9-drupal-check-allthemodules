<?php

namespace Drupal\entity_reference_text\Plugin\Field\FieldType;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;

/**
 * Defines the 'entity_reference_text' entity field type.
 *
 * Supported settings (below the definition's 'settings' key) are:
 * - target_type: The entity type to reference. Required.
 *
 * @FieldType(
 *   id = "entity_references_text",
 *   label = @Translation("Entity references with text"),
 *   description = @Translation(""),
 *   category = @Translation("Reference"),
 *   default_widget = "entity_reference_text_autocompletion",
 *   default_formatter = "entity_reference_text",
 * )
 */
class EntityReferenceWithText extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    $constraint_manager = $this->getTypedDataManager()->getValidationConstraintManager();
    $constraints[] = $constraint_manager->create('EntityReferenceTextValidReference', []);
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
        ],
        'entity_ids' => [
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type' => \Drupal::moduleHandler()->moduleExists('node') ? 'node' : 'user',
      'target_bundle' => '',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Text'))
      ->setDescription(t('The text including the ERs'))
    ;

    $settings = $field_definition->getSettings();
    $target_type_info = \Drupal::entityTypeManager()
      ->getDefinition($settings['target_type']);

    $target_id_data_type = 'string';
    if ($target_type_info->isSubclassOf(FieldableEntityInterface::class)) {
      $id_definition = \Drupal::entityManager()
        ->getBaseFieldDefinitions($settings['target_type'])[$target_type_info->getKey('id')];
      if ($id_definition->getType() === 'integer') {
        $target_id_data_type = 'integer';
      }
    }

    if ($target_id_data_type === 'integer') {
      $target_id_definition = ListDataDefinition::createFromDataType('entity_reference')
        ->setLabel($target_type_info->getPluralLabel())
        ->setSetting('unsigned', TRUE);
    }
    else {
      $target_id_definition = ListDataDefinition::createFromDataType('entity_reference')
        ->setLabel($target_type_info->getPluralLabel());
    }

    $properties['entity_ids'] = $target_id_definition
      ->setDescription('');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element['target_type'] = [
      '#type' => 'select',
      '#title' => t('Type of item to reference'),
      '#options' => \Drupal::entityManager()->getEntityTypeLabels(TRUE),
      '#default_value' => $this->getSetting('target_type'),
      '#required' => TRUE,
      '#disabled' => $has_data,
      '#size' => 1,
    ];

    if (!empty($this->getSetting('target_type'))) {
      $bundles = \Drupal::entityManager()->getBundleInfo($this->getSetting('target_type'));
      $bundle_labels = array_map(function ($info) {
        return $info['label'];
      }, $bundles);
      $element['bundle'] = [
        '#type' => 'select',
        '#title' => t('Bundle'),
        '#options' => $bundle_labels,
        '#default_value' => $this->getSetting('target_bundle'),
        '#required' => TRUE,
        '#disabled' => $has_data,
        '#size' => 1,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * Copied from \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::fieldSettingsForm().
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = $form_state->getFormObject()->getEntity();

    // Get all selection plugins for this entity type.
    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager */
    $selection_manager = \Drupal::service('plugin.manager.entity_reference_selection');
    $selection_plugins = $selection_manager->getSelectionGroups($this->getSetting('target_type'));
    $handlers_options = [];
    foreach (array_keys($selection_plugins) as $selection_group_id) {
      // We only display base plugins (e.g. 'default', 'views', ...) and not
      // entity type specific plugins (e.g. 'default:node', 'default:user',
      // ...).
      if (array_key_exists($selection_group_id, $selection_plugins[$selection_group_id])) {
        $handlers_options[$selection_group_id] = Html::escape($selection_plugins[$selection_group_id][$selection_group_id]['label']);
      }
      elseif (array_key_exists($selection_group_id . ':' . $this->getSetting('target_type'), $selection_plugins[$selection_group_id])) {
        $selection_group_plugin = $selection_group_id . ':' . $this->getSetting('target_type');
        $handlers_options[$selection_group_plugin] = Html::escape($selection_plugins[$selection_group_id][$selection_group_plugin]['base_plugin_label']);
      }
    }

    $form = [
      '#type' => 'container',
      // '#process' => [[get_class($this), 'fieldSettingsAjaxProcess']],
      '#element_validate' => [[get_class($this), 'fieldSettingsFormValidate']],
    ];

    $form['handler'] = [
      '#type' => 'details',
      '#title' => t('Reference type'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#process' => [[get_class($this), 'formProcessMergeParent']],
    ];

    $form['handler']['handler'] = [
      '#type' => 'select',
      '#title' => t('Reference method'),
      '#options' => $handlers_options,
      '#default_value' => $field->getSetting('handler'),
      '#required' => TRUE,
      // '#ajax' => TRUE,
      // '#limit_validation_errors' => [],
    ];
    $form['handler']['handler_submit'] = [
      '#type' => 'submit',
      '#value' => t('Change handler'),
      '#limit_validation_errors' => [],
      '#attributes' => [
        'class' => ['js-hide'],
      ],
      // '#submit' => [[get_class($this), 'settingsAjaxSubmit']],
    ];

    $form['handler']['handler_settings'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity_reference-settings']],
    ];

    $handler = $selection_manager->getSelectionHandler($field);
    $form['handler']['handler_settings'] += $handler->buildConfigurationForm([], $form_state);

    return $form;
  }

  /**
   * Render API callback: Moves entity_reference specific Form API elements
   * (i.e. 'handler_settings') up a level for easier processing by the
   * validation and submission handlers.
   *
   * @see _entity_reference_field_settings_process()
   */
  public static function formProcessMergeParent($element) {
    $parents = $element['#parents'];
    array_pop($parents);
    $element['#parents'] = $parents;
    return $element;
  }

  /**
   * Form element validation handler; Invokes selection plugin's validation.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   */
  public static function fieldSettingsFormValidate(array $form, FormStateInterface $form_state) {
    $field = $form_state->getFormObject()->getEntity();
    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager */
    $selection_manager = \Drupal::service('plugin.manager.entity_reference_selection');
    $handler = $selection_manager->getSelectionHandler($field);
    $handler->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'handler' => 'default',
      'handler_settings' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $this->entity_ids = $this->extractEntityReferences($this->value);
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    if ($property_name === 'value') {
      $this->writePropertyValue('entity_ids', $this->extractEntityReferences($this->value));
    }
    parent::onChange($property_name, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values) && !is_array($values)) {
      $this->set('value', $values, $notify);
    }
    else {
      parent::setValue($values, $notify);
      if (is_array($values) && isset($values['value'])) {
        $this->onChange('value', FALSE);
      }
      elseif (is_array($values) && array_key_exists('entity_ids', $values)) {
        $this->onChange('entity_ids', FALSE);
      }
    }
  }


  /**
   * Extract entity IDs out of the given user input.
   *
   * @param string $value
   *   The user input
   *
   * @return mixed[]
   *   A list of entity IDs.
   */
  protected function extractEntityReferences($value) {
    $entity_ids = [];
    if (preg_match_all("/\s\((\d+)\)/", $value, $matches, PREG_PATTERN_ORDER)) {
      foreach ($matches[1] as $match) {
        $entity_ids[] = $match;
      }
    }
    elseif (preg_match_all("/\s\(([\w.]+)\)/", $value, $matches, PREG_PATTERN_ORDER)) {
      foreach ($matches[1] as $match) {
        $entity_ids[] = $match;
      }
    }
    return $entity_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->value);
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();

    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $manager */
    $manager = \Drupal::service('plugin.manager.entity_reference_selection');
    if ($referenceable = $manager->getSelectionHandler($field_definition)->getReferenceableEntities()) {
      $group = array_rand($referenceable);
      $values['entity_ids'] = array_rand($referenceable[$group], 3);
      $values['value'] = implode($random->word(mt_rand(1, 3)), array_map(function ($entity_id) {
        return '(' . $entity_id . ')';
      }, $values['entity_ids']));
      return $values;
    }
  }

}
