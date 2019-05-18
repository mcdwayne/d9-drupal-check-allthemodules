<?php

namespace Drupal\identification\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a form to configure identfication settings for this site.
 */
class IdentificationConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'identification_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['identification.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('identification.settings');
    $url_object = \Drupal::service('path.validator')->getUrlIfValid('admin/config/people/accounts/fields');
    $route_name = $url_object->getRouteName();
    // Create URL.
    $url = Url::fromRoute($route_name);
    $form['identification_field_name'] = array(
      '#type' => 'select',
      '#title' => $this->t('Identification field'),
      '#field_suffix' => \Drupal::l(t('Create new field'), $url),
      '#default_value' => $config->get('identification_field_name'),
      '#description' => $this->t('Select the field to use for the user identification number (or string).'),
      '#required' => FALSE,
    );

    // We'll allow the following field types to be used as an identifier.
    $field_types = array(
      'decimal',
      'float',
      'integer',
      'string',
    );

    // Grab list of eligible field names and place them as options on the form.
    $options = array('' => '- None -');
    foreach (\Drupal::entityManager()->getFieldDefinitions('user', 'user') as $field_name => $field_definition) {
      $field_definition_bundle = $field_definition_type = NULL;
      $field_definition_bundle = $field_definition->getTargetBundle();
      $field_definition_type = $field_definition->getType();
      if (!empty($field_definition_bundle) && in_array($field_definition_type, $field_types)) {
        $options[$field_name] = $field_definition->getLabel() . " ({$field_name})";
      }
    }
    $form['identification_field_name']['#options'] = $options;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('identification.settings');
    $config->set('identification_field_name', $form_state->getValue('identification_field_name'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
