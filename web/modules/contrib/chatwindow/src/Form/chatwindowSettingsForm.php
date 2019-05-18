<?php namespace Drupal\chatwindow\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure chatwindow settings for this site.
 */
class chatwindowSettingsForm extends ConfigFormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'chatwindow_admin_settings';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'chatwindow.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('chatwindow.settings');

    $form['botposturl'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Rasa http Url for sending data'),
	  '#description' => $this->t('Rasa server ex :  http://localhost:5004/webhooks/rest/webhook'),
      '#default_value' => $config->get('botposturl'),
	  '#required' => true
    );  

    $form['curlwaittime'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Wait time for post - Curl Time out in seconds'),
      '#description' => $this->t('Defualt wait for Rasa server to reply in seconds - Default 25s'),
      '#default_value' => $config->get('curlwaittime'),
	  '#required' => true
    ); 


    $form['accesstoken'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Access token'),
      '#description' => $this->t('Access token to check for requests comming from rasa'),
      '#default_value' => $config->get('accesstoken'),
	  '#required' => true
    ); 	
	
	
	
	$form['dontaddcssforbot'] = array(
	  '#type' => 'checkbox',
	  '#title' => $this->t('Remove bootstrap 4 css added for bot'),
	  '#description' => $this->t('By checking the check box css will be removed for the bot.'),
	  '#default_value' => $config->get('dontaddcssforbot'),
	);

    return parent::buildForm($form, $form_state);
  }
  
 /**
   * {@inheritdoc}
   */ 
  public function validateForm(array &$form, FormStateInterface $form_state) {
	

	// check if connection to rasa server is working
	$post= json_encode([
				'sender'=> 'test999user',
				'message'=>'hi'
				
			]);

		$url = $form_state->getValue('botposturl');


		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		# Return response instead of printing.
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,3); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 3); 
		# Send request.
		$response = curl_exec($ch);  
		$err = curl_error($ch);
		curl_close($ch);
		if ($err) {    
		  $response = 'failure';    
		}
  
  
  
		if( $response == 'failure')
		{
			$form_state->setErrorByName('CURLPOST_ERROR', $this->t("Couldn't connect to Rasa server"));

		}
		else if( $response == 'success')
		{
			\Drupal::messenger()->addMessage("Connected successfully with Rasa server");		
		  
		}	  



		if(ctype_digit($form_state->getValue('curlwaittime')) === false)
		{
			$form_state->setErrorByName('INTEGER_ERROR', $this->t('Enter a valid integer for Wait time for post'));
			
		}
	
  }
  

	/** 
	* {@inheritdoc}
	*/
  public function submitForm(array &$form, FormStateInterface $form_state) {
      // Retrieve the configuration
       $this->configFactory->getEditable('chatwindow.settings')
      // Set the submitted configuration setting
      ->set('botposturl', $form_state->getValue('botposturl'))
      ->set('curlwaittime', empty($form_state->getValue('curlwaittime'))? 25 : $form_state->getValue('curlwaittime'))
      ->set('accesstoken', $form_state->getValue('accesstoken'))	  
      ->set('dontaddcssforbot', $form_state->getValue('dontaddcssforbot'))	  
      ->save();

    parent::submitForm($form, $form_state);
  } 
}
