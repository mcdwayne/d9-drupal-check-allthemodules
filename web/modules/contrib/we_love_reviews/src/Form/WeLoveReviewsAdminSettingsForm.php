<?php

/**
 * @file
 * Contains \Drupal\we_love_reviews\Form\WeLoveReviewsAdminSettingsForm.
 */

namespace Drupal\we_love_reviews\Form;

# Symfony Defaults
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

# We use below for handle Gizzle requests 
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use \GuzzleHttp\Exception\RequestException;



class WeLoveReviewsAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
 public function getFormId() {
    return 'we_love_reviews_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */

 public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('we_love_reviews.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */

 protected function getEditableConfigNames() {
    return ['we_love_reviews.settings'];
  }

 public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $config = $this->config('we_love_reviews.settings');

    $form['apikey'] = [
       '#prefix' => t('<strong>What is the We ♥ Reviews Drupal module about?</strong><br> The Module NOT ONLY adds schema.org LocalBusiness contact info markup to the head section of each of your Drupal pages in JSON/LD format, BUT IT INCLUDES the up-to-date overall rating value and the total number of reviews as gathered through the ReputationCRM.com API. After configuration, you can check the schema markup on any page of your Drupal site by using the <a href="@google-testing-tool" title="Google Structured Data Testing Tool" target="_blank" />Google Structured Data Testing Tool</a>.<br><br><strong>Want more customer feedback for your business?</strong> For more info, head over to <a href="@reputation-aegis" title="Reputation Aegis" target="_blank" />Reputation Aegis</a>.',
      	 [
           '@google-testing-tool' => 'https://search.google.com/structured-data/testing-tool',
           '@reputation-aegis' => 'https://reputationaegis.com/',           
      	 ]), 
      '#title' => t('API Key'),
      '#type' => 'textfield',
      '#default_value' => $config->get('apikey'),
      '#size' => 60,
      '#required' => TRUE,
       '#description' => t('<a target="_blank" href="@reputationcrm-apikey">Click here</a> to get your API Key.', 
        [ 
         '@reputationcrm-apikey' => 'https://reputationcrm.com/settings/index/Reputation-Builder#api_server',
        ]),
    ];

    $form['companyid'] = [
      '#title' => t('Company / Location ID'),
      '#type' => 'textfield',
      '#default_value' => $config->get('companyid'),
      '#size' => 30,
      '#maxlength' => 30,
      '#required' => TRUE,
      '#description' => t('<a target="_blank" href="@reputationcrm-companyid">Click here</a> to view the datatable with all Companies / Locations.',
      	[
      	  '@reputationcrm-companyid' => 'https://reputationcrm.com/companies-locations',
        ]),
    ];

    $form['actions']['rating_check'] = [
      '#type' => 'submit',
      '#default_value' => t('Check Overall Rating'),
      '#submit' => array([$this, 'validateForm'], [$this, 'ratingCheck']),
      '#weight' => 100,
    ];
  return parent::buildForm($form, $form_state);
  }


 public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

    /** API: https://reputationcrm.com/developers/documentation/v2/explorer
     *   Example call:
     *   https://reputationcrm.com/v2/getOverallRatingAndReviewCountForPlatformReviewsByCompanyId?companyid=COMPANYID&key=APIKEY
     */
 
    $key = $form_state->getValue('apikey');
    $cid = $form_state->getValue('companyid');

    $client = \Drupal::httpClient();
    $url = 'https://reputationcrm.com/v2/getOverallRatingAndReviewCountForPlatformReviewsByCompanyId?companyid='
    .$cid.'&key='.$key;
    $method = 'GET';
    $options = [
   'http_errors' => FALSE,
    ];

    $response = $client->request($method, $url, $options);
 
    if($response) {
 
    $code = $response->getStatusCode();
    $data = $response->getBody(); 
 
  if ($code != '200') {
      drupal_set_message(t('Error - Server response: ') .  $data);
    } 

  } else {
 	   drupal_set_message(t('No response from the ReputationCRM.com API. Please retry!'));
  }
  return $response;
 }


/*
 * Rating check
*/
public function ratingCheck($form, $form_state) {

  
   $response = $this->validateForm($form, $form_state);
   if($response) {

   $response_formatted = explode(',', $response->getBody()); 
   
   $searchr = array('{"', '":' );  
   $replacer = array('<strong>', '</strong> :  ');  
   $overal = str_replace($searchr, $replacer, $response_formatted[0]);  

   $searchk = array('"R', '":', '}' );  
   $replacek = array('<strong>R', '</strong> :  ', '');  
   $rcount = str_replace($searchk, $replacek, $response_formatted[1]);
  
   $ratingcheck = '';
   $ratingcheck .= t('<h2>Values returned by the ReputationCRM.com API:</h2>');
   $ratingcheck .= $overal . '<br>';
   $ratingcheck .= $rcount;

   drupal_set_message(array(
        '#markup' => $ratingcheck,
        '#type' => 'info',
        '#safe' => TRUE, 
     ));
  
    } else {
  return t('No response from the ReputationCRM.com API. Please retry!');
  }
 } 
}

?>