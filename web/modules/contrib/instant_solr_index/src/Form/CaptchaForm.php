<?php

namespace Drupal\instant_solr_index\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Entity\Server;
use Drupal\instant_solr_index\Controller\InstantSolrIndexOptionManagedSolrServer;

class CaptchaForm extends FormBase {

  public function getFormId() {
    // Unique ID of the form.
    return 'instant_solr_index_get_captcha_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $site_key = NULL, $token = NULL, $server_type = NULL) {
    $attributes['id'] = "captcha-div-id";
    $attributes['class'] = "g-recaptcha";
    $attributes['data-sitekey'] = $site_key;
    $attributes['data-stoken'] = $token;
    $attributes['data-callback'] = "recaptcha_verify_callback";

    // Adding a div that will contain the Google recaptcha.
    // Recaptcha will be generated according to the site-key
    // and token provided in the attributes.
    $form['captcha-div'] = array(
      "#markup" => '<div ' . new Attribute($attributes) . '></div>',
    );
    $form['server-type'] = array(
      '#type' => 'hidden',
      '#value' => $server_type,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => array('class' => array('submitRecaptcha')),
    );

    $form['#attached']['library'][] = 'instant_solr_index/instant_solr_index-recaptcha';
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate submitted form data.
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle submitted form data.
    $server_type = $form_state->getValue('server-type');
    $managed_solr_server = new InstantSolrIndexOptionManagedSolrServer('gotosolr', $server_type);
    if (isset($_POST['g-recaptcha-response'])) {
      // Following $_POST will receive the recaptcha response of the form
      // from a Trusted Resource(Google).
      $g_recaptcha_response = $_POST['g-recaptcha-response'];
      $respone_obj_after_create_index = $managed_solr_server->callRestCreateSolrIndex($g_recaptcha_response);
      if (isset($respone_obj_after_create_index) && InstantSolrIndexOptionManagedSolrServer::isResponseOk($respone_obj_after_create_index)) {

        self::instant_solr_index_create_searchapi_index($form, $form_state, $respone_obj_after_create_index);
        $form_state->setRedirect(
                'instant_solr_index.searchapi', array('query' => array('servercreated' => 'success'))
        );
      }
      else {
        $form_state->setRedirect(
                'instant_solr_index.searchapi', array('query' => array('gotosolrCustomErrorMsg' => $respone_obj_after_create_index->status->message))
        );
      }
    }
  }
  
  /**
   * Creating searchapi server on drupal.
   */
  public function instant_solr_index_create_searchapi_index($form, $form_state, $response_object) {
    $response_values = $response_object->results[0][0];
    $username_pass = explode(':', $response_values->uuid);
    $values = array(
        'clean_ids' => 1,
        'uuid' => $response_values->uuid,
        'urlCore' => $response_values->urlCore,
        'scheme' => $response_values->urlScheme,
        'host' => $response_values->urlDomain,
        'port' => $response_values->urlPort,
        'path' => '/' . $response_values->urlPath . '/' . $response_values->urlCore,
        'class' => 'search_api_solr_service',
        'key' => $response_values->key,
        'secret' => $response_values->secret,
        'http_user' => $username_pass[0],
        'http_pass' => $username_pass[1],
        'database' => 'default:default'
    );
    $index = Server::create([
              'id' => 'gotosolr_searchapi_server',
              'name' => 'Test index hosted by gotosolr.com',
              'uuid' => $response_values->uuid,
              'backend' => 'search_api_db',
              'backend_config' => $values
    ]);
    $index->save();
    \Drupal::getContainer()->get('config.factory')->getEditable('instant_solr_index.settings')->set('instant_solr_index_searchApiServer_id','gotosolr_searchapi_server')->save();
  }
}
