<?php

namespace Drupal\ebourgognenewsletter\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

use Drupal\Core\Link;

/**
* Define the constants used by the module.
*/
define('EBOU_NEWS_MODULE_CONFIG_URL', 'admin/config/content/ebourgognenewsletter');

define('EBOU_NEWS_BO_BASE_URL', \Drupal::config('ebourgognenewsletter.settings')->get('ebou_news_bo_base_url'));

define('EBOU_NEWS_BO_API_URL', EBOU_NEWS_BO_BASE_URL . 'api/newsletter/');
define('EBOU_NEWS_BO_API_LIST_URL', EBOU_NEWS_BO_API_URL);
define('EBOU_NEWS_BO_API_ADD_URL', EBOU_NEWS_BO_API_URL . 'add/');

define('EBOU_NEWS_PROXY', \Drupal::config('ebourgognenewsletter.settings')->get('ebou_news_proxy'));

define('EBOU_NEWS_BO_API_APIKEY_REFERER', 'ebou-api-key');


/**
 * Provides a 'E-bourgogne newsletter' block.
 *
 * Drupal\Core\Block\BlockBase gives us a very useful set of basic functionality
 * for this configurable block. We can just fill in a few of the blanks with
 * defaultConfiguration(), blockForm(), blockSubmit(), and build().
 *
 * @Block(
 *   id = "ebourgognenewsletter_config_block",
 *   admin_label = "Configuration block for e-bourgogne newsletter"
 * )
 */
class EbourgogneNewsletterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'list_newsletter' => array(),
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
  function ebourgognenewsletter_retrieve_newsletters($apiKey) {
    $organism_id = explode(':', base64_decode($apiKey))[0];

    $base_url = EBOU_NEWS_BO_API_LIST_URL . '/' . $organism_id;

    $config = array();

    $config = [
      'curl' => [
        CURLOPT_PROXY => EBOU_NEWS_PROXY,
      ],
      'headers' => [
        EBOU_NEWS_BO_API_APIKEY_REFERER => $apiKey,
        'Connection' => 'Keep-Alive',
      ],
    ];

    $newslettersArray = array();

    try {
      $response = \Drupal::httpClient()->request('GET', $base_url, $config);

      // If successful HTTP query.
      if ($response->getStatusCode() == 200) {

        $contents = $response->getBody()->getContents();

        $newsletters_json = json_decode($contents, TRUE);

        $newslettersArray = array();

        foreach ($newsletters_json as $newsletter) {
          $newslettersArray[$newsletter['id']] = $newsletter['title'];
        }

        $this->variable_set('ebourgognenewsletter_array', $newslettersArray);

      }
      else {

        $this->variable_set('good_api_key', FALSE);

        if ($response->getStatusCode() == 403) {
          drupal_set_message(t("Votre clé d'API n'est pas valide. Veuillez vérifier si elle correspond bien à celle fournie par e-bourgogne. Si le problème persiste, contactez l'assistance e-bourgogne."), 'error');
        }
        else {
          drupal_set_message(t("Une erreur est survenue lors de la vérification de votre clé d'API. Si le problème persiste, contactez l'assistance e-bourgogne."), 'error');
        }
      }
    }
    catch (ClientException $e) {

      $this->variable_set('good_api_key', FALSE);
      if ($e->getResponse()->getStatusCode() == 403) {
        drupal_set_message(t("Votre clé d'API n'est pas valide. Veuillez vérifier si elle correspond bien à celle fournie par e-bourgogne. Si le problème persiste, contactez l'assistance e-bourgogne."), 'error');

        $loglink = Link::fromTextAndUrl("page de configuration", Url::fromuserInput('/' . EBOU_NEWS_MODULE_CONFIG_URL));
        $msg = t("Veuillez vérifier votre clé dans la " . $loglink->toString());
        drupal_set_message($msg, 'error');
      }
      else {
        drupal_set_message(t("Une erreur est survenue lors de la récuperation des newsletters. Si le probleme persiste, contactez l'assistance e-bourgogne."), 'error');
      }
    }
    catch (RequestException $e) {

      $this->variable_set('good_api_key', FALSE);
      drupal_set_message(t("Une erreur est survenue lors de la récuperation des newsletters. Si le probleme persiste, contactez l'assistance e-bourgogne.", 'error'));
    }

    return $newslettersArray;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Texte'),
      '#default_value' => $this->variable_get('ebourgognenewsletter_text', ''),
      '#required' => TRUE,
    );
    $form['text']['#attributes']['class'] = array('form-control');

    $form['newsletterList'] = array(
      '#id' => 'news-list',
      '#type' => 'select',
      '#title' => t("Newsletter(s) affichée(s)"),
      '#options' => $this->ebourgognenewsletter_retrieve_newsletters($this->variable_get('ebourgognenewsletter_api_key', '')),
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#default_value' => $this->variable_get('ebourgognenewsletter_selected_newsletters_values_only', ''),
    );
    $form['newsletterList']['#attributes']['class'] = array('form-control');

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

    $newsArray = $this->variable_get('ebourgognenewsletter_array', '');
    $selected_newsletters = array();
    $selected_newsletters_values_only = array();

    foreach ($form_state->getValue('newsletterList') as $newsletters) {
      $selected_newsletters[$newsletters] = $newsArray[$newsletters];
      $selected_newsletters_values_only[] = $newsletters;
    }

    // Set the newletters to display in the list of available newletter in the newsletter block.
    $this->variable_set('ebourgognenewsletter_selected_newsletters', $selected_newsletters);

    // Set the options to select by default next time this form is displayed.
    $this->variable_set('ebourgognenewsletter_selected_newsletters_values_only', $selected_newsletters_values_only);

    $this->variable_set('ebourgognenewsletter_text', $form_state->getValue('text'));

    parent::submitConfigurationForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $form = \Drupal::formBuilder()->getForm('Drupal\ebourgognenewsletter\Form\NewsletterBlockForm');

    return $form;
  }

}
