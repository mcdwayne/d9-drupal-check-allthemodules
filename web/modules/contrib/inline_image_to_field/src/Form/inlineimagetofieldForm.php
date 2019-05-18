<?php

namespace Drupal\inline_image_to_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * {@inheritdoc}
 */
class inlineimagetofieldForm extends ConfigFormBase {

  protected $config;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactory $config) {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['inline_image_to_field.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'inline_image_to_field_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get all content types.
    $nodes = entity_get_bundles('node');
    // Loop through each content type.
    foreach ($nodes as $type => $node) {
      // Get all fields on the content type.
      $fields = \Drupal::entityManager()->getFieldDefinitions('node', $type);
      // Populate a list of options from the field names.
      $options = ['none' => 'None'];
      foreach ($fields as $field => $value) {
        if (strpos($field, 'field_') !== FALSE || strpos($field, 'body') !== FALSE) {
          $options[$field] = $field;
        }
      }

      $config = $this->config('inline_image_to_field.settings');

      // Create a settings form for each content type that has fields or a body.
      if (count($options) > 1) {
        $form['inline_image_to_field_' . $type] = [
          '#type' => 'fieldset',
          '#title' => $type,
        ];
        $form['inline_image_to_field_' . $type]['iitf_wysiwyg_' . $type] = [
          '#type' => 'select',
          '#title' => $this->t('WYSIWYG Field'),
          '#description' => $this->t('The text field that is using a WYSIWYG to add images'),
          '#default_value' => $config->get('iitf_wysiwyg_' . $type),
          '#group' => 'inline_image_to_field_' . $type,
          '#options' => $options,
        ];
        $form['inline_image_to_field_' . $type]['iitf_image_' . $type] = [
          '#type' => 'select',
          '#title' => $this->t('Image Field'),
          '#description' => $this->t('The image field of which the WYSIWYG images will be attached.'),
          '#default_value' => $config->get('iitf_image_' . $type),
          '#group' => 'inline_image_to_field_' . $type,
          '#options' => $options,
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Get all form state values.
    $values = $form_state->getValues();
    $configuration = $this->config->getEditable('inline_image_to_field.settings');
    // Loop through all of the values and set any values that are applicable.
    foreach ($values as $key => $value) {
      if (strpos($key, 'iitf_wysiwyg') !== FALSE || strpos($key, 'iitf_image') !== FALSE) {
        $configuration->set($key, $value);
      }
    }
    $configuration->save();
  }

}
