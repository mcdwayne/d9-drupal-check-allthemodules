<?php
/**
 * @file
 * Contains \Drupal\lesser_forms\Form\ConfigurationForm.
 */

namespace Drupal\lesser_forms\Form;
use Drupal\lesser_forms\Controller\LesserFormsController;
use Drupal\user\Entity\Role;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form.
 */
class ConfigurationForm extends ConfigFormBase {


  /**
   * Class constructor.
   */
  public function __construct() {
    // Include functions from LesserFormsController.
    $this->lesser_forms = new LesserFormsController();
  }


  /**
   * Gets the form ID.
   *
   * @return array
   *   Returns form id.
   */
  public function getFormId() {
    return 'lesser_forms_settings';
  }


  /**
   * Get the editable configuration names.
   *
   * @return array
   *   Returns config settings from file.
   */
  public function getEditableConfigNames() {
    return [
      'lesser_forms.settings',
    ];
  }

  /**
   * Show the configuration panel.
   *
   * @parameter $form
   * @parameter $form_state
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('lesser_forms.settings');
    $applies_to = $config->get('applies_to');
    $user_roles = Role::loadMultiple();
    $header = $this->lesser_forms->getTableHeader($user_roles);

    // Provide default fields + all custom fields.
    $fields = array_merge($this->lesser_forms->getConfigFields(), $this->lesser_forms->getCustomFields());

    // Add stylesheet.
    $form['#attached']['library'][] = 'lesser_forms/main';

    $form['lf_wrapper'] = array(
      '#prefix' => '<div id="lf_wrapper">',
      '#suffix' => '</div>',
    );

    $form['lf_wrapper']['lf_table'] = array(
      '#type' => 'table',
      '#header' => $header,
    );
    foreach ($fields as $field) {
      $form['lf_wrapper']['lf_table'][$field]['title'] = array(
        '#plain_text' => $field,
      );
      $form = $this->lesser_forms->printUserRoles($form, $field);
      // Make empty cell for the standard fields.
      $form['lf_wrapper']['lf_table'][$field]['button_delete_custom_field'] = array();
      // Print custom fields.
      if (in_array($field, $this->lesser_forms->getCustomFields())) {
        // Add Delete button to custom field rows.
        $form['lf_wrapper']['lf_table'][$field]['button_delete_custom_field'] = array(
          '#type' => 'submit',
          '#name'  => 'delete-custom-field-button-' . $field,
          '#value' => t('Remove'),
          '#value_callback' => $field,
        );
      }
    }

    // Add Custom Fields to table.
    $form['add_custom_field'] = array(
      '#type' => 'textfield',
      '#title' => 'Add Custom Field',
      '#autocomplete_route_name' => 'lesser_forms.autocomplete',
      '#attributes' => array(
        'placeholder' => t('Custom Field Machine Name'),
      ),
    );
    $form['button_add_custom_field'] = array(
      '#value' => t('Add'),
      '#type' => 'submit',
    );

    // Lesser Forms is active on these form machine names.
    $form['applies_to'] = array(
      '#title' => t('Enabled on'),
      '#type' => 'textarea',
      '#description' => t('Lesser Forms will only be active on the form machine names you insert here. Only one machine name per line. Use new lines to add multiples.') . '<br>' . t('You can use a wildcard "*"-symbol to apply to every standardized form, but keep in mind this is arbitrary.') . '<br>' . t('These content type forms are known by your site already:') . ' ' . $this->lesser_forms->getNodeTypesForms(),
      '#default_value' => $applies_to,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#name' => 'form-submit',
      '#value' => t('Save config'),
    );

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    // Validation for adding empty input of whitespace for new custom field.
    if (in_array('button_add_custom_field', $element['#parents'])) {
      $value = $form_state->getValue('add_custom_field');
      if (empty($value)) {
        $form_state->setErrorByName('add_custom_field', $this->t('Input for new custom field can not be empty.'));
      }
      elseif (strpos($value, " ") !== FALSE) {
        $form_state->setErrorByName('add_custom_field', $this->t('Custom field name can not have whitespace.'));
      }
      else {
        // Passed validation so add custom submit handler to add custom fields.
        $form['#submit']['button_add_custom_field'] = array($this->lesser_forms, 'addCustomFieldSubmit');
      }
    }
    // If remove button was clicked.
    elseif (in_array('button_delete_custom_field', $element['#parents'])) {
      // Custom submit handler to remove custom fields.
      $form['#submit']['button_delete_custom_field'] = array($this->lesser_forms, 'removeCustomFieldSubmit');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('lesser_forms.settings');
    $config->set('fields', $form_state->getUserInput()['lf_table']);
    $config->set('applies_to', $form_state->getUserInput()['applies_to']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
