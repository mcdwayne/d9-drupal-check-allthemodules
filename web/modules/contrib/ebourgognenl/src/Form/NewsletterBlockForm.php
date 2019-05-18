<?php


namespace Drupal\ebourgognenewsletter\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

/**
 * Builds the newsletter form for the e-bourgogne newsletter block.
 */
class NewsletterBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ebourgognenewsletter_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('core.site_information');

    $retour = $config->get('ebourgognenewsletter_selected_newsletters');

    $form['text'] = array(
      '#markup' => '<p>' . $config->get('ebourgognenewsletter_text') . '<p>',
    );

    $form['newslettersList'] = array(
      '#type' => 'select',
      '#options' => $config->get('ebourgognenewsletter_selected_newsletters'),
    );
    $form['newslettersList']['#attributes']['class'] = array('form-control');

    $form['mail'] = array(
      '#type' => 'textfield',
      '#title' => t("email"),
      '#required' => TRUE,
    );
    $form['mail']['#attributes']['class'] = array('form-control');
    $form['mail']['#attributes']['placeholder'] = t("Entrez votre email");

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t("S'inscrire"),
    );
    $form['submit']['#attributes']['class'] = array('btn btn-primary');

    /*$form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => $this->t('Log in'));
     */
    $form['#validate'][] = '::validateEmail';

    return $form;
  }

  /**
   * Sets an error if supplied email address is wrong.
   */
  public function validateEmail(array &$form, FormStateInterface $form_state) {
    if (!valid_email_address($form_state->getValue('mail'))) {
      $form_state->setErrorByName('mail', $this->t("L'adresse %mail n'est pas valide.", array('%mail' => $form_state->getValue('mail'))));
    }
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
  function ebourgognenewsletter_subscribe_newsletters($newsletter_id, $email) {
    $api_key = $this->variable_get('ebourgognenewsletter_api_key', '');

    $base_url = EBOU_NEWS_BO_API_ADD_URL . '/' . $newsletter_id . '?followerEmail=' . $email;

    $config = array();

    $config = [
      'headers' => [
        EBOU_NEWS_BO_API_APIKEY_REFERER => $api_key,
        'Connection' => 'Keep-Alive',
      ],
      'curl' => [
        CURLOPT_PROXY => EBOU_NEWS_PROXY,
      ],
    ];

    try {
      $response = \Drupal::httpClient()->request('POST', $base_url, $config);

      // If successful HTTP query.
      if ($response->getStatusCode() == 200) {

        drupal_set_message(t("Vous êtes inscrit à la newsletter."));
      }
      else {

        $this->variable_set('good_api_key', FALSE);

        if ($response->getStatusCode() == 403) {
          drupal_set_message(t("Votre clé d'API n'est pas valide. Veuillez vérifier si elle correspond bien à celle fournie par e-bourgogne. Si le problème persiste, contactez l'assistance e-bourgogne."), 'error');
        }
        else {
          drupal_set_message(t("Une erreur est survenue lors de l'inscription à la newsletter. Si le probleme persiste, contactez l'assistance e-bourgogne."), 'error');
        }
      }
    }
    catch (ClientException $e) {
      $this->variable_set('good_api_key', FALSE);
      if ($e->getResponse()->getStatusCode() == 403) {
        drupal_set_message(t("Votre clé d'API n'est pas valide. Veuillez vérifier si elle correspond bien à celle fournie par e-bourgogne. Si le problème persiste, contactez l'assistance e-bourgogne."), 'error');
      }
      else {
        drupal_set_message(t("Une erreur est survenue lors de l'inscription à la newsletter. Si le probleme persiste, contactez l'assistance e-bourgogne."), 'error');
      }
    }
    catch (RequestException $e) {
      $this->variable_set('good_api_key', FALSE);
      drupal_set_message(t("Une erreur est survenue lors de l'inscription à la newsletter. Si le probleme persiste, contactez l'assistance e-bourgogne."), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->ebourgognenewsletter_subscribe_newsletters($form_state->getValue('newslettersList'), $form_state->getValue('mail'));
  }

}
