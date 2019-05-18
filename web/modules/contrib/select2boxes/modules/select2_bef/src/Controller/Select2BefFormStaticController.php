<?php

namespace Drupal\select2_bef\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Class Select2BefFormController.
 *
 * @package Drupal\select2_bef\Controller
 */
class Select2BefFormStaticController {
  /**
   * Library name.
   *
   * @var string
   */
  public static $library = 'select2boxes/widget';

  /**
   * Check if the form key is valid for adding Select2 widgets.
   *
   * @param string $key
   *   Form element's key.
   *
   * @return bool
   *   Checking result.
   */
  public static function isValidFormKey($key) {
    return
      stripos($key, 'field_') !== FALSE ||
      stripos($key, 'langcode') !== FALSE ||
      in_array($key, ['langcode', 'content_translation_source']);
  }

  /**
   * Handle list widget.
   *
   * @param array &$form
   *   Form array.
   * @param \Drupal\views\Plugin\views\filter\FilterPluginBase $filter
   *   Filter object.
   * @param array &$libraries
   *   Libraries array.
   * @param string $key
   *   Form element key.
   * @param array $bef
   *   BEF settings.
   */
  public static function handleList(array &$form, FilterPluginBase $filter, array &$libraries, $key, array $bef) {
    $form[$key]['#attributes'] = [
      // Disable core autocomplete.
      'data-jquery-once-autocomplete'         => 'true',
      'data-select2-autocomplete-list-widget' => 'true',
      'class'                                 => ['select2-widget'],
      'data-field-name'                       => str_replace('_value', '', $key),
    ];
    $form[$key]['#multiple'] = $filter->options['expose']['multiple'];
    // Pass additional data attribute for multiple fields.
    if ($form[$key]['#multiple']) {
      $form[$key]['#attributes']['data-select2-multiple'] = 'true';
    }
    // Pass additional data attribute for fields
    // with enabled "limited_search" option.
    if (!empty($bef[$key]['more_options']['limited_search']) && $bef[$key]['more_options']['limited_search'] == '1') {
      $form[$key]['#attributes']['data-minimum-search-length'] = $bef[$key]['more_options']['minimum_search_length'];
    }
    // Include the flags icons if enabled in BEF settings.
    if (!empty($bef[$key]['more_options']['include_flags']) && $bef[$key]['more_options']['include_flags'] == '1') {
      static::includeIcons($form, $filter->definition, $key);
    }
    // Attach library.
    static::addLibrary($libraries);
  }

  /**
   * Include flags icons.
   *
   * @param array &$form
   *   Form array.
   * @param array $filter_definition
   *   Filter's definition data.
   * @param string $key
   *   Element's key.
   */
  protected static function includeIcons(array &$form, array $filter_definition, $key) {
    // Create a map of country or language dependent classes.
    $flags = [];
    // Get some additional data from the filter's definition data array.
    $field_name = isset($filter_definition['field_name']) ? $filter_definition['field_name'] : $filter_definition['entity field'];
    $entity_type = $filter_definition['entity_type'];
    // Get the field's definition object to know the field's type.
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $definition */
    $definition = \Drupal::service('entity_field.manager')
      ->getFieldStorageDefinitions($entity_type)[$field_name];
    // Use an appropriate mapper service relatively to the field's type.
    $mapper = !is_null($definition) && $definition->getType() == 'country'
      ? \Drupal::service('flags.mapping.country')
      : \Drupal::service('flags.mapping.language');
    foreach (array_keys($form[$key]['#options']) as $key) {
      if ($key == '***LANGUAGE_language_interface***' || $key == '***LANGUAGE_site_default***') {
        continue;
      }
      $flags[$key] = [
        'flag',
        'flag-' . $mapper->map($key),
        $mapper->getExtraClasses()[0],
      ];
    }
    // Merge these values to have all countries
    // and languages flags in the same place to prevent missing flags icons.
    if (!isset($form['#attached']['drupalSettings']['flagsClasses'])) {
      $form['#attached']['drupalSettings']['flagsClasses'] = [];
    }
    $form['#attached']['drupalSettings']['flagsClasses'] += $flags;
    // We have to use field's column name for views,
    // e.g. FIELD_NAME_value instead of FIELD_NAME.
    // Note: small workaround for the entity properties like langcode.
    if (!isset($filter_definition['field'])) {
      $filter_definition['field'] = $filter_definition['entity field'];
    }
    $form['#attached']['drupalSettings']['flagsFields'][$filter_definition['field']] = TRUE;
    // Attach the flags library.
    $form['#attached']['library'][] = 'flags/flags';
  }

