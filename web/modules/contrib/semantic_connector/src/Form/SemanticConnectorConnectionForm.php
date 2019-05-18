<?php

/**
 * @file
 * Contains \Drupal\semantic_connector\Form\SemanticConnectorConnectionForm.
 */

namespace Drupal\semantic_connector\Form;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\semantic_connector\Entity\SemanticConnectorConnection;
use Drupal\semantic_connector\SemanticConnector;

class SemanticConnectorConnectionForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\semantic_connector\Entity\SemanticConnectorConnectionInterface $entity */
    $entity = $this->entity;
    $is_new = !$entity->getOriginalId();

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Server title'),
      '#description' => t('A short title for the connection.'),
      '#size' => 35,
      '#maxlength' => 60,
      '#required' => TRUE,
      '#default_value' => $entity->get('title'),
    );

    $form['url'] = array(
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#description' => $entity->getType() == 'pp_server' ? t('The URL, where the PoolParty server runs.@brExample: If your PoolParty instance is available at "https://my-poolparty-server.com/PoolParty/", please use "https://my-poolparty-server.com" here.', array('@br' => new FormattableMarkup('<br />', array()))) : t('The URL, where the SPARQL endpoint is available at.'),
      '#size' => 35,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#default_value' => $entity->get('url'),
    );

    $credentials = $entity->getCredentials();
    $form['credentials'] = array(
      '#type' => 'fieldset',
      '#title' => t('Credentials'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['credentials']['username'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#description' => t('Name of a user for the credentials.'),
      '#size' => 35,
      '#maxlength' => 60,
      '#default_value' => $credentials['username'],
    );
    $form['credentials']['password'] = array(
      '#type' => 'textfield',
      '#title' => t('Password'),
      '#description' => t('Password of a user for the credentials.'),
      '#size' => 35,
      '#maxlength' => 128,
      '#default_value' => $credentials['password'],
    );

    $form['health_check'] = array(
      '#type' => 'button',
      '#value' => t('Health check'),
      '#ajax' => array(
        'callback' => '::connectionTest',
        'wrapper' => 'health_info',
        'method' => 'replace',
        'effect' => 'slide',
        'progress' => array(
          'type' => 'throbber',
          'message' => t('Testing the connection...'),
        ),
      ),
    );

    if ($is_new) {
      $markup = '<div id="health_info">' . t('Click to check if the server is available.') . '</div>';
    }
    else {
      $available = '<div id="health_info" class="available"><div class="semantic-connector-led led-green" title="Service available"></div>' . t('The server is available.') . '</div>';
      $not_available = '<div id="health_info" class="not-available"><div class="semantic-connector-led led-red" title="Service NOT available"></div>' . t('The server is not available or the credentials are incorrect.') . '</div>';
      $markup = $entity->available() ? $available : $not_available;
    }
    $form['server_settings']['health_info'] = array(
      '#markup' => $markup,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check if the URL is valid.
    if (!UrlHelper::isValid($form_state->getValue('url'), TRUE)) {
      $form_state->setErrorByName('url', $this->t('A valid URL has to be given.'));
    }
  }
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var SemanticConnectorConnection $entity */
    $entity = $this->entity;
    $is_new = !$entity->getOriginalId();
    if ($is_new) {
      // Configuration entities need an ID manually set.
      $entity->set('id', SemanticConnector::createUniqueEntityMachineName($entity->getEntityTypeId(), $entity->get('title')));
      \Drupal::messenger()->addMessage(t('Connection %title has been created.', array('%title' => $entity->get('title'))));
    }
    else {
      \Drupal::messenger()->addMessage(t('Updated connection %title.',
        array('%title' => $entity->get('title'))));
    }

    $entity->set('credentials', array(
      'username' => $form_state->getValue('username'),
      'password' => $form_state->getValue('password'),
    ));
    $entity->set('config', array());
    $entity->save();

    $form_state->setRedirectUrl(Url::fromRoute('semantic_connector.overview'));
  }

  /**
   * Ajax callback function for checking if a new PoolParty GraphSearch server
   * is available.
   *
   * @param array $form
   *   The form array.
   * @param FormStateInterface &$form_state
   *   The form_state array.
   *
   * @return array
   *   The output array to be rendered.
   */
  function connectionTest(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\semantic_connector\Entity\SemanticConnectorConnectionInterface $entity */
    $entity = $this->entity;

    $available = '<div id="health_info" class="available"><div class="semantic-connector-led led-green" title="Service available"></div>' . t('The server is available.') . '</div>';
    $not_available = '<div id="health_info" class="not-available"><div class="semantic-connector-led led-red" title="Service NOT available"></div>' . t('The server is not available or the credentials are incorrect.') . '</div>';
    $markup = '';

    if (!empty($form_state->getValue('url')) && UrlHelper::isValid($form_state->getValue('url'), TRUE)) {
      // Create a new connection (without saving) with the current form data.
      $connection = SemanticConnector::getConnection($entity->getType());
      $connection->setUrl($form_state->getValue('url'));
      $connection->setCredentials(array(
        'username' => $form_state->getValue('username'),
        'password' => $form_state->getValue('password'),
      ));

      $availability = $connection->available();;
      $markup = $availability ? $available : $not_available;
    }

    if (empty($markup)) {
      $markup = $not_available;
    }

    // Clear potential error messages thrown during the requests.
    \Drupal::messenger()->deleteAll();

    return array(
      '#type' => 'markup',
      '#markup' => $markup,
    );
  }
}
