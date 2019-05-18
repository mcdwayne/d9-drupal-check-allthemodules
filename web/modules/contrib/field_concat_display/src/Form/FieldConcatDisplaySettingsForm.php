<?php
/**
 * @file
 * This is the GlobalRedirect admin include which provides an interface to global redirect to change some of the default settings
 * Contains \Drupal\field_concat_display\Form\FieldConcatDisplaySettingsForm.
 */

namespace Drupal\field_concat_display\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Defines a field_concat_display settings form
 */
class FieldConcatDisplaySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'field_concat_display_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node_type = NULL) {

    $form['#node_type'] = $node_type;
    $this_node_type = $node_type;

    // Get all the field settings for this content type.
    $settings = \Drupal::state()->get("field_concat_display_settings_{$this_node_type}") ?: array();

    if (!empty($settings)) {
        foreach ($settings as $instance => $data) {

            $field_name = str_replace('_', ' ', $instance);

            // Display a table for each concatenated field and their subfields.
            // $instance refers to a concatenated field.
            $form[$instance]["prefix_suffix_table_{$instance}"] = array(
              '#type' => 'container',
              '#tree' => TRUE,
              '#theme' => 'table',
              '#header' => array($field_name, '', ''),
              '#rows' => array(),
            );

            foreach ($data as $key => $el) {
                // $el refers to a sub-field -- 2 or more sub-fields make up a
                // concatenated field.
                if (!empty($el)) {

                  // Make sure the prefix/suffix/weight have a value.
                  if (!isset($el['prefix'])) {
                    $default_prefix = '';
                  }
                  else {
                    $default_prefix = $el['prefix'];
                  }
                  $prefix = array(
                    '#type' => 'textfield',
                    '#title' => t('@key: PREFIX', array('@key' => $key)),
                    '#default_value' => $default_prefix,
                  );

                  if (!isset($el['suffix'])) {
                    $default_suffix = '';
                  }
                  else {
                    $default_suffix = $el['suffix'];
                  }
                  $suffix = array(
                    '#type' => 'textfield',
                    '#title' => t('@key: SUFFIX', array('@key' => $key)),
                    '#default_value' => $default_suffix,
                  );

                  if (!isset($el['weight'])) {
                    $default_weight = 0;
                  }
                  else {
                    $default_weight = $el['weight'];
                  }
                  $weight = array(
                    '#type' => 'textfield',
                    '#title' => t('@key: WEIGHT', array('@key' => $key)),
                    '#description' => t('Must be zero or greater'),
                    '#default_value' => $default_weight,
                    '#size' => 2,
                    '#maxlength' => 2,
                  );

                  $form[$instance]["prefix_suffix_table_{$instance}"][$key] = array(
                    "prefix_{$key}" => &$prefix,
                    "suffix_{$key}" => &$suffix,
                    "weight_{$key}" => &$weight,
                  );

                  $form[$instance]["prefix_suffix_table_{$instance}"]['#rows'][$key] = array(
                    array('data' => &$prefix),
                    array('data' => &$suffix),
                    array('data' => &$weight),
                  );

                  unset($prefix);
                  unset($suffix);
                  unset($weight);
                }
            }

            // Setup the remove and update buttons.
            $remove = array(
              '#type' => 'submit',
              '#name' => "remove_{$instance}",
              '#value' => t('Remove @instance', array('@instance' => $instance)),
              '#submit' => array('field_concat_display_remove_field'),
            );
            $form[$instance][] = array(
              "remove_{$instance}" => &$remove,
            );

            $update = array(
              '#type' => 'submit',
              '#name' => "update_{$instance}",
              '#value' => t('Update @instance', array('@instance' => $instance)),
              '#submit' => array('field_concat_display_update_field'),
              '#validate' => array('field_concat_display_update_field_validate'),
            );

            $form[$instance][] = array(
              "update_{$instance}" => &$update,
            );
            unset($remove);
            unset($update);
        }
    }

    // Create the form elements for adding new concatenated fields.
    $form['new_field'] = array(
      '#type' => 'container',
    );

    $form['new_field']['label'] = array(
      '#type' => 'item',
      '#title' => t('Create a new field:'),
    );

    // Get the list of fields which appears on this content type
    // so we can select fields to concatenate.
    // $fields = field_info_instances('node', $this_node_type);
    $fields = \Drupal::entityManager()->getFieldDefinitions('node', 'article');
    $field_names = array();
    foreach($fields as $field_key=>$field) {
        if($field->getFieldStorageDefinition()->isBaseField() == FALSE) {
          $field_names[$field_key] = $field_key;
        }
    }

    $form['new_field']['field_concat_display_field_name'] = array(
        '#type' => 'textfield',
        '#title' => t('What should the machine_name/label of this field be?'),
        '#default_value' => (isset($settings['field_name'])) ? $settings['field_name'] : '',
    );

    $form['new_field']['field_concat_display_select_fields'] = array(
      '#type' => 'checkboxes',
      '#options' => $field_names,
      '#title' => 'Select the fields you wish to concatenate: ',
    );

    $form['field_concat_display_new_field_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save New Field'),
      '#validate' => array('field_concat_display_new_field_validate'),
    );

    return $form;

  }

  /**
    * {@inheritdoc}
    */
   public function submitForm(array &$form, FormStateInterface $form_state) {
     $this_node_type = $form['#node_type'];

     $form_state_values = $form_state->getValues();

     // Get all the field settings for this content type.
      $settings = \Drupal::state()->get("field_concat_display_settings_{$this_node_type}") ?: array();

        $field_name = $form_state_values['field_concat_display_field_name'];
        $new_field_name = str_replace(" ", "_", $field_name);

        // Count the number of viable fields... as we increment, we want to use
        // this counter as the value for the field weights.
        $field_counter = 0;
        foreach ($form_state_values['field_concat_display_select_fields'] as $old_field_name) {
          if (!empty($old_field_name)) {
            $new_field[$old_field_name] = array(
              'weight' => $field_counter,
              'prefix' => '',
              'suffix' => '',
            );
            $field_counter++;
          }
        }

        $settings[$new_field_name] = $new_field;

        \Drupal::state()->set("field_concat_display_settings_{$this_node_type}", $settings);

        $var_names = \Drupal::state()->get('field_concat_display_var_names') ?: array();

        if (!in_array($this_node_type, $var_names)) {
          $var_names[] = $this_node_type;
          \Drupal::state()->set('field_concat_display_var_names', $var_names);
        }
        $saved_field_name = str_replace("_", " ", $new_field_name);
        drupal_set_message(t("Field $saved_field_name has been saved!"));

   }
}
