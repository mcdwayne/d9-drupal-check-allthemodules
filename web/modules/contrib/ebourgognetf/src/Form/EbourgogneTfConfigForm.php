<?php

namespace Drupal\ebourgognetf\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

/**
* Define the constants used by the module.
*/
define('EBOU_TF_MODULE_CONFIG_URL', 'admin/config/content/ebourgognetf');

define('EBOU_TF_PROXY', \Drupal::config('ebourgognetf.settings')->get('ebou_tf_proxy'));

define('EBOU_TF_BO_BASE_URL', \Drupal::config('ebourgognetf.settings')->get('ebou_tf_bo_base_url'));
define('EBOU_TF_BO_API_URL', EBOU_TF_BO_BASE_URL . 'api/teleformulaires/');
define('EBOU_TF_BO_API_CHECK_URL', EBOU_TF_BO_API_URL . 'check');
define('EBOU_TF_BO_API_SEARCH_URL', EBOU_TF_BO_API_URL . 'search');
define('EBOU_TF_BO_API_APIKEY_REFERER', 'ebou-api-key');

define('EBOU_TF_FO_BASE_URL', \Drupal::config('ebourgognetf.settings')->get('ebou_tf_fo_base_url'));

/**
 * Class EbourgogneTfConfigForm.
 *
 * @package Drupal\ebourgognetf\Form
 */
class EbourgogneTfConfigForm extends ConfigFormBase {

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
   * Convert json list of teleform into an array.
   */
  function getTeleformArray() {

    $organism_tfs_json = $this->variable_get('ebourgognetf_teleform', array());
    $teleformArray = array();

    foreach ($organism_tfs_json as $organism_tf) {
      $teleformArray[EBOU_TF_FO_BASE_URL . $organism_tf['url']] = $organism_tf['eForm']['name'];
    }

    return $teleformArray;
  }

  /**
   *
   */
  function retrieveTeleform($apiKey) {

    $base_url = EBOU_TF_BO_API_SEARCH_URL . '/?organismId=';

    $organism_id = explode(':', base64_decode($apiKey))[0];

    $base_url .= $organism_id;

    $config = array();

    $config = [
      'headers' => [
        EBOU_TF_BO_API_APIKEY_REFERER => $apiKey,
        'Connection' => 'Keep-Alive',
      ],
      'curl' => [
        CURLOPT_PROXY => EBOU_TF_PROXY,
      ],
    ];

    try {
      $response = \Drupal::httpClient()->request('GET', $base_url, $config);

      // If successful HTTP query.
      if ($response->getStatusCode() == 200) {

        $contents = $response->getBody()->getContents();

        $organism_tfs_json = json_decode($contents, TRUE);

        $this->variable_set('ebourgognetf_teleform', $organism_tfs_json);
      }
      else {

        $this->variable_set('good_api_key', FALSE);

        if ($response->getStatusCode() == 403) {
          drupal_set_message(t("Invalid API key. Please check that it correspond to the one given by e-bourgogne. If the problem persists, please contact e-bourgogne's helpdesk."), 'error');
        }
        else {
          drupal_set_message(t("An error has occurred while checking your API key, please try again in a few minutes. If the problem persists, please contact e-bourgogne's helpdesk."), 'error');
        }
      }
    }
    catch (ClientException $e) {
      $this->variable_set('good_api_key', FALSE);
      if ($e->getResponse()->getStatusCode() == 403) {
        drupal_set_message(t("Invalid API key. Please check that it correspond to the one given by e-bourgogne. If the problem persists, please contact e-bourgogne's helpdesk."), 'error');
      }
      else {
        drupal_set_message(t("An error has occurred while retrieving the eforms, please try again in a few minutes. If the problem persists, please contact e-bourgogne's helpdesk."), 'error');
      }
    }
    catch (RequestException $e) {
      $this->variable_set('good_api_key', FALSE);
      drupal_set_message(t("An error has occurred while retrieving the eforms, please try again in a few minutes. If the problem persists, please contact e-bourgogne's helpdesk."), 'error');
    }
  }

