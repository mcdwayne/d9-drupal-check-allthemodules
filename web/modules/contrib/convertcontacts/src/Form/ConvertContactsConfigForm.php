<?php

namespace Drupal\convertcontacts\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConvertContactsConfigForm.
 *
 * @package Drupal\convertcontacts\Form
 */
class ConvertContactsConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'convertcontacts.convertcontactsconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'convertcontacts_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('convertcontacts.convertcontactsconfig');
    $form['convertcontacts_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site ID'),
      '#description' => t('This Site ID is unique to each site you want to track separately, and is in the form of XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX. <p>Need help finding your ConvertContacts Site ID?</p> <ol><li>Sign into <a href="@convertcontacts_site">ConvertContacts</a>.</li><li>Navigate to Settings tab, and click on \'Tracking Code\'.</li><li>Copy the Tracking Code ID out of your tracking code snippet. It should look something like: d4098273-6c87-4672-9f5e-94bcabf5597a <strong>Note:</strong> Do not use the example tracking code id as it will not work properly.</li></ol><p>If you have difficulty with this step or cannot find your Tracking ID, please contact your ConvertContacts account representative.</p>',
        array(
          '@convertcontacts_site' => 'http://reachedge.reachlocal.com/',
        )),      
      '#maxlength' => 36,
      '#size' => 36,
      '#required' => TRUE,      
      '#default_value' => ($config->get('convertcontacts_id') ?? 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX'),
    ];
    return parent::buildForm($form, $form_state);
  } 

  /**
   * {@inheritdoc}
   */
 
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (!preg_match('/^[A-Z0-9]{8}(-[A-Z0-9]{4}){3}-[A-Z0-9]{12}$/i', $form_state->getValue('convertcontacts_id'))) {
      $form_state->setErrorByName('convertcontacts_id', $this->t('A valid ConvertContacts Site ID is formatted like XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {     
    $config = $this->config('convertcontacts.convertcontactsconfig');  
    $config->set('convertcontacts_id', $form_state->getValue('convertcontacts_id')); 
    $config->save(); 
    parent::submitForm($form, $form_state);
  }

}

