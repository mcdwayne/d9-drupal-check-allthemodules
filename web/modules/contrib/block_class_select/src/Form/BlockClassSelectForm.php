<?php

namespace Drupal\block_class_select\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Block Class Select settings for this site.
 */
class BlockClassSelectForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'block_class_select_admin_settings';
  }

  protected function getEditableConfigNames() {
    return ['block_class_select.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('block_class_select.settings');

    // Make Key Value Pairs as strings for default value
    $classes = $config->get('classes');
    $default_classes = [];
    if (!empty($classes)) {
      foreach ($classes as $key => $value) {
        $default_classes[] = $key . '|' . $value;
      }
    }
    
    $form['intro'] = [
      '#markup' => t('Classes entered below will be available when adding/editing blocks.'),
      '#weight' => '0',
    ];

    $description = '<p>' . $this->t('Enter one value per line, in the format key|label.');
    $description .= '<br/>' . $this->t('The key is the stored class. The label will be used in block edit forms.');
    $description .= '<br/>' . $this->t('The label is optional: if a line contains a single string, it will be used as key and label.');
    $description .= '</p>';
    $form['classes'] = [
      '#title' => t('Allowed Classes List'),
      '#type' => 'textarea',
      '#weight' => '1',
      '#description' => $description,
      '#default_value' => implode("\n", $default_classes),
    ];
    
    $form['multiple'] = [
      '#title' => t('Allow Multiple'),
      '#type' => 'checkbox',
      '#weight' => '2',
      '#description' => $this->t('Allow selecting multiple classes from the list.'),
      '#default_value' => $config->get('multiple'),
    ];

    $form['label'] = [
      '#title' => t('Widget Label'),
      '#type' => 'textfield',
      '#weight' => '3',
      '#description' => $this->t('Overrides the label of the select element on the block form.'),
      '#default_value' => $config->get('label'),
    ];

    $form['description'] = [
      '#title' => t('Widget Description'),
      '#type' => 'textfield',
      '#weight' => '4',
      '#description' => $this->t('Overrides the description of the select element on the block form.'),
      '#default_value' => $config->get('description'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $classes = [];
    $values = explode("\n", $form_state->getValue('classes'));
    foreach ($values as $key => $value) {
      // still getting whitespace or line breaks here so using trim() to get rid of it.
      $class = trim($value);
      if (!empty($class)) {
        // Check for key|value pairs - taken from list module
        $matches = [];
        if (preg_match('/(.*)\|(.*)/', $class, $matches)) {
          $key = $matches[1];
          $value = $matches[2];
          $classes[$key] = $value;
        }
        else {
          $classes[$class] = $class;
        }
      }
    }
    
    $this->config('block_class_select.settings')
      ->set('classes', $classes)
      ->set('multiple', $form_state->getValue('multiple'))
      ->set('label', $form_state->getValue('label'))
      ->set('description', $form_state->getValue('description'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