  /**
   *
   */
  function checkApiKey($apiKey) {
    $base_url = EBOU_TF_BO_API_CHECK_URL;

    $config = array();

    $config = [
      'headers' => [
        EBOU_TF_BO_API_APIKEY_REFERER => $apiKey,
      ],
      'curl' => [
        CURLOPT_PROXY => EBOU_TF_PROXY,
      ],
    ];

    try {
      $response = \Drupal::httpClient()->request('GET', $base_url, $config);

      // If successful HTTP query.
      if ($response->getStatusCode() == 200) {
        $this->variable_set('good_api_key', TRUE);
      }
      // Just in case no exception thrown.
      else {
        $this->variable_set('good_api_key', FALSE);
        if ($response->getStatusCode() == 403) {
          drupal_set_message(t("Invalid API key. Please check that it correspond to the one given by e-bourgogne. If the problem persists, please contact e-bourgogne's helpdesk."), 'error');
        }
        else {
          drupal_set_message(t("An error has occurred while checking your API key, please try again in a few minutes. If the problem persists, please contact e-bourgogne's helpdesk."), 'error');
        }
      }
    }
    catch (ClientException $e) {
      $this->variable_set('good_api_key', FALSE);
      if ($e->getResponse()->getStatusCode() == 403) {
        drupal_set_message(t("Invalid API key. Please check that it correspond to the one given by e-bourgogne. If the problem persists, please contact e-bourgogne's helpdesk."), 'error');
      }
      else {
        drupal_set_message(t("An error has occurred while checking your API key, please try again in a few minutes. If the problem persists, please contact e-bourgogne's helpdesk."), 'error');
      }
    }
    catch (RequestException $e) {
      $this->variable_set('good_api_key', FALSE);
      drupal_set_message(t("An error has occurred while checking your API key, please try again in a few minutes. If the problem persists, please contact e-bourgogne's helpdesk."), 'error');
    }

    $this->variable_set('ebourgognetf_api_key', $apiKey);

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ebourgognetf.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ebourgognetf_config_form';
  }

  /**
   * Definition of the configuration form of the module.
   */
  function buildForm(array $form, FormStateInterface $form_state) {
    $message = t('Important notice: in order for the module to properly work, it has to load external data from <a href="@ebou-url">e-bourgogne</a>',
      array('@ebou-url' => 'http://www.e-bourgogne.fr'));

    $form['#prefix'] = "<div class=\"messages messages--warning\">" . $message . "</div>";

    $form['apiKey'] = array(
      '#type' => 'textfield',
      '#title' => t('API Key'),
      '#default_value' => $this->variable_get('ebourgognetf_api_key', ''),
      '#required' => TRUE,
    // We will define manually the wrapper with #prefix and #suffix because we want to include a div just after the input.
      '#theme_wrappers' => array(),
    // Begin of the wrapper.
      '#prefix' => "<div class=\"form-item form-type-textfield form-item-apiKey\"><label for=\"edit-apikey\">"
        . t("API key")
        . "<span class=\"form-required\" title=\"This field is required.\">*</span></label>",
    );
    $form['apiKey']['#attributes']['class'] = array('form-control');

    if ($this->variable_get('good_api_key', FALSE)) {
      // Insert our logo div before close the wrapper.
      $form['apiKey']['#suffix'] = '<div class="verifiedKeyLogo">&nbsp;</div></div>';
    }
    else {
      // Standard wrapper.
      $form['apiKey']['#suffix'] = '</div>';
    }

    $form['submit_button'] = array(
      '#type' => 'submit',
      '#value' => t("Save"),
    );

    if ($this->variable_get('good_api_key', FALSE)) {
      $form['formList'] = array(
        '#type' => 'select',
        '#title' => t("Available EForms"),
        '#options' => $this->getTeleformArray(),
        '#size' => 20,
        '#suffix' => '<div>' . t('Link') . ' : <span id="linkUrl">&nbsp;</span></div>',
        '#attached' => array(
          'library' => array(
            'ebourgognetf/ebourgognetf',
          ),
        ),
      );
    }

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
    if ($this->variable_get('good_api_key', FALSE)) {
      $this->retrieveTeleform($form_state->getValue('apiKey'));
    }
    else {
      $this->variable_set('ebourgognetf_teleform', array());
    }

    $this->variable_set('ebou_tf_fo_base_url', EBOU_TF_FO_BASE_URL);

    parent::submitForm($form, $form_state);

  }

}
