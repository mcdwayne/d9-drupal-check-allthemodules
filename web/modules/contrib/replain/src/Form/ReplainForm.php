<?php
 
/**
 * @file
 */
 
namespace Drupal\replain\Form;
 
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
 
class ReplainForm extends FormBase {
 
	/**
	* {@inheritdoc}.
	*/
	public function getFormId() {
		return 'replain_form';
	}

	/**
	* {@inheritdoc}.
	*/
	public function buildForm(array $form, FormStateInterface $form_state) {

		$token = \Drupal::state()->get('replain_token');
		$status = \Drupal::state()->get('replain_status');
		$jsCode = '<script>var __REPLAIN_ = \''.$token.'\';(function(u){var s=document.createElement(\'script\');s.type=\'text/javascript\';s.async=true;s.src=u;var x=document.getElementsByTagName(\'script\')[0];x.parentNode.insertBefore(s,x);})(\'https://widget.replain.cc/dist/client.js\');</script>';
		$jsCode = stripslashes( $jsCode );
		
		if ( empty($token) ) {
			$form['helper'] = array(
				'#type' => 'item',
				'#markup' => '<p>' . t('To connect Re:plain you need to get the Re:plain code. To do this, follow the link below.') . '</p><p><a class="replain-bot-link" href="https://replain.cc/" target="_blank">' . t('Get the Re:plain code') . '</a></p>',
			);				
		} else {
			$form['is_replain'] = array(
				'#title'         => t('Chat is'),
				'#type'          => 'select',
				'#default_value' => $status ? $status : 2,
				'#options' => array(
				  1 => t('Enabled'),
				  2 => t('Disabled'),
				),
			);				
		}

		$form['replain_code'] = array(
			'#title'         => t('Enter your Re:plain code'),
			'#type'          => 'textarea',
			'#default_value' => $token ? $jsCode : '',
		);	

		$form['actions']['#type'] = 'actions';

		$form['actions']['submit'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Update Settings'),
			'#button_type' => 'primary',
		);
	return $form;
	}

	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {
		
		preg_match( '/REPLAIN_\s*=\s*\W(.*?)\W;/', stripslashes( $form_state->getValue('replain_code') ), $matches );

		if ( !empty( $matches ) && isset( $matches[1] ) ) {
			$token = $matches[1];
		} else {
			preg_match( '/id\s*:\s*\W(.*?)\W{2}/', stripslashes( $form_state->getValue('replain_code') ), $matches );

			if ( !empty( $matches ) && isset( $matches[1] ) ) {
				$token = $matches[1];
			} else {
				$token = '';
			}
		}
		
		$status = $form_state->getValue('is_replain');

		\Drupal::state()->set('replain_status', $status);	
		\Drupal::state()->set('replain_token', $token);		
		
		drupal_set_message($this->t('Data saved successfully'));
	}
}