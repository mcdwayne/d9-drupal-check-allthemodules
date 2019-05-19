<?php

/**
 * @file
 * Contains \Drupal\userqueue\Form\UserQueueViewForm.
 */

namespace Drupal\userqueue\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;


/**
 * Implements an example form.
 */
class UserQueueViewForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'userqueue_view';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $nid = NULL) {

    $form['userqueue']['username'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',	
      '#prefix' => '<div class="container-inline">',
      '#maxlength' => 60,
      '#default_value' => '',
    );

    $form['userqueue']['add_user'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add User'),
      '#suffix' => '</div>',
    );

    return $form;
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

                 $current_path = \Drupal::service('path.current')->getPath();
	    	 $path_args = explode('/', $current_path);	   
	
			   
		
		$queryn = db_select('userqueue_user', 'uu');
		$queryn->addExpression('MAX(weight)', 'weight');
		$position = $queryn->condition('uqid',$path_args[4],'=');
  		$position = $queryn->execute()->fetchField();

		
		
		db_insert('userqueue_user')
		->fields(array(
		'uqid' => $path_args[4],
		'uid' => $form_state->getValue('username'),
		'weight' => $position+1,		 
		))
		->execute(); 

    
  }
}
