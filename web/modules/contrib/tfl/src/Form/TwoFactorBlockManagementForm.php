<?php

namespace Drupal\tfl\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;


/**
 * {@inheritdoc}
 */
class TwoFactorBlockManagementForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tfl_block_management_form';
  }

  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config( 'tfl.settings' );

    $form['block'] = [
      '#type' => 'details',
      '#title' => $this->t( 'Add or Remove from block list' ),
      '#open' => TRUE,
    ];
    
     $form['block']['output'] = array(
      '#prefix' => '<div id="api-output-wrapper">',
      '#suffix' => '</div>',
    );
    
    $form['block']['phone'] = [
        '#type' => 'tel',
        '#title' => $this->t( 'Phone number' ),
        '#size' => 60,
        '#description' => $this->t( "Enter user's phone number." ),
        '#required' => TRUE,
        '#attributes' => [
                'id' => array('phone-number'),
             ],
      ];
    
    $form['block']['actions']['blocked'] = [
        '#type' => 'submit',
        '#value' => $this->t( 'Blocked' ),
        '#ajax' => array(
            'callback' => '\Drupal\tfl\Form\TwoFactorBlockManagementForm::tflBlockedNumberSubmit',
            'wrapper' => 'api_output_wrapper',
            'event' => 'click',
            'progress' => [
                'type' => 'throbber',
                'message' => t('Please wait...'),
              ],
           ),
      ];
    
    $form['block']['actions']['unblocked'] = [
        '#type' => 'submit',
        '#value' => $this->t( 'Un-Blocked' ),
        '#ajax' => array(
        'callback' => '\Drupal\tfl\Form\TwoFactorBlockManagementForm::tflUnBlockedNumberSubmit',
            'wrapper' => 'api-output-wrapper',
            'event' => 'click',
            'progress' => [
                'type' => 'throbber',
                'message' => t('Please wait...'),
              ],
           ),
      ];
    
    
    
    return $form;
  }
  
  
  public function tflBlockedNumberSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if (empty($form_state->getValue('phone'))) {
      $response->addCommand(new \Drupal\Core\Ajax\HtmlCommand('#api-output-wrapper', $form_state->getValue('phone')."Please enter phone number."));
    }else{
     $response->addCommand(new ReplaceCommand('#api-output-wrapper', "Blocked"));
    }
    return $response;
  }
  
  public function tflUnBlockedNumberSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if (empty($form_state->getValue('phone'))) {
      $response->addCommand(new \Drupal\Core\Ajax\HtmlCommand('#api-output-wrapper', "Please enter phone number."));
    }else{
     $response->addCommand(new \Drupal\Core\Ajax\HtmlCommand('#api-output-wrapper', "Un-Blocked "));
    }
    return $response;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    parent::submitForm( $form, $form_state );
  }


}
