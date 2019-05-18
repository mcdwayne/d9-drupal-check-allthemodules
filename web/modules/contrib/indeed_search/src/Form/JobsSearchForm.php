<?php

namespace Drupal\indeed_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements a Jobs Search Form.
 */
class JobsSearchForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'jobssearch';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $form['search_keyword'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Keyword'),
      '#default_value' => '',
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => FALSE,
      '#placeholder'=>'eg B.Tech,Drupal etc.'	
    ];

    $form['search_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Location'),
      '#default_value' => '',
      '#size' => 30,
      '#maxlength' => 128,
      '#required' => FALSE,
      '#placeholder'=>'eg Delhi,Gurgaon etc'	
    ];
    
  
    $form['sort_by'] = [
      '#type' => 'radios',
      '#title' => $this->t('Sort By'),
      '#options'=> ['date' => $this->t('Date'),'relevance' => $this->t('Relevance')],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search Jobs'),
    ];

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

    $keyword = $form_state->getValue('search_keyword');
    if($keyword == NULL)
	    $keyword = " ";
  
 
    $location = $form_state->getValue('search_location');
    if($location == NULL )
	    $location = " ";

    $sortby = $form_state->getValue('sort_by');
    if($sortby == NULL)
	    $sortby = " ";

    $form_state->setRedirect('indeed_search.content_k.keyword.location.sortby',['keyword'=>$keyword,'location'=>$location,'sortby'=>$sortby]);

  }

}
