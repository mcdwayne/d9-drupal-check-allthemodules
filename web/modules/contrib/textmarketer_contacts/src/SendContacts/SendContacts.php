<?php

/**
 * @file
 * Allows the site to send and receive user contacts to and from Text Marketer.
 */

namespace Drupal\textmarketer_contacts\SendContacts;

use Drupal\Core\Annotation\Action;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Entity;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SendContacts class.
 */
class SendContacts extends ControllerBase implements SendContactsInterface {

  protected $httpClient;
  protected $configFactory;

  /**
   * The constructor.
   */
  public function __construct(Client $http_client, ConfigFactory $config_factory) {

    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('textmarketer_contacts.client'),
      $container->get('config.factory')
    );
  }

  /**
   * Catches the telephone numbers on user registration and account update.
   *
   * @param array $entity
   *   An array containing the user data.
   */
  public function postClient(array $entity) {

    $client = $this->getConfig()->client;
    $phone = $this->isSend($entity)['phone'];
    $is_send = $this->isSend($entity)['is_send'];
    $url = $this->apiUrl();

    if ($this->isSend($entity) === NULL) {
      return;
    }

    try {

      if ($is_send && !empty($phone)) {
        $client->request('POST', $url,
          ['form_params' => ['numbers' => $phone]]);
      }
    }
    catch (\GuzzleHttp\Exception\ClientException $e) {
      $message = t('An error has occured with status code: @code',
        array('@code' => $e->getResponse()->getStatusCode()));

      // @todo: Replace static method with logger service.
      \Drupal::logger('textmarketer')->log(Error::ERROR, $message);
    }
  }

  /**
   * Ensures the user has subscribed before sending numbers.
   *
   * @param array $entity
   *   The user entity.
   *
   * @return mixed
   *   Returns boolean.
   */
  protected function isSend(array $entity) {

    $field_telephone = $this->getConfig()->config->get('field_telephone');
    $field_subscribe = $this->getConfig()->config->get('field_subscribe');
    $this->send['phone'] = !empty($field_telephone)
      ? $entity->get($field_telephone)->getValue()[0]['value'] : FALSE;
    $subscribe = ($field_subscribe !== 'field not required')
      ? $entity->get($field_subscribe)->getValue()[0]['value'] : TRUE;
    $this->send['is_send'] = (!empty($this->send['phone'])
      && ($field_subscribe === 'field_not_required'
        || $subscribe == TRUE)) ? TRUE : FALSE;

    return $this->send;
  }

  /**
   * Helper function returns the configuration settings.
   *
   * @return $this
   */
  private function getConfig() {

    // @todo: Replace the special methods & use existing Guzzle factory.
    // The container returns NULL in the user profile, had to use this solution.
    $this->config = \Drupal::config('textmarketer_contacts.settings');
    $this->client = \Drupal::httpClient();

    return $this;
  }

  /**
   * Helper function prepares a URL string with operation and credentials.
   *
   * @return string
   *   Returns the URL with credentials.
   */
  private function apiUrl() {

    $config = $this->getConfig()->config;
    $op = '/services/rest/group/' . $config->get('group_id');
    $username = $config->get('username');
    $password = $config->get('password');
    $api_url = $config->get('api_url');
    $url = "https://{$username}:{$password}@{$api_url}{$op}";

    return $url;
  }

}
