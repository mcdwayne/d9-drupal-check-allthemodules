<?php

/**
* @file
* Contains \Drupal\no_follow\Form\PathNofollowForm
*/

namespace Drupal\path_nofollow\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PathNofollowForm extends ConfigFormBase {

	/**
	* {@inheridoc}
	*/
	public function getFormId(){
		return 'path_nofollow_ form';
	}
	
	/**
	* {@inheridoc}
	*/
	public function buildForm(array $form, FormStateInterface $form_state){

		$config = $this->config('path_nofollow.settings');

		$form['path_nofollow_textarea'] = array(
      		'#type' => 'textarea',
      		'#title' => 'Hide for search engines',
      		'#description' => 'Enter paths (one per line) to exclude from search engine indexing. Wildcards are allowed. (ie: admin/*)',
      		'#default_value' => $config->get(PATH_NOFOLLOW_PATH_MATCH),
  		);
  		return parent::buildForm($form, $form_state);
	}

   /**
    * {@inheritdoc}
    */
   protected function getEditableConfigNames() {
     return array('system.site');
   }

	/*
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state) {

		parent::submitForm($form, $form_state);
		//Save textarea edited.
		$config = \Drupal::service('config.factory')->getEditable('path_nofollow.settings');
		$config->set(PATH_NOFOLLOW_PATH_MATCH, $form_state->getValue('path_nofollow_textarea'))->save();

	}

}