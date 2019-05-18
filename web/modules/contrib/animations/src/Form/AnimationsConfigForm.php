<?php
 
namespace Drupal\animations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class AnimationsConfigForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
	return "animations_config_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
	// check dependencies

  $requirements = array();
	$config = \Drupal::config('animations.config');

	foreach($config->get("animations_dependencies") as $key => $library){
		$fileExists = file_exists(DRUPAL_ROOT . '/libraries/'.$key.'/'.$library["file"]);
		if(!$fileExists){
			$requirements[$key] = array(
				'title' => t($library["name"].' library')
			);
			$requirements[$key]['description'] = t('Please use command "drush dal" to download dependencies or manually download the repository '.$library["repository"].' into the libraries folder to define the following structure [DRUPAL_ROOT]/libraries/'.$key.'/'.$library["file"]);
			$requirements[$key]['severity'] = REQUIREMENT_ERROR;
		}
	}
	
	if(count($requirements) > 0){
	    $form['errors'] = [
		  '#type' => 'table',
		  '#header' => [t('Missing library'), t('Actions')],
		  '#rows' => [
		  ],
		];
		
		foreach($requirements as $requirement){
			$form['errors']["#rows"][] = array($requirement["title"],$requirement["description"]);
		}
		print_r($form['errors']["rows"]);
		return $form;
	}
  
  
  
  
  
	  
	  
	  
	// get the module configuration
    $config = $this->config('animations.config');
	
	
	$form['topsubmit'] = array(
      '#type' => 'submit',
      '#value' => t('Save changes'),
    );
	
	
	$form['examples'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Examples'),
    ];

    $form['examples']['content'] = [
      '#type' => 'table',
      '#header' => [t('CSS selector'), t('Description')],
      '#rows' => [
        [
          'input, textarea',
          t('Use all single line text fields and textareas on site.'),
        ],
        [
          '.your-form-class *',
          t('Use all text fields in given form class.'),
        ],
        [
          '#your-form-id *',
          t('Use all text fields in given form id.'),
        ],
        [
          '#your-form-id *:not(textarea)',
          t('Use all single line text fields but not textareas in given form id.'),
        ],
        [
          '#your-form-id input:not(input[type=password])',
          t('Use all single line text fields but not password text fields in given form id.'),
        ],
      ],
    ];
	
	// add collapsible sections for all effects by cycling the config file and adding one textarea for each key
	foreach($config->get('animations') as $key => $effect){

			$title = t(ucfirst($key).' effect' );
			if(isset($effect["name"])) {
				$title = t($effect["name"]);
			}
			
			$desc = $this->t('CSS selectors');
			if(isset($effect["desc"])) {
				$desc = t($effect["desc"]);
			}
			
			$form['group'.$key] = array(
			  '#type' => 'details',
			  '#title' => $title,
			  '#collapsible' => TRUE,
			  '#collapsed' => TRUE,  
			);
			$form['group'.$key][$key] = array(
		 
			  '#type' => 'textarea',
				
			  '#title' => $desc,
			   // implode the config values array into multi line string for displaying in the textarea
			  '#default_value' => implode("\n",$effect['classes'])
		 
			);
	}
 
	$form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save changes'),
    );
 
	return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
	// TODO: validate the css selectors before saving
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
	// get editibale instance of the module config
	$config = \Drupal::service('config.factory')->getEditable('animations.config');
	
	// loop and save the new values in the module config
	foreach($form_state->getValues() as $key => $value){
		// check if the field name is present in the config - because not all submitted fields are css selectors
		if(is_array($config->get('animations.'.$key.'.classes'))){
			// explode the input string back to array
			$config->set('animations.'.$key.'.classes', explode("\n",$form_state->getValue($key)));
		}
			
		
	}
	$config->save();
 
  }
}
?>