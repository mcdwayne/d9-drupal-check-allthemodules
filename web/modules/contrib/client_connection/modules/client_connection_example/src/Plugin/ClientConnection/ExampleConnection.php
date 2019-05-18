<?php

namespace Drupal\client_connection_example\Plugin\ClientConnection;

use Drupal\client_connection\Plugin\ClientConnection\ClientConnectionBase;
use Drupal\client_connection\Plugin\ClientConnection\ClientConnectionInterface;
use Drupal\client_connection\Plugin\ClientConnection\HttpClientInterface;
use Drupal\client_connection\Plugin\ClientConnection\HttpClientTrait;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;

/**
 * Provides a default client connection example.
 *
 * @ClientConnection(
 *   id = "example",
 *   label = @Translation("Example"),
 *   description = @Translation("This is an example client connection."),
 *   categories = {
 *     "global" = @Translation("Global"),
 *     "inventory" = @Translation("Example")
 *   }
 * )
 */
class ExampleConnection extends ClientConnectionBase implements ClientConnectionInterface, HttpClientInterface {

  use HttpClientTrait;

  /**
   * {@inheritdoc}
   */
  protected function clientForm(array $form, FormStateInterface $form_state) {

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $this->getConfigurationValue('api_key', ''),
      '#description' => $this->t("An api key to use."),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function clientValidate(array $form, FormStateInterface $form_state) {
    if ($form_state->getValue('api_key') == 'this will fail') {
      $form_state->setErrorByName('api_key', 'The API Key cannot be filled in with "this will fail".');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function clientSubmit(array &$form, FormStateInterface $form_state) {
    $this->configuration['api_key'] = $form_state->getValue('api_key');
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigDefaults() {
    return [
      'base_uri' => 'https://jsonplaceholder.typicode.com',
      RequestOptions::HEADERS => ['Accept: application/json'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRequestOptions($method, $uri, &$options) {
    switch ($method) {
      case 'get':
        // Set authorization.
        $api_key = $this->getConfigurationValue('api_key');
        if (!is_null($api_key)) {
          $options[RequestOptions::QUERY]['api_key'] = $api_key;
        }
        break;

    }
  }

  /**
   * {@inheritdoc}
   */
  protected function alterSendOptions(RequestInterface $request, &$options) {

  }

  /**
   * Get posts from this connection.
   *
   * @return array|mixed
   *   An array of post information from the API.
   */
  public function getPosts() {
    try {
      $uri = '/posts';
      $response = $this->request('get', $uri);
      if ($response->getStatusCode() == 200) {
        return self::jsonDecode($response->getBody()->getContents());
      }
      return [];
    }
    catch (ClientException $exception) {
      return [];
    }
  }

  /**
   * Get a specific posts from this connection.
   *
   * @param int|string $post_id
   *   The post id.
   *
   * @return array|mixed
   *   An array of specific post information from the API.
   */
  public function getPost($post_id) {
    try {
      $uri = '/posts/' . $post_id;
      $response = $this->request('get', $uri);
      if ($response->getStatusCode() == 200) {
        return self::jsonDecode($response->getBody()->getContents());
      }
      return [];
    }
    catch (ClientException $exception) {
      return [];
    }
  }

}
