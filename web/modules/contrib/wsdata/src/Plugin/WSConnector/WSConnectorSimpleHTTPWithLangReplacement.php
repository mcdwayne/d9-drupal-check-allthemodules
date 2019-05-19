<?php

namespace Drupal\wsdata\Plugin\WSConnector;

use Drupal\Core\Utility\Token;
use Drupal\wsdata\WSDataInvalidMethodException;
use Drupal\wsdata\Plugin\WSConnectorBase;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * HTTP Connector.
 *
 * @WSConnector(
 *   id = "WSConnectorSimpleHTTPWithLangReplacement",
 *   label = @Translation("Simple HTTP connector with Language Replacement", context = "WSConnector"),
 * )
 */
class WSConnectorSimpleHTTPWithLangReplacement extends WSConnectorSimpleHTTP {

  /**
   * {@inheritdoc}
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        Client $http_client,
        Token $token,
        LanguageManagerInterface $language_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $http_client, $token, $language_manager);
    $this->http_client = $http_client;
    $this->token = $token;
    $this->language_manager = $language_manager;
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
      $container->get('token'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsForm($options = []) {
    $form = parent::getOptionsForm($options);

    $form['intructions'] = [
      '#markup' => $this->t('This connector will replace the string [LANGUAGE] in the path or the WSServer URL with the strings defined below, depending on the site\'s current content language'),
      '#weight' => -50,
    ];

    $form['urllang'] = [
      '#title' => $this->t('URL Language Replacement'),
      '#type' => 'fieldset',
    ];

    foreach ($this->language_manager->getLanguages() as $langcode => $language) {
      $form['lang-' . $langcode] = [
        '#type' => 'textfield',
        '#title' => $this->t('Replacement value for %lang', ['%lang' => $language->getName()]),
        '#default_value' => $langcode,
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
    $langcode = $this->language_manager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    $replacements['LANGUAGE'] = $options['lang-' . $langcode] ?? $langcode;
    return parent::call($options, $method, $replacements, $data, $tokens);
  }
}
