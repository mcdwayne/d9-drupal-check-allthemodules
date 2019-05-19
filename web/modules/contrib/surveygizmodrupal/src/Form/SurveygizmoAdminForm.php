<?php

/**
 * @file  
 * Contains Drupal\surveygizmodrupal\Form\SurveygizmoAdminForm.  
 */

namespace Drupal\surveygizmodrupal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SurveygizmoAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}  
   */
  protected function getEditableConfigNames() {
    return ['surveygizmodrupal.adminsettings'];
  }

  /**
   * {@inheritdoc}  
   */
  public function getFormId() {
    return 'surveygizmodrupal_admin_form';
  }

  /**
   * {@inheritdoc}  
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('surveygizmodrupal.adminsettings');

    $form['SG_API_KEY'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Surveygizmo API KEY'),
      '#description' => $this->t('Please enter surveygizmo api key'),
      '#default_value' => $config->get('SG_API_KEY'),
      '#required' => TRUE,
    ];

    $form['SG_API_SECRET'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Surveygizmo API SECRET'),
      '#description' => $this->t('Please enter surveygizmo api key'),
      '#default_value' => $config->get('SG_API_SECRET'),
      '#required' => TRUE,
    ];

    if ($config->get('SG_DATA_LIMIT')) {
      $value = $config->get('SG_DATA_LIMIT');
    }
    else {
      $value = 10;
    }

    $form['SG_DATA_LIMIT'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Surveygizmo API Question'),
      '#description' => $this->t('Please enter number of record fetch'),
      '#default_value' => $value,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}  
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('surveygizmodrupal.adminsettings')
      ->set('SG_API_KEY', $form_state->getValue('SG_API_KEY'))
      ->set('SG_API_SECRET', $form_state->getValue('SG_API_SECRET'))
      ->set('SG_DATA_LIMIT', $form_state->getValue('SG_DATA_LIMIT'))
      ->save();
  }
}
