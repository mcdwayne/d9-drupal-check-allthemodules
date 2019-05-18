<?php
namespace Drupal\mktcnnt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Implements an MarketoConnect form.
 */
class MarketoConnectForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mktcnnt_variables_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mktcnnt.mktcnnt_variables',
      'mktcnnt.mktcnnt_roles',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $configRoles = $this->config('mktcnnt.mktcnnt_roles');
    $configConnect = $this->config('mktcnnt.mktcnnt_variables');
    
	$role_objects = Role::loadMultiple();
	$system_roles = array_combine(array_keys($role_objects), array_map(function($a){ return $a->label();}, $role_objects));
	
	$form['marketoSoapEndPoint'] = [  
      '#type' => 'textfield',  
      '#title' => $this->t('End Point'),  
      '#description' => $this->t('Marketo SOAP End Point'),  
      '#default_value' => $configConnect->get('marketoSoapEndPoint'),  
    ];
	
	$form['marketoUserId'] = [  
      '#type' => 'textfield',  
      '#title' => $this->t('SOAP user id'),  
      '#description' => $this->t('Marketo User Id'),  
      '#default_value' => $configConnect->get('marketoUserId'),  
    ];
	
	$form['marketoSecretKey'] = [  
      '#type' => 'textfield',  
      '#title' => $this->t('Secret Key'),  
      '#description' => $this->t('Marketo Secret Key'),  
      '#default_value' => $configConnect->get('marketoSecretKey'),  
    ];
	
	$form['marketoNameSpace'] = [  
      '#type' => 'textfield',  
      '#title' => $this->t('Name Space'),  
      '#description' => $this->t('Marketo Name Space'),  
      '#default_value' => $configConnect->get('marketoNameSpace'),  
    ];	
	
	$form['button'] = [
		'#type' => 'button',
		'#value' => $this->t('Test Connection'),
		'#ajax' => [
			'callback' => [$this, '_mkt_reading_config_connect'],
			'event' => 'click',
		],
	];
	
	$form['api_token_verfiy_msg'] = [
      '#markup' => '<div class="token_verfiy_msg"></div>',
      '#allowed_tags' => ['div']
    ];
	
	$form['UserRoles'] = [
		'#type' => 'checkboxes',
		'#options' => $system_roles,
		'#title' => $this->t('Which role(s) you would  like to sync with Marketo at time of registration?'),
		'#default_value' => $configRoles->get('UserRoles'),  
	];
    
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save config.
    $this->config('mktcnnt.mktcnnt_variables')
      ->set('marketoSoapEndPoint', $form_state->getValue('marketoSoapEndPoint'))
      ->set('marketoUserId', $form_state->getValue('marketoUserId'))
      ->set('marketoSecretKey', $form_state->getValue('marketoSecretKey'))
      ->set('marketoNameSpace', $form_state->getValue('marketoNameSpace'))
      ->save();
	  
    $this->config('mktcnnt.mktcnnt_roles')
      ->set('UserRoles', $form_state->getValue('UserRoles'))
      ->save();

    parent::submitForm($form, $form_state);
  }
  
  public function _mkt_reading_config_connect(array &$form, FormStateInterface $form_state){
	$marketo_object = new \Drupal\mktcnnt\Controller\MarketoAPIController();
	$marketo_object->marketoSoapEndPoint = \Drupal::request()->get('marketoSoapEndPoint');
	$marketo_object->marketoUserId = \Drupal::request()->get('marketoUserId');
	$marketo_object->marketoSecretKey = \Drupal::request()->get('marketoSecretKey');
	$marketo_object->marketoNameSpace = \Drupal::request()->get('marketoNameSpace');
	
	$loading = $marketo_object->load();
	if($loading === true){
		$marketo_object->testConnection();
		
		$leadResponse = $marketo_object->testConnection();
		
		if(!$leadResponse[0]){
			$token = ['valid' => 0, 'message' => $leadResponse[1]];
		}else{
			$token = ['valid' => 1, 'message' => 'Connected Successfully.'];
		}
	}else{
		$token = ['valid' => 0, 'message' => $loading];
	}

    if ($token['valid'] == 1) {
      $error_class = "messages--status";
    }
    else {
      $error_class = "messages--error";
    }
	
	$response = new AjaxResponse();
	$response->addCommand(new HtmlCommand('.token_verfiy_msg', '<div class="messages '.$error_class.'">' . $token['message'] . '</div>'));
    return $response;
  }
}