  /**
   * Handle single-autocomplete widget.
   *
   * @param array &$form
   *   Form array.
   * @param array &$libraries
   *   Libraries array.
   * @param string $key
   *   Form element key.
   * @param array $bef
   *   BEF settings.
   */
  public static function handleSingleAutocomplete(array &$form, array &$libraries, $key, array $bef) {
    // Extract entity reference data.
    $data = static::extractReferenceFieldsData($key);
    // Get list of referenced bundles from the BEF settings.
    $reference_bundles = $bef[$key]['more_options']['reference_bundles'];
    /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface[] $storage_definitions */
    $storage_definitions = \Drupal::service('entity_field.manager')
      ->getFieldStorageDefinitions($data['entity_type']);
    $field_settings = $storage_definitions[$data['field_name']]->getSettings();

    // Build options list based on target entity type and bundles.
    $options = static::buildOptions($field_settings['target_type'], $reference_bundles);
    // Alter the existing filter element
    // to make it dropdown, working using Select2 library.
    $form[$key]['#type']       = 'select';
    $form[$key]['#options']    = ['All' => '- Any -'] + $options;
    $form[$key]['#multiple']   = FALSE;
    $form[$key]['#attributes']['data-jquery-once-autocomplete'] = 'true';
    $form[$key]['#attributes']['data-select2-autocomplete-list-widget'] = 'true';
    $form[$key]['#attributes']['class'][] = 'select2-widget';
    $form[$key]['#attributes']['data-field-name'] = str_replace('_target_id', '', $key);

    // Prevent error "Illegal choice".
    if (!\Drupal::request()->query->has($key)) {
      $form[$key]['#value'] = '';
      $form[$key]['#default_value'] = 'All';
    }
    // Pass additional data attribute for fields
    // with enabled "limited_search" option.
    if ($bef[$key]['more_options']['limited_search'] == '1') {
      $form[$key]['#attributes']['data-minimum-search-length'] = $bef[$key]['more_options']['minimum_search_length'];
    }
    // Attach library.
    static::addLibrary($libraries);
  }

  /**
   * Handle multi-autocomplete widget.
   *
   * @param array &$form
   *   Form array.
   * @param array &$libraries
   *   Libraries array.
   * @param string $key
   *   Form element key.
   */
  public static function handleMultiAutocomplete(array &$form, array &$libraries, $key) {
    // Extract entity reference data.
    $data = static::extractReferenceFieldsData($key);
    /** @var \Drupal\Core\Entity\EntityFieldManager $manager */
    $manager = \Drupal::service('entity_field.manager');
    /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface[] $storage_definitions */
    $storage_definitions = $manager->getFieldStorageDefinitions($data['entity_type']);
    $field_settings = $storage_definitions[$data['field_name']]->getSettings();

    /** @var \Drupal\field\Entity\FieldConfig $definition */
    $definition = $manager->getFieldDefinitions($data['entity_type'], reset($data['bundles']))[$data['field_name']];
    $definition = $definition->getSettings();
    $definition['target_type'] = $field_settings['target_type'];
    /** @var \Drupal\Core\Entity\EntityFieldManager $field_config */
    // Alter the existing filter element
    // to make it dropdown, working using Select2 library.
    $form[$key]['#type']       = 'select';
    $form[$key]['#attributes']['data-jquery-once-autocomplete'] = 'true';
    $form[$key]['#attributes']['data-select2-multiple'] = 'true';
    $form[$key]['#attributes']['data-autocomplete-path'] = static::getEntityAutocompletePath($definition);
    $form[$key]['#attributes']['data-field-name'] = $data['field_name'];
    $form[$key]['#attributes']['class'][] = 'select2-widget';
    $form[$key]['#attributes']['class'][] = 'select2-boxes-widget';

    $form[$key]['#needs_validation'] = FALSE;
    $form[$key]['#name'] = $key;
    $form[$key]['#multiple'] = $form[$key]['#validated'] = TRUE;
    static::addLibrary($libraries);
  }

