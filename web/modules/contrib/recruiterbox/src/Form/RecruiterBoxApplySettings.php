<?php

namespace Drupal\recruiterbox\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RecruiterBoxApplySettings.
 *
 * @package Drupal\recruiterbox\Form
 */
class RecruiterBoxApplySettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'recruiterbox.recruiterboxapplysettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recruiter_box_apply_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('recruiterbox.recruiterboxapplysettings');
    
    $num_recruiterbox_form = $form_state->get('num_recruiterbox_form');
    $form['#tree'] = TRUE;

    $form['recruiterbox_form_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Recruiter Box form settings'),
      '#prefix' => "<div id='recruiterbox-form-fieldset-wrapper'>",
      '#suffix' => '</div>',
    ];

    if (empty($num_recruiterbox_form)) {
      $recruiterbox_form_fieldset_count = $config->get('recruiterbox_form_fieldset_count');
      if ($recruiterbox_form_fieldset_count) {
        $num_recruiterbox_form = $form_state->set('num_recruiterbox_form', $recruiterbox_form_fieldset_count);
      }
      else {
        $num_recruiterbox_form = $form_state->set('num_recruiterbox_form', 1);
      }
    }

    for ($i = 0; $i < $form_state->get('num_recruiterbox_form'); $i++) {
      $form_count = $i + 1;
      $form_arr = $config->get($form_count);
      $drupal_form_id = is_array($form_arr) ? key($form_arr) : NULL;
      $form['recruiterbox_form_fieldset'][$i]['recruiter_box_opening_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Recruiter Box Opening ID'),
        '#description' => $this->t('Recruiter Box Opening ID. It should be integer.'),
        '#maxlength' => 50,
        '#size' => 50,
        '#default_value' => $config->get($form_count . '.' . $drupal_form_id . '.' . 'recruiter_box_opening_id'),
        '#prefix' => "<div class='inner-fieldset'><legend><span class='fieldset-legend'>{$this->t('Recruiter Box form:')} {$form_count}</span></legend>",
      ];
      $form['recruiterbox_form_fieldset'][$i]['drupal_form_id'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Drupal Form&#039;s ID'),
        '#description' => $this->t('Drupal Form&#039;s ID.'),
        '#maxlength' => 64,
        '#size' => 64,
        '#default_value' => $config->get($form_count . '.' . $drupal_form_id . '.' . 'drupal_form_id'),
      ];
      $form['recruiterbox_form_fieldset'][$i]['initial_forms_field_mapping'] = [
        '#type' => 'textarea',
        '#required' => TRUE,
        '#title' => $this->t('Initial fields mapping'),
        '#description' => $this->t('Map Recruiter Box initial fields machine name with Drupal&#039;s form fields machine name .'),
        '#default_value' => $config->get($form_count . '.' . $drupal_form_id . '.' . 'initial_forms_field_mapping'),
      ];
      $form['recruiterbox_form_fieldset'][$i]['profile_forms_field_mapping'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Profile fields mapping'),
        '#description' => $this->t('Map Recruiter Box profile fields machine name with Drupal&#039;s form fields machine name .'),
        '#default_value' => $config->get($form_count . '.' . $drupal_form_id . '.' . 'profile_forms_field_mapping'),
        '#suffix' => '</div>',
      ];
    }

    $form['recruiterbox_form_fieldset']['actions'] = [
      '#type' => 'actions',
    ];
    $form['recruiterbox_form_fieldset']['actions']['add_name'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add one more'),
      '#submit' => array('::addOne'),
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => "recruiterbox-form-fieldset-wrapper",
      ],
    ];
    if ($form_state->get('num_recruiterbox_form') > 1) {
      $form['recruiterbox_form_fieldset']['actions']['remove_name'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove one'),
        '#submit' => array('::removeCallback'),
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => "recruiterbox-form-fieldset-wrapper",
        ],
      ];
    }
    $form_state->setCached(FALSE);

    return parent::buildForm($form, $form_state);
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
    parent::submitForm($form, $form_state);

    $config_obj = $this->config('recruiterbox.recruiterboxapplysettings');
    $config_obj->delete();
    $recruiterbox_form_fieldset_count = 0;
    foreach ($form_state->getValue(array('recruiterbox_form_fieldset')) as $key => $value) {
      if (is_numeric($key)) {
        $drupal_form_id = $form_state->getValue(array('recruiterbox_form_fieldset', $key, 'drupal_form_id'));
        $config_obj->set(($recruiterbox_form_fieldset_count + 1) . '.' . $drupal_form_id . '.' . 'initial_forms_field_mapping', $form_state->getValue(array('recruiterbox_form_fieldset', $key, 'initial_forms_field_mapping')))
            ->set(($recruiterbox_form_fieldset_count + 1) . '.' . $drupal_form_id . '.' . 'profile_forms_field_mapping', $form_state->getValue(array('recruiterbox_form_fieldset', $key, 'profile_forms_field_mapping')))
            ->set(($recruiterbox_form_fieldset_count + 1) . '.' . $drupal_form_id . '.' . 'recruiter_box_opening_id', $form_state->getValue(array('recruiterbox_form_fieldset', $key, 'recruiter_box_opening_id')))
            ->set(($recruiterbox_form_fieldset_count + 1) . '.' . $drupal_form_id . '.' . 'drupal_form_id', $form_state->getValue(array('recruiterbox_form_fieldset', $key, 'drupal_form_id')))
            ->save();
        $recruiterbox_form_fieldset_count++;
      }
    }
    if ($recruiterbox_form_fieldset_count) {
      $config_obj->set('recruiterbox_form_fieldset_count', $recruiterbox_form_fieldset_count)
          ->save();
    }
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    $num_recruiterbox_form = $form_state->get('num_recruiterbox_form');
    return $form['recruiterbox_form_fieldset'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $num_recruiterbox_form = $form_state->get('num_recruiterbox_form');
    $add_button = $num_recruiterbox_form + 1;
    $form_state->set('num_recruiterbox_form', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $num_recruiterbox_form = $form_state->get('num_recruiterbox_form');
    if ($num_recruiterbox_form > 1) {
      $remove_button = $num_recruiterbox_form - 1;
      $form_state->set('num_recruiterbox_form', $remove_button);
    }
    $form_state->setRebuild();
  }

}
