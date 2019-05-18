<?php
/**
 * @file
 * Contains \Drupal\powertagging\Plugin\Field\FieldType\PowerTaggingTagsItem
 */

namespace Drupal\powertagging\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\URL;
use Drupal\Core\Validation\Plugin\Validation\Constraint\AllowedValuesConstraint;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\powertagging\Form\PowerTaggingConfigForm;
use Drupal\powertagging\PowerTagging;
use Drupal\semantic_connector\SemanticConnector;

/**
 * Plugin implementation of the 'powertagging_tags' field type.
 *
 * @FieldType(
 *   id = "powertagging_tags",
 *   label = @Translation("PowerTagging Tags"),
 *   description = @Translation("An entity field containing a taxonomy term
 *   reference."), category = @Translation("Reference"), default_widget =
 *   "powertagging_tags_default", default_formatter = "powertagging_tags_list",
 * )
 */
class PowerTaggingTagsItem extends FieldItemBase {

  /**
   * Returns the list of supported field types for the extraction mechanism.
   *
   * @param bool $is_sub_entity
   *   TRUE if the entity already is a referenced entity, FALSE if not.
   *
   * @return array
   *   The list of supported field types
   */
  public static function getSupportedFieldTypes($is_sub_entity = FALSE) {
    $allowed_widgets = [
      'core' => [
        'string' => ['string_textfield'],
        'string_long' => ['string_textarea'],
      ],
      'text' => [
        'text' => ['text_textfield'],
        'text_long' => ['text_textarea'],
        'text_with_summary' => ['text_textarea_with_summary'],
      ],
      'file' => [
        'file' => ['file_generic'],
      ],
    ];

    if (!$is_sub_entity) {
      $allowed_widgets['core']['entity_reference'] = ['entity_reference_autocomplete', 'entity_reference_autocomplete_tags'];
      $allowed_widgets = array_merge($allowed_widgets, [
        'file' => [
          'file' => ['file_generic'],
        ],
      ]);
    }

    return $allowed_widgets;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'target_id' => [
          'description' => 'The ID of the target taxonomy term.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'score' => [
          'description' => 'The score of the taxonomy term for this entity.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $target_type_info = \Drupal::entityTypeManager()
      ->getDefinition('taxonomy_term');

    $properties['target_id'] = DataDefinition::create('integer')
      ->setLabel(t('@label ID', ['@label' => $target_type_info->getLabel()]))
      ->setRequired(TRUE);
    $properties['score'] = DataDefinition::create('integer')
      ->setLabel(t('Score'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    // Remove the 'AllowedValuesConstraint' validation constraint because entity
    // reference fields already use the 'ValidReference' constraint.
    foreach ($constraints as $key => $constraint) {
      if ($constraint instanceof AllowedValuesConstraint) {
        unset($constraints[$key]);
      }
    }
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->get('target_id'));
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
        'powertagging_id' => NULL,
      ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $options = [];
    $description = '';

    $powertagging_configs = PowerTaggingConfig::loadMultiple();
    /** @var PowerTaggingConfig $powertagging_config */
    if (!is_null($powertagging_configs)) {
      foreach ($powertagging_configs as $powertagging_config) {
        $options[$powertagging_config->id()] = $powertagging_config->getTitle();
      }
    }
    else {
      $url = URL::fromRoute('entity.powertagging.collection');
      $description = t('No PowerTagging configuration found.') . '<br />';
      $description .= t('Please create it first in the <a href="@url">PowerTagging configuration</a> area.', ['@url' => $url->toString()]);
      drupal_set_message(t('No PowerTagging configuration found for the selection below.'), 'error');
    }

    $element['powertagging_id'] = [
      '#type' => 'select',
      '#title' => t('Select the PowerTagging configuration'),
      '#description' => $description,
      '#options' => $options,
      '#default_value' => $this->getSetting('powertagging_id'),
      '#required' => TRUE,
      '#disabled' => $has_data,
      '#size' => 1,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $max_file_size = file_upload_max_size();

    return [
        'include_in_tag_glossary' => FALSE,
        'automatically_tag_new_entities' => FALSE,
        'custom_freeterms' => TRUE,
        'use_shadow_concepts' => FALSE,
        'browse_concepts_charttypes' => [],
        'fields' => [],
        'default_tags_field' => '',
        'limits' => [],
        'file_upload' => array(
          'max_file_size' => ($max_file_size > (2 * 1048576)) ? (2 * 1048576) : $max_file_size,
          'max_file_count' => 5
        ),
        'ac_add_matching_label' => FALSE,
        'ac_add_context' => FALSE,
      ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    /** @var FieldConfig $field */
    $field = $form_state->getFormObject()->getEntity();
    $form = [];

    // Check if the entity type has taxonomy term references.
    $field_definitions = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions($field->getTargetEntityTypeId(), $field->getTargetBundle());
    $term_references = ['' => '- None -'];
    foreach ($field_definitions as $field_definition) {
      if ($field_definition instanceof FieldConfig && $field_definition->getType() == 'entity_reference') {
        $handler = $field_definition->getSetting('handler');
        if (!is_null($handler) && strpos($handler, 'taxonomy_term') !== FALSE) {
          $term_references[$field_definition->getName()] = $field_definition->label();
        }
      }
    }

    // Show the fields with taxonomy term references if available.
    if (count($term_references) > 1) {
      $form['default_tags_field'] = [
        '#type' => 'radios',
        '#title' => t('Term reference fields that can be used for default values'),
        '#description' => t('Select the field from which the linked terms are used as default values.'),
        '#options' => $term_references,
        '#default_value' => $field->getSetting('default_tags_field'),
      ];
    }

    $form['special'] = array(
      '#type' => 'fieldset',
      '#title' => t('Special settings'),
      '#collapsible' => FALSE,
      '#tree' => FALSE,
    );

    $form['special']['include_in_tag_glossary'] = [
      '#type' => 'checkbox',
      '#title' => t('Include in PowerTagging Tag Glossary'),
      '#description' => t('Show tags of this field in the "PowerTagging Tag Glossary" block (if it is enabled)'),
      '#default_value' => $field->getSetting('include_in_tag_glossary'),
      '#parents' => array('settings', 'include_in_tag_glossary'),
    ];

    $form['special']['automatically_tag_new_entities'] = array(
      '#type' => 'checkbox',
      '#title' => t('Automatically tag new entities'),
      '#description' => t('When entities get created and don\'t have values for this field yet, they will be tagged automatically.'),
      '#default_value' => $field->getSetting('automatically_tag_new_entities'),
      '#empty_value' => '',
      '#parents' => array('settings', 'automatically_tag_new_entities'),
    );

    // Show the fields that can be used for tagging.
    $options = $this->getSupportedTaggingFields($field->getTargetEntityTypeId(), $field->getTargetBundle());
    $form['fields'] = [
      '#type' => 'checkboxes',
      '#title' => t('Fields that can be used for tagging'),
      '#description' => t('Select the fields from which the concepts and free terms are extracted.'),
      '#options' => $options,
      '#default_value' => $field->getSetting('fields'),
      '#required' => TRUE,
    ];

    // Add file upload settings if the content type has the appropriate fields.
    $allowed_modules = array('file', 'media');
    $state_fields_list = array();
    foreach ($options as $field_name => $title) {
      $field_storage = FieldStorageConfig::loadByName($field->getTargetEntityTypeId(), $field_name);
      if (!is_null($field_storage)) {
        if (in_array($field_storage->getTypeProvider(), $allowed_modules)) {
          $state_fields_list[] = ':input[name="settings[fields][' . $field_name . ']"]';
        }
      }
    }

    if (!empty($state_fields_list)) {
      $file_upload_settings = $field->getSetting('file_upload');
      $state_fields = implode(', ', $state_fields_list);
      $form['file_upload'] = array(
        '#type' => 'details',
        '#title' => t('File extraction settings'),
        '#open' => FALSE,
        '#states' => array(
          'visible' => array($state_fields => array('checked' => TRUE)),
        ),
      );

      // Add max file size to the form.
      $max_file_size = floor(file_upload_max_size() / 1048576);
      $max_file_size = ($max_file_size > 10) ? 10 : $max_file_size;
      $file_size_options = array();
      for ($i = 1; $i <= $max_file_size; $i++) {
        $file_size_options[$i * 1048576] = $i . ' MB';
      }
      $default_max_file_size = (isset($file_upload_settings['max_file_size']) && $max_file_size > ($file_upload_settings['max_file_size'] / 1048576)) ? ($file_upload_settings['max_file_size'] / 1048576) : $max_file_size;
      $form['file_upload']['max_file_size'] = array(
        '#type' => 'select',
        '#title' => t('Maximum file size for each attached file'),
        '#description' => t('Only files below the specified value are used for the extraction.'),
        '#options' => $file_size_options,
        '#default_value' => ($default_max_file_size * 1048576),
      );

      // Add max file count to the form.
      $file_count_options = array();
      for ($i = 1; $i <= 10; $i++) {
        $file_count_options[$i] = $i;
      }
      $form['file_upload']['max_file_count'] = array(
        '#type' => 'select',
        '#title' => t('Maximum number of files per node'),
        '#description' => t('Only the specified number of files are used for the extraction per node.'),
        '#options' => $file_count_options,
        '#default_value' => (isset($file_upload_settings['max_file_count']) ? $file_upload_settings['max_file_count']: 5),
      );
    }

    // Limit settings.
    $form['limits'] = [
      '#type' => 'details',
      '#title' => t('Settings for concepts / categories and free terms'),
      '#open' => FALSE,
    ];

    $powertagging_id = $field->getFieldStorageDefinition()
      ->getSetting('powertagging_id');
    $powertagging_config = PowerTaggingConfig::load($powertagging_id);
    $powertagging_config_settings = $powertagging_config->getConfig();
    $limits = empty($field->getSetting('limits')) ? $powertagging_config_settings['limits'] : $field->getSetting('limits');

    $powertagging_mode = $powertagging_config_settings['project']['mode'];
    $powertagging_corpus = $powertagging_config_settings['project']['corpus_id'];
    PowerTaggingConfigForm::addLimitsForm($form['limits'], $limits, TRUE);
    foreach (array('concepts', 'freeterms') as $concept_type) {
      $form['limits'][$concept_type]['#description'] .= '<br />' . t('Note: These settings override the global settings defined in the connected PowerTagging configuration.');
    }

    // The most part of the global limits are only visible when PowerTagging is
    // used for annotation.
    if ($powertagging_mode == 'classification') {
      $form['limits']['concepts']['concepts_threshold']['#access'] = FALSE;
      $form['limits']['freeterms']['#access'] = FALSE;
    }

    if ($powertagging_mode == 'annotation') {
      if (!empty($powertagging_corpus)) {
        $form['limits']['concepts']['use_shadow_concepts'] = array(
          '#type' => 'checkbox',
          '#title' => t('Also find concepts that are not directly contained within the content'),
          '#description' => t('It searches for concepts that do not appear in the content but have something to do with it.'),
          '#default_value' => (!is_null($field->getSetting('use_shadow_concepts')) ? $field->getSetting('use_shadow_concepts') : FALSE),
          '#parents' => array('settings', 'use_shadow_concepts'),
        );
      }

      $form['limits']['concepts']['browse_concepts_charttypes'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Visually browse concepts'),
        '#options' => array(
          'spider' => 'Visual Mapper (circle visualisation)',
          'tree' => 'Tree View',
        ),
        '#default_value' => (!is_null($field->getSetting('browse_concepts_charttypes')) ? $field->getSetting('browse_concepts_charttypes') : []),
        '#description' => t('If at least one of the visualisation types is selected, users can click on a button to use a visualisation to select additional concepts to use in the thesaurus.') . '<br />' . t('Selecting multiple chart types will allow the user to switch between the chart types.'),
        '#parents' => array('settings', 'browse_concepts_charttypes'),
      );

      if (!SemanticConnector::visualMapperUsable()) {
        $form['limits']['concepts']['browse_concepts_charttypes']['#disabled'] = TRUE;
        $form['limits']['concepts']['browse_concepts_charttypes']['#prefix'] = '<div class="messages warning">' . t('To enable the "Visually browse concepts" all requirements of the VisualMapper library need to be met.') . '</div>';
      }
    }

    $form['limits']['freeterms']['custom_freeterms'] = array(
      '#type' => 'checkbox',
      '#title' => 'Allow users to add custom free terms',
      '#description' => 'If this options is enabled users can add custom free terms by writing text in the search-box of the PowerTagging widget and clicking the enter key.',
      '#default_value' => $field->getSetting('custom_freeterms'),
      '#parents' => array('settings', 'custom_freeterms'),
    );

    // Search settings.
    $form['search'] = array(
      '#type' => 'details',
      '#title' => t('Search settings'),
      '#open' => FALSE,
    );

    $form['search']['ac_add_matching_label'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add the matching label to every suggestion in the drop down menu.'),
      '#default_value' => $field->getSetting('ac_add_matching_label'),
      '#parents' => array('settings', 'ac_add_matching_label'),
    );

    $form['search']['ac_add_context'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add the context (title of the concept scheme) to every suggestion in the drop down menu.'),
      '#default_value' => $field->getSetting('ac_add_context'),
      '#parents' => array('settings', 'ac_add_context'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function fieldSettingsToConfigData(array $settings) {
    $limits = [];
    if (!empty($settings['limits'])) {
      $limits = [
        'concepts_per_extraction' => $settings['limits']['concepts']['concepts_per_extraction'],
        'concepts_threshold' => $settings['limits']['concepts']['concepts_threshold'],
        'freeterms_per_extraction' => $settings['limits']['freeterms']['freeterms_per_extraction'],
        'freeterms_threshold' => $settings['limits']['freeterms']['freeterms_threshold'],
      ];
    }
    return [
      'include_in_tag_glossary' => $settings['include_in_tag_glossary'],
      'automatically_tag_new_entities' => $settings['automatically_tag_new_entities'],
      'custom_freeterms' => $settings['custom_freeterms'],
      'use_shadow_concepts' => $settings['use_shadow_concepts'],
      'browse_concepts_charttypes' => $settings['browse_concepts_charttypes'],
      'fields' => $settings['fields'],
      'default_tags_field' => $settings['default_tags_field'],
      'limits' => $limits,
      'file_upload' => $settings['file_upload'],
      'ac_add_matching_label' => $settings['ac_add_matching_label'],
      'ac_add_context' => $settings['ac_add_context'],
    ];
  }

  /**
   * Get the the fields that are supported for the tagging.
   *
   * @param string $entity_type
   *   The entity type to check.
   * @param string $bundle
   *   The bundle to check.
   * @param bool $is_sub_entity
   *   TRUE if the entity already is a referenced entity, FALSE if not.
   *
   * @return array
   *   A list of supported fields.
   */
  public static function getSupportedTaggingFields($entity_type, $bundle, $is_sub_entity = FALSE) {
    $field_definitions = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions($entity_type, $bundle);
    $widget_manager = \Drupal::service('plugin.manager.field.widget');
    $supported_field_types = static::getSupportedFieldTypes($is_sub_entity);
    $supported_fields = [];

    switch ($entity_type) {
      case 'node':
        $supported_fields['title'] = $field_definitions['title']->getLabel()
           . '<span class="description">[Text field]</span>';
        break;

      case 'taxonomy_term':
        $supported_fields['name'] = t('Name of the term') . '<span class="description">[' . t('Textfield') . ']</span>';
        $supported_fields['description'] = t('Description') . '<span class="description">[' . t('Text area (multiple rows)') . ']</span>';
        break;

      case 'user':
        $supported_fields['name'] = t('Name of the user') . '<span class="description">[' . t('Textfield') . ']</span>';
        break;
    }

    // Get the form display to check which widgets are used.
    $form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($entity_type . '.' . $bundle . '.' . 'default');

    /** @var \Drupal\Core\Field\BaseFieldDefinition $field_definition */
    foreach ($field_definitions as $field_definition) {
      if (!$field_definition instanceof FieldConfig) {
        continue;
      }

      $field_storage = $field_definition->getFieldStorageDefinition();
      $specific_widget_type = $form_display->getComponent($field_definition->getName());
      if (isset($supported_field_types[$field_storage->getTypeProvider()][$field_storage->getType()]) && in_array($specific_widget_type['type'], $supported_field_types[$field_storage->getTypeProvider()][$field_storage->getType()])) {
        $widget_info = $widget_manager->getDefinition($specific_widget_type['type']);
        // A normal field.
        if ($field_storage->getType() !== 'entity_reference') {
          $supported_fields[$field_definition->getName()] = $field_definition->label() . '<span class="description">[' . $widget_info['label'] . ']</span>';
        }
        // A referenced entity.
        else {
          $ref_field_settings = $field_definition->getSettings();
          $ref_entity_type = $ref_field_settings['target_type'];
          $ref_bundles = ($ref_entity_type !== 'user') ? array_values(array_filter($ref_field_settings['handler_settings']['target_bundles'])) : ['user'];
          $allowed_entity_types = ['node', 'taxonomy_term', 'user'];
          if (in_array($ref_entity_type, $allowed_entity_types)) {
            foreach ($ref_bundles as $ref_bundle) {
              $sub_results = self::getSupportedTaggingFields($ref_entity_type, $ref_bundle, TRUE);

              foreach ($sub_results as $field_id => $label) {
                $supported_fields[$field_definition->getName() . '|' . $ref_bundle . '|' . $field_id] = $field_definition->label() . ' <span class="description">[' . $widget_info['label'] . ']</span> --> ' . $ref_bundle . ' --> ' . $label;
              }
            }
          }
        }
      }
    }

    return $supported_fields;
  }

}
