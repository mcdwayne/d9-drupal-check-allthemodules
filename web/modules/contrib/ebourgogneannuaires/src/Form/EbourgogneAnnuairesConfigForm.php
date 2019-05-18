<?php

namespace Drupal\ebourgogneannuaires\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

/**
* Define the constants used by the module.
*/
define('EBOU_ANNU_SERVICE_BASE_URL', \Drupal::config('ebourgogneannuaires.settings')->get('ebou_annu_service_base_url'));
define('EBOU_ANNU_PROXY', \Drupal::config('ebourgogneannuaires.settings')->get('ebou_annu_proxy'));
define('EBOU_ANNU_SERVICE_EXTERNAL_URL', EBOU_ANNU_SERVICE_BASE_URL . 'cms/api/directory/');
define('EBOU_ANNU_SERVICE_EXTERNAL_CHECK_URL', EBOU_ANNU_SERVICE_EXTERNAL_URL . 'check');
define('EBOU_ANNU_SERVICE_APIKEY_REFERER', 'ebou-api-key');

/**
 * Class EbourgogneAnnuairesConfigForm.
 *
 *    Allow to enter the ebou-api-key  .
 *
 * @package Drupal\ebourgogneannuaires\Form
 */
class EbourgogneAnnuairesConfigForm extends ConfigFormBase {

  /**
   * Variable_get doesn't exist anymore in drupal 8
   * we redefine it.
   */
  function variable_get($name, $default_return) {
    $config = \Drupal::configFactory()->getEditable('core.site_information');

    $retour = $config->get($name);

    if ($retour == NULL) {
      $retour = $default_return;
    }

    return $retour;
  }

  /**
   * Variable_set doesn't exist anymore in drupal 8
   * we redefine it.
   */
  function variable_set($name, $value) {
    $config = \Drupal::configFactory()->getEditable('core.site_information');

    $config->set($name, $value)->save();
  }

  /**
   *
   */
  function checkApiKey($apiKey) {

    $base_url = EBOU_ANNU_SERVICE_EXTERNAL_CHECK_URL;

    $config = array();

    $config = [
      'headers' => [
        EBOU_ANNU_SERVICE_APIKEY_REFERER => $apiKey,
      ],
      'curl' => [
        CURLOPT_PROXY => EBOU_ANNU_PROXY,
      ],
    ];

    try {
      $response = \Drupal::httpClient()->request('GET', $base_url, $config);

      // If successful HTTP query.
      if ($response->getStatusCode() == 200) {
        $this->variable_set('ebourgogneannuaires_good_api_key', TRUE);
      }
      // Just in case no exception thrown.
      else {
        $this->variable_set('ebourgogneannuaires_good_api_key', FALSE);
        if ($response->getStatusCode() == 403) {
          drupal_set_message(t("Votre clé d'API n'est pas valide. Veuillez vérifier si elle correspond bien à celle fournie par e-bourgogne. Si le problème persiste, contactez l'assistance e-bourgogne."), 'error');
        }
        else {
          error_log($response->getContent());
          drupal_set_message(t("Une erreur est survenue lors de la vérification de votre clé d'API. Si le problème persiste, contactez l'assistance e-bourgogne."), 'error');
        }
      }
    }
    catch (ClientException $e) {
      error_log($e->getMessage());
      $this->variable_set('ebourgogneannuaires_good_api_key', FALSE);
      if ($e->getResponse()->getStatusCode() == 403) {
        drupal_set_message(t("Votre clé d'API n'est pas valide. Veuillez vérifier si elle correspond bien à celle fournie par e-bourgogne. Si le problème persiste, contactez l'assistance e-bourgogne."), 'error');
      }
      else {
        drupal_set_message(t("Une erreur est survenue lors de la vérification de votre clé d'API. Si le problème persiste, contactez l'assistance e-bourgogne."), 'error');
      }
    }
    catch (RequestException $e) {
      error_log($e->getMessage());
      $this->variable_set('ebourgogneannuaires_good_api_key', FALSE);
      drupal_set_message(t("Une erreur est survenue lors de la vérification de votre clé d'API. Si le problème persiste, contactez l'assistance e-bourgogne."), 'error');
    }

    $this->variable_set('ebourgogneannuaires_api_key', $apiKey);

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ebourgogneannuaires.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ebourgogneannuaires_config_form';
  }

  /**
   * Definition of the configuration form of the module.
   */
  function buildForm(array $form, FormStateInterface $form_state) {

    $message = t("Important : afin d'assurer son fonctionnement, le plugin e-bourgogne Annuaires charge des données extérieures en provenance d'<a href=\"@ebou-url\">e-bourgogne</a>", array('@ebou-url' => 'http://www.e-bourgogne.fr'));

    $form['#prefix'] = "<div class=\"messages messages--warning\">" . $message . "</div>";

    $form['apiKey'] = array(
      '#type' => 'textfield',
      '#title' => t('Cle d API'),
      '#default_value' => $this->variable_get('ebourgogneannuaires_api_key', ''),
      '#required' => TRUE,
    // We will define manually the wrapper with #prefix and #suffix because we want to include a div just after the input.
      '#theme_wrappers' => array(),
    // Begin of the wrapper.
      '#prefix' => '<div class="form-item form-type-textfield form-item-apiKey">',
    );
    $form['apiKey']['#attributes']['class'] = array('form-control');

    if ($this->variable_get('ebourgogneannuaires_good_api_key', FALSE)) {
      // Insert our logo div before close the wrapper.
      $form['apiKey']['#suffix'] = '<div class="verifiedKeyLogo">&nbsp;</div></div>';
    }
    else {
      // Standard wrapper.
      $form['apiKey']['#suffix'] = '</div>';
    }

    $form['submit_button'] = array(
      '#type' => 'submit',
      '#value' => t("Enregistrer la clé"),
    );

    $form['#attached'] = array(
      'library' => array(
        'ebourgogneannuaires/ebourgogneannuaires',
      ),
    );

    return $form;
    // Return parent::buildForm($form, $form_state);.
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->checkApiKey($form_state->getValue('apiKey'));

    parent::submitForm($form, $form_state);
  }

}
