<?php
/**
 * @file
 * Contains \Drupal\lesser_forms\Controller\LesserFormsController.
 */

namespace Drupal\lesser_forms\Controller;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;
use Drupal\node\Entity\NodeType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;

/**
 * LesserFormsController.
 */
class LesserFormsController extends ControllerBase {

  public static $configFields = array(
    'promote',
    'sticky',
    'preview',
    'author',
    'revision_information',
    'path',
  );

  /**
   * Returns all Configuration Fields.
   */
  public function getConfigFields() {
    return self::$configFields;
  }

  /**
   * Get all the fields from our config.
   *
   * @return array|mixed|null
   *   Returns all fields in settings.
   */
  public function getFields() {
    $config = \Drupal::config('lesser_forms.settings');
    $fields = $config->get('fields');

    return $fields;
  }

  /**
   * Get only the custom fields.
   *
   * @return array $$custom_fields
   *   Returns custom fields.
   */
  public function getCustomFields() {
    $custom_fields = array();
    foreach ($this->getFields() as $key => $value) {
      if (!in_array($key, self::$configFields)) {
        array_push($custom_fields, $key);
      }
    }

    return $custom_fields;
  }

  /**
   * Custom Callback to add custom form field to array.
   *
   * @param array $form
   *   The form from the table.
   * @param string $field
   *   The field name.
   *
   * @return array $form
   *    returns updated form.
   */
  public function printUserRoles($form, $field) {
    $user_roles = Role::loadMultiple();
    $fields = $this->getFields();

    foreach ($user_roles as $id => $entity) {
      $form['lf_wrapper']['lf_table'][$field][$entity->id()] = array(
        '#type' => 'checkbox',
        '#default_value' => $this->getSettingsValue($fields, $entity->id(), $field),
      );
    }
    return $form;
  }


  /**
   * Get the default value of the checkbox out of the settings.
   */
  public function getSettingsValue($settings, $role_name, $field_name) {
    if ($settings == NULL || $settings[$field_name] == NULL) {
      return FALSE;
    }
    return $settings[$field_name][$role_name];
  }

  /**
   * Prepares the config table header.
   */
  public function getTableHeader($user_roles) {
    $header = array(t('field'));

    foreach ($user_roles as $id => $entity) {
      $header[] = $entity->label();
    }
    $header[] = t('Actions');
    return $header;
  }


  /**
   * Returns all NodeTypes.
   */
  public function getNodeTypesForms() {
    $array_keys = array_keys(NodeType::loadMultiple());

    // Format the keys. @todo: Is there a better way of formatting the form_id?
    foreach ($array_keys as $item) {
      $items[$item] = 'node_' . $item . '_form';
      $items[] = 'node_' . $item . '_edit_form';
    }

    return implode(', ', $items);
  }


  /**
   * Custom Submit handler to add custom fields to form.
   *
   * @parameter $form
   * @parameter $form_state
   */
  public function addCustomFieldSubmit(array &$form, FormStateInterface $form_state) {
    $custom_field = $form_state->getValue('add_custom_field');
    $new_record = array(
      'title' => array(
        '#plain_text' => $custom_field,
      ),
    );
    $new_record_values = array();

    foreach (Role::loadMultiple() as $id => $entity) {
      $new_record[] = array(
        '#type' => 'checkbox',
        '#default_value' => '',
      );
      $new_record_values[$entity->id()] = NULL;
    }

    $form['lf_wrapper']['lf_table'][] = $new_record;
    $form_state->setValue($custom_field, $new_record_values);

    $save_config = $form_state->getUserInput()['lf_table'];
    $save_config[$custom_field] = $new_record_values;
    self::saveConfig($save_config);
  }

  /**
   * Custom Submit handler to remove custom fields from form.
   *
   * @parameter $form
   * @parameter $form_state
   */
  public function removeCustomFieldSubmit(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getTriggeringElement();
    $custom_field_value = $values['#value_callback'];

    $save_config = $form_state->getUserInput()['lf_table'];
    // Remove custom field from array.
    unset($save_config[$custom_field_value]);

    self::saveConfig($save_config);
  }

  /**
   * Save the form config and refresh page.
   *
   * @parameter $save_config
   */
  public function saveConfig($save_config) {
    $config = \Drupal::configFactory()->getEditable('lesser_forms.settings');
    $config->set('fields', $save_config);
    $config->save();
  }

  /**
   * Autocomplete callback for add custom field textfield.
   *
   * @parameter Request request
   *   Request with search term.
   *
   * @return array
   *   Returns JsonResponse.
   */
  public function autocomplete(Request $request) {

    $input = $request->query->get('q');
    $output = array();

    // Get all elements already in fields table.
    $config = \Drupal::configFactory()->getEditable('lesser_forms.settings');
    $config_fields = $config->get('fields');

    // Get All Content Types.
    $content_types = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    foreach ($content_types as $content_type) {
      // Get all fields per content type.
      $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $content_type->get('type'));

      foreach ($fields as $field) {
        // Add machine name to array.
        $name = $field->getName();
        // If Item is not already in the table.
        if (!in_array($name, $output) && !array_key_exists($name, $config_fields)) {
          $output[] = $name;
        }
      }
    }
    // Only return names that match user input value.
    $output = array_filter($output, function ($item) use ($input) {
      if (stripos($item, $input) !== FALSE) {
        return TRUE;
      }
      return FALSE;
    });

    // Sort array ascending.
    sort($output);
    if (empty($output)) {
      $output = array(t('No results found in the content types but you can still add your custom field'));
    }

    // Append the results to the autocomplete element.
    return new JsonResponse($output);
  }

}
