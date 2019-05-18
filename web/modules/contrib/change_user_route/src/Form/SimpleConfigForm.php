<?php
 
/**
 
 * @file
 
 * Contains \Drupal\simple\Form\SimpleConfigForm.
 
 */
 
namespace Drupal\change_user_route\Form;
 
use Drupal\Core\Form\ConfigFormBase;
 
use Drupal\Core\Form\FormStateInterface;
 
class SimpleConfigForm extends ConfigFormBase {
 
  /**
 
   * {@inheritdoc}
 
   */
 
  public function getFormId() {
 
    return 'simple_config_form';
 
  }
 
  /**
 
   * {@inheritdoc}
 
   */
 
  public function buildForm(array $form, FormStateInterface $form_state) {
 
    $form = parent::buildForm($form, $form_state);
 
    $config = $this->config('simple.settings');
 
   $form['login_route'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Set Login Route'),
      '#default_value' => $config->get('login_route'),
    );  

    $form['pass_route'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Set Password Route'),
      '#default_value' => $config->get('pass_route'),
    );  
	
	 $form['regi_route'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Set Registration  Route'),
      '#default_value' => $config->get('regi_route'),
    ); 
	 $form['logout_route'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Set Logout Route'),
      '#default_value' => $config->get('logout_route'),
    ); 
    return $form;
 
  }
 
  /**
 
   * {@inheritdoc}
 
   */
 
  public function submitForm(array &$form, FormStateInterface $form_state) {
 
		$config = $this->config('simple.settings');
		$config->set('login_route', $form_state->getValue('login_route'));
		$config->set('pass_route', $form_state->getValue('pass_route'));
		$config->set('regi_route', $form_state->getValue('regi_route'));
		$config->set('logout_route', $form_state->getValue('logout_route'));
 
    $config->save();
	
	
 
    return parent::submitForm($form, $form_state);
 
  }
 
  /**
 
   * {@inheritdoc}
 
   */
 
  protected function getEditableConfigNames() {
 
    return [
 
      'simple.settings',
 
    ];
 
  }
 
}