  /**
   * Build options list array.
   *
   * @param string $target_entity_type
   *   Target entity type ID.
   * @param array $bundles
   *   Bundle names. Optional.
   *
   * @return array
   *   Options array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected static function buildOptions($target_entity_type, array $bundles = []) {
    // Prepare required keys, from the entity type definitions.
    $definition = \Drupal::entityTypeManager()->getDefinition($target_entity_type);
    $bundle_key = $definition->getKey('bundle');
    $id_key     = $definition->getKey('id');
    $label_key  = $definition->getKey('label');
    $data_table = $definition->getDataTable();

    // Select data using the appropriate entity keys.
    $select = \Drupal::database()->select($data_table, 'opt');
    $select->fields('opt', [$id_key, $label_key]);
    // Add an optional condition by specific entity bundle.
    if (isset($bundles) && !empty($bundles)) {
      $select->condition($bundle_key, $bundles, 'IN');
    }
    $options = $select
      ->orderBy($label_key)
      ->distinct()
      ->execute()
      ->fetchAllKeyed();
    return !empty($options) ? $options : [];
  }

  /**
   * Add library if it's not already there.
   *
   * @param array &$libraries
   *   Libraries array.
   */
  protected static function addLibrary(array &$libraries) {
    if (!in_array(static::$library, $libraries)) {
      $libraries[] = static::$library;
    }
  }

  /**
   * Extract entity reference fields data.
   *
   * @param string $key
   *   Form element key.
   *
   * @return array
   *   Field's data.
   */
  protected static function extractReferenceFieldsData($key) {
    $data = [];
    $fields = \Drupal::service('entity_field.manager')
      ->getFieldMapByFieldType('entity_reference');
    foreach ($fields as $type => $bundle_fields) {
      foreach ($bundle_fields as $field_name => $field) {
        if ($field_name === str_replace('_target_id', '', $key)) {
          $data['bundles']     = array_keys($field['bundles']);
          $data['entity_type'] = $type;
          $data['field_name']  = $field_name;
          break;
        }
      }
    }
    return $data;
  }

  /**
   * Get entity autocomplete path.
   *
   * @param array $field_settings
   *   Field settings array.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   Entity autocomplete path.
   */
  protected static function getEntityAutocompletePath(array $field_settings) {
    // Store the selection settings in the key/value store and pass a hashed key
    // in the route parameters.
    $selection_settings = $field_settings['handler_settings'];
    $data = serialize($selection_settings)
      . $field_settings['target_type']
      . $field_settings['handler'];
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());

    $key_value_storage = \Drupal::keyValue('entity_autocomplete');
    if (!$key_value_storage->has($selection_settings_key)) {
      $key_value_storage->set($selection_settings_key, $selection_settings);
    }

    $params = [
      'target_type'            => $field_settings['target_type'],
      'selection_handler'      => $field_settings['handler'],
      'selection_settings_key' => $selection_settings_key,
    ];

    return Url::fromRoute('system.entity_autocomplete', $params)->toString();
  }

  /**
   * Reset taxonomy term filters extra settings.
   *
   * If the Select2 widget was specified in BEF settings for this field.
   *
   * @param array $bef
   *   BEF options array.
   * @param string $field_id
   *   Current field name.
   * @param array &$form
   *   Form array to be altered if needed.
   */
  public static function resetTaxonomyExtraSetting(array $bef, $field_id, array &$form) {
    // List of possible widgets.
    static $formats = ['select2boxes_autocomplete_multi', 'select2boxes_autocomplete_single'];
    // Ensure this field is handled by BEF.
    if (isset($bef[$field_id])) {
      // Check if BEF format for this field is one of the Select2 widgets.
      if (in_array($bef[$field_id]['bef_format'], $formats)) {
        // Remove the autocomplete option.
        unset($form['options']['type']['#options']['textfield']);
        // Set dropdown option as a default one.
        $form['options']['type']['#default_value'] = 'select';
      }
    }
  }

}
