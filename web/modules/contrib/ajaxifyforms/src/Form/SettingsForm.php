<?php

namespace Drupal\ajaxify_submit_forms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\ajaxify_submit_forms\Form
 */
class SettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ajaxify_submit_forms';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ajaxify_submit_forms.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ajaxify_submit_forms.settings');
    $count = !empty($config->getRawData()) ? count($config->getRawData()) : 1;

    // State that the form needs to allow for a hierarchy (ie, multiple
    // names with our names key).
    $form['#tree'] = TRUE;

    // Initial number of names.
    if (!$form_state->get('num_names')) {
      $form_state->set('num_names', $count);
    }

    // Container for our repeating fields.
    $form['names'] = [
      '#type' => 'container',
      '#prefix' => '<div id="names_fieldset_wrapper">',
      '#suffix' => '</div>',
    ];

    $num = $form_state->get('num_names');

    // Add our names fields.
    for ($x = 0; $x < $form_state->get('num_names'); $x++) {

      $value = explode(':', $config->get('ajaxify_submit_form_' . $x));

      $form['names'][$x] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['item-form']],
      ];
      $form['names'][$x]['id_form'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Form ID'),
        '#default_value' => !empty($value) ? $value[0] : '',
        '#description' => $this->t('Example : Form ID ="user_register_form" and Action ="submit"'),
      ];

      $form['names'][$x]['actions'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Action'),
        '#default_value' => !empty($value) ? $value[1] : '',
      ];

    }

    // Button to add more names.
    $form['addname'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add more'),
      '#ajax' => [
        'callback' => '::addNewFields',
        'wrapper' => 'names_fieldset_wrapper',
        'effect' => 'fade',
      ],
    ];

    // Submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    $form['#attached']['library'][] = 'ajaxify_submit_forms/ajaxify_submit_forms.style';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Decide what action to take based on which button the user clicked.
    switch ($values['op']) {
      case 'Add more':
        $this->addNewFields($form, $form_state);
        break;

      default:
        $this->Save($form, $form_state);
    }
  }

  /**
   * Handle adding new.
   */
  public function addNewFields(array &$form, FormStateInterface $form_state) {
    // Add 1 to the number of names.
    $num_names = $form_state->get('num_names');
    $form_state->set('num_names', ($num_names + 1));

    // Rebuild the form.
    $form_state->setRebuild();
    return $form['names'];
  }

  /**
   * Handle submit.
   */
  private function Save(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $config = \Drupal::configFactory()->getEditable('ajaxify_submit_forms.settings');
    foreach ($form_values['names'] as $key => $value) {

      if (!empty($value['id_form'])) {
        $config->set('ajaxify_submit_form_' . $key, $value['id_form'] . ':' . $value['actions'])->save();
      }
      else {
        $config->clear('ajaxify_submit_form_' . $key)->save();
      }

    }

    drupal_set_message(t('Your configuration is saved'), 'status');

  }

}
