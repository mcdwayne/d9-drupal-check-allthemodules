<?php

/**
 * @file
 * Contains \Drupal\canvas_api\Form\ModuleConfigurationForm.
 */

namespace Drupal\canvas_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form to configure the settings for the Canvas integration.
 */
class CanvasSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'canvas_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'canvas_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('canvas_api.settings');
    
    $form['institution'] = array(
      '#type' => 'textfield',
      '#title' => 'Institution',
      '#description' => 'Enter your institution\'s name as it appears in your Canvas URL. <br>i.e. https://<strong>your-institutions-name</strong>.instructure.com',
      '#default_value' => $config->get('institution'),
    );

    $form['environment'] = array(
      '#type' => 'radios',
      '#title' => 'Environment',
      '#options' => array(
        'test' => 'Test',
        'beta' => 'Beta',
        'production' => 'Production',
      ),
      '#default_value' => $config->get('environment'),
    );
    $form['token'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Token'),
      '#description' => 'Enter you Canvas access token.',
      '#default_value' => $config->get('token'),
    ); 
    
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    foreach($values as $key => $value){
      $this->config('canvas_api.settings')
        ->set($key, $value)
        ->save();        
    }
    $message = $this->t('Your configuration has been saved.');
    drupal_set_message($message);
  }
}