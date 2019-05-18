<?php

namespace Drupal\input_reset\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class InputRestSettingForm.
 */
class InputRestSettingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'input_reset.inputrestsetting',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'input_rest_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config                 = $this->config('input_reset.inputrestsetting');
    $config_field_ids_array = $config->get('field_ids_array');

    $name_field             = $form_state->get('num_names');
    $form['#tree']          = TRUE;
    $form['hint']           = [
      '#markup' => $this->t("<div>To enable input reset in <b>user login</b> form on <b>username</b> field then combination should be <b>user_login_form|edit-name</b>. </div>"),
    ];
    $form['names_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Combinations'),
      '#prefix' => "<div id='names-fieldset-wrapper'>",
      '#suffix' => '</div>',
    ];
    $name_field_count       = 1;
    if (count($config_field_ids_array)) {
      $name_field_count = count($config_field_ids_array);
    }
    if (empty($name_field)) {
      $name_field = $form_state->set('num_names', $name_field_count);
    }

    for ($i = 0; $i < $form_state->get('num_names'); $i++) {
      $form['names_fieldset'][$i]['input_reset_field_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Form and field id'),
        '#maxlength' => 64,
        '#size' => 64,
        '#default_value' => isset($config_field_ids_array[$i]) ? $config_field_ids_array[$i] : NULL,
        '#description' => $this->t("Please enter form id and field id with | separator"),
      ];
    }
    $form['names_fieldset']['actions']             = [
      '#type' => 'actions',
    ];
    $form['names_fieldset']['actions']['add_name'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => "names-fieldset-wrapper",
      ],
    ];
    if ($form_state->get('num_names') > 1) {
      $form['names_fieldset']['actions']['remove_name'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove one'),
        '#submit' => ['::removeCallback'],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => "names-fieldset-wrapper",
        ],
      ];
    }
    $form_state->setCached(FALSE);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['names_fieldset'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_names');
    $add_button = $name_field + 1;
    $form_state->set('num_names', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_names');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('num_names', $remove_button);
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $configArray = [];
    $config      = $this->config('input_reset.inputrestsetting');
    foreach ($form_state->getValue(['names_fieldset']) as $key => $value) {
      if (is_numeric($key)) {
        $config_value = $form_state->getValue([
          'names_fieldset',
          $key,
          'input_reset_field_id',
        ]);
        if (!empty($config_value)) {
          $trim_array          = explode("|", $config_value);
          $better_config_value = trim($trim_array[0]) . "|" . trim($trim_array[1]);
          $configArray[]       = $better_config_value;
        }
      }
    }
    $config->set('field_ids_array', array_unique($configArray));
    $config->save();
  }

}
