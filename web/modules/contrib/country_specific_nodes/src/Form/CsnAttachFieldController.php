<?php

namespace Drupal\country_specific_nodes\Form;

/**
 * @file
 * Admin settings form for country specific nodes.
 */
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contribute form.
 */
class CsnAttachFieldController extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'country_specific_nodes_content_type_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = $field_exist = $field_include_exist = array();

    // Get list of active content types.
    $types = node_type_get_names();

    // Fieldset for our data.
    $form['options'] = array(
      '#type' => 'fieldset',
      '#title' => t('List of options available'),
      '#markup' => '<div>' . t('Select content type to attach country field and uncheck it to remove.') . '</div>',
      '#weight' => 8,
    );

    // Get include content types.
    $include_list_string = \Drupal::config('country_specific_nodes.settings')->get('country_specific_nodes_include_list');
    $include_list = explode(',', $include_list_string);

    // Check if field is attached to content types.
    foreach ($types as $key => $type_val) {
      $country_field_config = FieldConfig::loadByName('node', $key, 'field_countries_cce');
      if (!empty($country_field_config)) {
        $selected_buldles[$key] = $type_val;
      }
      $field_exist[] = !empty($country_field_config) ? $key : '';
      if (!empty($include_list)) {
        $field_include_exist[] = in_array($key, $include_list) ? $key : '';
      }
    }

    // List content types with checkboxes.
    $form['options']['content_types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Standard Functionality'),
      '#options' => $types,
      '#default_value' => $field_exist,
      '#description' => t('Select the content types for which the nodes must be hidden for selected countries.'),
      '#weight' => 9,
    );

    // List content types with checkboxes to invert modules functionality.
    if (!empty($selected_buldles)) {
      $form['options']['include'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Invert Functionality'),
        '#options' => $selected_buldles,
        '#default_value' => $field_include_exist,
        '#description' => t('Select the content types for which the nodes must be made visible for selected countries.'),
        '#weight' => 10,
      );
    }
    // Submit button.
    $form['options']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save Settings'),
      '#weight' => 11,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();

    // Get selected/non-selected content types.
    $selected_types = $form_values['content_types'];

    // Save the content types for which the functionality needs to be inverted.
    $selected_include_types_array = $form_values['include'];
    $selected_include_types_string = implode(",", $selected_include_types_array);

    $csn_config = \Drupal::getContainer()->get('config.factory')->getEditable('country_specific_nodes.settings');
    $csn_config->set('country_specific_nodes_include_list', $selected_include_types_string);
    $csn_config->save();

    foreach ($selected_types as $key => $type_val) {
      // Check for field is already attached to content type.
      // Get our country field config.
      $country_field_storage = FieldStorageConfig::loadByName('node', 'field_countries_cce');
      $country_field_config = FieldConfig::loadByName('node', $key, 'field_countries_cce');
      $invert_field_storage = FieldStorageConfig::loadByName('node', 'field_invert_countries');
      $invert_field_config = FieldConfig::loadByName('node', $key, 'field_invert_countries');

      // Check which content types have been selected by user.
      if (!empty($type_val)) {
        // Check if field already exits, if not create it.
        if (empty($country_field_storage)) {
          // Field storage array.
          $country_field_storage = array(
            'field_name' => 'field_countries_cce',
            'type' => 'list_string',
            'entity_type' => 'node',
            'settings' => array(
              'label' => 'Countries',
              'description' => 'Provides country list to be excluded/included for nodes.',
              'allowed_values_function' => '_get_csn_countries',
            ),
            'cardinality' => -1,
          );
          // This creates the field storage.
          FieldStorageConfig::create($country_field_storage)->save();
        }
        if (empty($country_field_config)) {
          // Creating instance of the field.
          // Field structure array.
          $country_field_config = array(
            'field_name' => 'field_countries_cce',
            'entity_type' => 'node',
            'bundle' => $key,
            'label' => t('Countries'),
            'description' => t('Select countries to filter the node based on user country.'),
            'default_value' => NULL,
            'multiple' => TRUE,
            'settings' => array(
              'allowed_values_function' => '_get_csn_countries',
            ),
          );

          // This attaches the field to the node.
          FieldConfig::create($country_field_config)->save();

          // Now lets set the form display.
          entity_get_form_display('node', $key, 'default')
            ->setComponent('field_countries_cce', array(
              'type' => 'options_select',
              'weight' => 35,
            ))
            ->save();
        }

        if (empty($invert_field_storage)) {
          // Field storage array.
          $invert_field_storage = array(
            'field_name' => 'field_invert_countries',
            'type' => 'boolean',
            'entity_type' => 'node',
            'settings' => array(
              'label' => 'Invert',
              'description' => 'Provides invert option for the country field behavior.',
              'allowed_values_function' => '',
              'allowed_values' => array(
                0 => '0',
                1 => '1',
              ),
            ),
            'cardinality' => -1,
          );
          // This creates the field storage.
          FieldStorageConfig::create($invert_field_storage)->save();
        }
        if (empty($invert_field_config)) {
          // Creating instance of the field.
          // Field structure array.
          $invert_field_config = array(
            'field_name' => 'field_invert_countries',
            'entity_type' => 'node',
            'bundle' => $key,
            'label' => t('Invert'),
            'description' => t('Check if you want to show this node to all countries except the selected.'),
            'default_value' => NULL,
            'multiple' => TRUE,
            'settings' => array(
              'allowed_values' => array(
                0 => '0',
                1 => '1',
              ),
            ),
          );
          // This attaches the field to the node.
          FieldConfig::create($invert_field_config)->save();

          // Now lets set the form display.
          entity_get_form_display('node', $key, 'default')
            ->setComponent('field_invert_countries', array(
              'type' => 'options_select',
              'weight' => 35,
            ))
            ->save();
        }
      }
      else {
        // Check if field storage exits and delete it.
        if (!empty($country_field_config)) {
          // Delete field for unchecked content types.
          $country_field_config->delete();
        }
        if (!empty($invert_field_config)) {
          // Delete field for unchecked content types.
          $invert_field_config->delete();
        }
      }
    }
    // End of Foreach.
    // Calling this is important and hook_node_load doesn't identify
    // latest change.
    drupal_flush_all_caches();
    drupal_set_message(t('Settings have been successfully saved.'));
  }

}
