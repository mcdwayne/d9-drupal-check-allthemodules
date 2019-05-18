<?php

namespace Drupal\ebourgogneannuaires\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;


use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

/**
* Define the constants used by the module.
*/
define('EBOU_ANNU_MODULE_CONFIG_URL', 'admin/config/content/ebourgogneannuaires');

define('EBOU_ANNU_SERVICE_BASE_URL', \Drupal::config('ebourgogneannuaires.settings')->get('ebou_annu_service_base_url'));
define('EBOU_ANNU_PROXY', \Drupal::config('ebourgogneannuaires.settings')->get('ebou_annu_proxy'));
define('EBOU_ANNU_SERVICE_EXTERNAL_URL', EBOU_ANNU_SERVICE_BASE_URL . 'cms/api/directory/');
define('EBOU_ANNU_SERVICE_EXTERNAL_SEARCH_URL', EBOU_ANNU_SERVICE_EXTERNAL_URL . 'search');
define('EBOU_ANNU_SERVICE_EXTERNAL_SCRIPT_URL', EBOU_ANNU_SERVICE_EXTERNAL_URL . 'script/');
define('EBOU_ANNU_SERVICE_APIKEY_REFERER', 'ebou-api-key');


/**
 * Provides a 'E-bourgogne annuaires' block.
 *
 * Drupal\Core\Block\BlockBase gives us a very useful set of basic functionality
 * for this configurable block. We can just fill in a few of the blanks with
 * defaultConfiguration(), blockForm(), blockSubmit(), and build().
 *
 * @Block(
 *   id = "ebourgogneannuaires_config_block",
 *   admin_label = "Configuration block for e-bourgogne annuaires"
 * )
 */
class EbourgogneAnnuairesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'list_annuaires' => array(),
    );
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
   *
   */
  function ebourgogneannuaires_retrieve_annuaires($apiKey) {
    $organism_id = explode(':', base64_decode($apiKey))[0];

    $base_url = EBOU_ANNU_SERVICE_EXTERNAL_SEARCH_URL . '/?organismId=';
    $base_url .= $organism_id;
    $base_url .= '&page=no&sort=yes';

    $config = array();

    $config = [
      'headers' => [
        EBOU_ANNU_SERVICE_APIKEY_REFERER => $apiKey,
        'Connection' => 'Keep-Alive',
      ],
      'curl' => [
        CURLOPT_PROXY => EBOU_ANNU_PROXY,
      ],
    ];

    $annuairesArray = array();

    try {
      $response = \Drupal::httpClient()->request('GET', $base_url, $config);

      // If successful HTTP query.
      if ($response->getStatusCode() == 200) {

        $contents = $response->getBody()->getContents();

        $annuaires_json = json_decode($contents, TRUE);

        foreach ($annuaires_json as $annuaire) {
          $annuairesArray[$annuaire['id']] = $annuaire['title'];
        }

        $this->variable_set('ebourgogneannuaires_annuaires', $annuaires_json);
      }
      else {

        $this->variable_set('good_api_key', FALSE);

        if ($response->getStatusCode() == 403) {
          drupal_set_message(t("Votre clé d'API n'est pas valide. Veuillez vérifier si elle correspond bien à celle fournie par e-bourgogne. Si le problème persiste, contactez l'assistance e-bourgogne."), 'error');
        }
        else {
          drupal_set_message(t("Une erreur est survenue lors de la récupération des annuaires. Si le problème persiste, contactez l'assistance e-bourgogne."), 'error');
        }
      }
    }
    catch (ClientException $e) {
      $this->variable_set('good_api_key', FALSE);
      if ($e->getResponse()->getStatusCode() == 403) {
        drupal_set_message(t("Votre clé d'API n'est pas valide. Veuillez vérifier si elle correspond bien à celle fournie par e-bourgogne. Si le problème persiste, contactez l'assistance e-bourgogne."), 'error');

        $loglink = Link::fromTextAndUrl("page de configuration", Url::fromuserInput('/' . EBOU_ANNU_MODULE_CONFIG_URL));
        $msg = t("Veuillez vérifier votre clé dans la " . $loglink->toString());
        drupal_set_message($msg, 'error');
      }
      else {
        drupal_set_message(t("Une erreur est survenue lors de la récupération des annuaires. Si le problème persiste, contactez l'assistance e-bourgogne."), 'error');
      }
    }
    catch (RequestException $e) {

      $this->variable_set('good_api_key', FALSE);
      drupal_set_message(t("Une erreur est survenue lors de la récupération des annuaires. Si le problème persiste, contactez l'assistance e-bourgogne."), 'error');
    }

    return $annuairesArray;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['annuaireList'] = array(
      '#type' => 'select',
      '#title' => t("Annuaire choisi"),
      '#options' => $this->ebourgogneannuaires_retrieve_annuaires($this->variable_get('ebourgogneannuaires_api_key', '')),
      '#required' => TRUE,
      '#default_value' => isset($config['selectedAnnu']) ? $config['selectedAnnu'] : '',
    );
    $form['annuaireList']['#attributes']['class'] = array('form-control');

    $form['cssUrl'] = array(
      '#type' => 'textfield',
      '#title' => t("Feuille de style personnalisée (URL)"),
      '#default_value' => isset($config['cssUrl']) ? $config['cssUrl'] : '',
    );
    $form['cssUrl']['#attributes']['class'] = array('form-control');

    $form['radius'] = array(
      '#type' => 'textfield',
      '#title' => t("Rayon (en km)"),
      '#default_value' => isset($config['radius']) ? $config['radius'] : '',
    );
    $form['radius']['#attributes']['class'] = array('form-control');

    $form['city'] = array(
      '#type' => 'textfield',
      '#title' => t("Ville"),
      '#default_value' => isset($config['city']) ? $config['city'] : '',
    );
    $form['city']['#attributes']['class'] = array('form-control');

    $form['postcode'] = array(
      '#type' => 'textfield',
      '#title' => t("Code postal"),
      '#default_value' => isset($config['postcode']) ? $config['postcode'] : '',
    );
    $form['postcode']['#attributes']['class'] = array('form-control');

    $form['postcode']['#suffix'] = '<div class="messages messages--warning">' . t("Attention : il est deconseillé d'ajouter plusieurs annuaires sur une même page.") . '</div>';

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    $this->setConfigurationValue('selectedAnnu', $form_state->getValue('annuaireList'));
    $this->setConfigurationValue('cssUrl', $form_state->getValue('cssUrl'));
    $this->setConfigurationValue('radius', $form_state->getValue('radius'));
    $this->setConfigurationValue('city', $form_state->getValue('city'));
    $this->setConfigurationValue('postcode', $form_state->getValue('postcode'));
  }

  /**
   *
   */
  private function constructUrl() {
    $config = $this->getConfiguration();

    $url = $config['selectedAnnu']
    . ".js?divId=iframeFlag-"
    . $config['selectedAnnu'];

    if ($config['cssUrl'] != "") {
      $url .= "&customCss=" . $config['cssUrl'];
    }

    if ($config['radius'] != "") {
      $url .= "&radius=" . $config['radius']
      . "&ville=" . $config['city']
      . "&codePostal=" . $config['postcode'];
    }

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();

    $url = $this->constructUrl();

    $scriptTag = "<script type=\"text/javascript\" src=\"" . EBOU_ANNU_SERVICE_EXTERNAL_SCRIPT_URL . $url . "\" charset=\"UTF-8\"></script>";

    $snippet = "<div id=\"iframeFlag-" . $config['selectedAnnu'] . "\"></div>"
    . $scriptTag;

    return array(
      '#markup' => $snippet,
    // By default script and div tag are forbidden.
      '#allowed_tags' => array('script', 'div'),

    );
  }

}
