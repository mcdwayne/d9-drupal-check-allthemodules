<?php
/**
 * @file
 * Contains Drupal\bontact\Form\BontactSettingsForm.
 */

namespace Drupal\bontact\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Bontact Setting form.
 */
class BontactSettingsForm extends ConfigFormBase {
	
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
  	return 'bontact_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
  	$form = parent::buildForm($form, $form_state);
  	
  	$config = $this->config('bontact.settings');
  	
  	$form['bontact_customer'] = array(
				'#type' => 'textfield',
				'#title' => t('Bontact Customer ID'),
				'#description' => t('Unique Customer ID from your Bontact Dashboard. Use the one defined with \'var bontactCustomer\' in the javascript code, under Setting > Code embed '),
				'#default_value' => $config->get('bontact_customer'),
		);
		
		$form['signup'] = array(
				'#markup' => t('If you do not have an acount with Bontact, <a href="http://bit.ly/1OWETuG">Sign up</a>'),
				'#weight' => -50,
		);
  	
		return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  
  	if ($form_state->getValue('bontact_customer') == '' ) {
  		$form_state->setErrorByName('bontact_customer', t('Bontact Customer ID is required.'));
  	}
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  	$values = $form_state->getValues();
  	
  	$this->config('bontact.settings')
  	->set('bontact_customer', $values['bontact_customer'])
  	->save();
  	drupal_set_message('The configuration options have been saved.');
  }
  
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
  	return [
  	'bontact.settings',
  	];
  }
}
?>