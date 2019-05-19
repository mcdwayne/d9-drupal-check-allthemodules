<?php

namespace Drupal\wsdata\Plugin\WSConnector;

use Drupal\Core\Utility\Token;
use Drupal\wsdata\WSDataInvalidMethodException;
use Drupal\wsdata\Plugin\WSConnectorBase;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * HTTP Connector.
 *
 * @WSConnector(
 *   id = "WSConnectorSimpleHTTP",
 *   label = @Translation("Simple HTTP connector", context = "WSConnector"),
 * )
 */
class WSConnectorSimpleHTTP extends WSConnectorBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        Client $http_client,
        Token $token
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $token);
    $this->http_client = $http_client;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMethods() {
    return ['get', 'post', 'put', 'delete', 'head', 'options'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return [
      'path' => NULL,
      'method' => [],
      'headers' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function saveOptions($values) {
    // Check how many key values and create the array.
    foreach ($values as $key => $value) {
      if (preg_match("/^key_([0-9]+)/", $key, $matches)) {
        if (isset($matches[1]) && !empty($values['key_' . $matches[1]])) {
          $values['headers'][$matches[1]] = array('key_' . $matches[1] => $values['key_' . $matches[1]],
                                                  'value_' . $matches[1] => $values['value_' . $matches[1]]);
          unset($values['key_' . $matches[1]]);
          unset($values['value_' . $matches[1]]);
        }
      }
    }
    return parent::saveOptions($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getReplacements(array $options) {
    return $this->findTokens($this->endpoint . '/' . $options['path']);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsForm($options = []) {

    $methods = $this->getMethods();
    $form['path'] = [
      '#title' => $this->t('Path'),
      '#description' => $this->t('The final endpoint will be <em>Server Endpoint/Path</em>'),
      '#type' => 'textfield',
      '#maxlength' => 512,
    ];

    $form['method'] = [
      '#title' => $this->t('HTTP Method'),
      '#type' => 'select',
      '#options' => array_combine($methods, $methods),
    ];

    $form['expires'] = [
      '#type' => 'number',
      '#title' => $this->t('Expire'),
      '#description' => $this->t('Cache the response for number of seconds. This values will override the Cache-Control header value if it\'s set'),
    ];

    $header_count = 1;

    if (isset($options['form_state'])) {
      $input = $options['form_state']->getUserInput();
      if (isset($input['headers_count'])) {
        $header_count = $input['headers_count'] + 1;
      }
    }

    $form['headers'] = [
      '#title' => $this->t('Headers'),
      '#type' => 'fieldset',
      '#attributes' => ['id' => 'wsconnector-headers'],
    ];

    $form['headers']['headers_count'] = [
      '#type' => 'hidden',
      '#value' => $header_count,
    ];

    for($i = 0; $i < $header_count; $i++) {
      $form['headers'][$i]['key_' . $i] = [
        '#type' => 'textfield',
        '#title' => $this->t('Key'),
      ];

      $form['headers'][$i]['value_' . $i] = [
        '#type' => 'textfield',
        '#title' => $this->t('Value'),
      ];
    }

    if (isset($options['form_state'])) {
      $form['headers']['add_another'] = [
        '#type'   => 'submit',
        '#value'  => $this->t('Add another'),
        '#ajax'   => [
          'callback' => '\Drupal\wsdata\Plugin\WSConnector\WSConnectorSimpleHTTP::wsconnectorHttpHeaderAjaxCallback',
          'wrapper'  => 'wsconnector-headers',
        ],
        '#limit_validation_errors' => [],
      ];
    }

    return $form;
  }

  /**
   * Ajax callback function.
   */
  public static function wsconnectorHttpHeaderAjaxCallback(array &$form, FormStateInterface $form_state) {
    return $form['options']['wsserveroptions']['headers'];
  }

  /**
   * {@inheritdoc}
   */
  public function call($options, $method, $replacements = [], $data = NULL, array $tokens = []) {
    $token_service = \Drupal::token();
    if (!in_array($method, $this->getMethods())) {
      throw new WSDataInvalidMethodException(sprintf('Invalid method %s on connector type %s', $method, __CLASS__));
    }

    $uri = $this->endpoint . '/' . $options['path'];
    $uri = $this->applyReplacements($uri, $replacements, $tokens);
    $options['http_errors'] = FALSE;

    // Perform the token replace on the headers.
    if (!empty($options['headers'])) {
      for ($i = 0; $i < count($options['headers']); $i++) {
        if (!empty($options['headers'][$i]['key_' . $i])) {
          $options['headers'][$options['headers'][$i]['key_' . $i]] = $token_service->replace($options['headers'][$i]['value_' . $i], $tokens);
        }
        unset($options['headers'][$i]['key_' . $i]);
        unset($options['headers'][$i]['value_' . $i]);
        unset($options['headers'][$i]);
      }
    }

    if (!empty($data)) {
      $options['body'] = $data;
    }
    if (isset($options['body'])) {
      $options['body'] = $token_service->replace($options['body'], $tokens);
    }

    $response = $this->http_client->request($method, $uri, $options);

    // If the debug mode is enabled let's create a payload to display to ksm.
    if (\Drupal::state()->get('wsdata_debug_mode')) {
      $debug['method'] = $method;
      $debug['uri'] = $uri;
      $debug['options'] = $options;
      $debug['response']['code'] = $response->getStatusCode();
      $debug['response']['body'] = (string)$response->getBody();
      ksm($debug);
    }

    // Set the cache expire time.
    if (isset($options['expires']) && !empty($options['expires'])) {
      $this->expires = (integer)$options['expires'];
    }
    else {
      $this->setCacheExpire($response);
    }

    $status = $response->getStatusCode();

    if ($status >= 199 and $status <= 300) {
      return (string)$response->getBody();
    }

    $this->setError($status, $response->getReasonPhrase());
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsCaching($method = NULL) {
    // If the request is a GET support caching.
    if (in_array($method, ['get'])) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  public function setCacheExpire($response) {
    // Check the
    $cache_header = $response->getHeader('Cache-Control');
    foreach ($cache_header as $control_header) {
      if (preg_match("/^max-age=\d+/", $control_header)) {
        $this->expires = (integer)str_replace('max-age=', '' ,$control_header);
      }
    }
  }
}
