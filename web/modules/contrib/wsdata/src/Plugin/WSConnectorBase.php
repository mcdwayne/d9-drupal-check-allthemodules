<?php

namespace Drupal\wsdata\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Utility\Token;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Wsconnector plugin plugins.
 */
abstract class WSConnectorBase extends PluginBase implements WSConnectorInterface {
  use StringTranslationTrait;

  protected $expires;
  protected $cacheDefaultTime;
  protected $cacheDefaultOverride;
  protected $staleCache;
  protected $endpoint;
  protected $error;

  protected $languagePlugins = ['default'];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Token $token
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->token = $token;

    $this->expires = 0;
    $this->cacheDefaultTime = 0;
    $this->cacheDefaultOverride = FALSE;
    $this->staleCache = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token')
    );
  }

  /**
   * Return available options supported by the connector.
   */
  abstract public function getOptions();

  /**
   * Return available methods supported by the connector.
   */
  abstract public function getMethods();

  /**
   * Return an array of replacements.
   */
  abstract public function getReplacements(array $options);

  /**
   * Return the settings form provided by the connector.
   */
  public function getOptionsForm($options = []) {
    return [];
  }

  /**
   * Make the connector call.
   */
  abstract public function call($options, $method, $replacements = [], $data = NULL, array $tokens = []);

  /**
   * Setter for the endpoint.
   */
  public function setEndpoint($endpoint) {
    $this->endpoint = trim($endpoint);
  }

  /**
   * Getter for the endpoint.
   */
  public function getEndpoint() {
    return $this->endpoint;
  }

  /**
   * Whether returned data can be cached.
   */
  public function supportsCaching($method = NULL) {
    return FALSE;
  }

  /**
   * Return the last error the connector received.
   */
  public function getError() {
    return $this->error;
  }

  /**
   * Return the list of supported language handling plugins.
   */
  public function getSupportedLanguagePlugins() {
    return $this->languagePlugins;
  }

  /**
   * Figure out the overrides for cache times.
   */
  public function defaultCache($mintime = 0, $override = FALSE, $stale = FALSE) {
    $this->cacheDefaultTime = $mintime;
    $this->cacheDefaultOverride = $override;
    $this->staleCache = $stale;
  }

  /**
   * Get the expired time for caching.
   */
  public function expires() {
    if ($this->expires > 0) {
      return $this->expires;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Whether the connector is in a dead state and shouldn't be called.
   */
  public function isDegraded() {
    return FALSE;
  }

  /**
   * Setter for the connector errors.
   */
  protected function setError($code, $message) {
    $this->error = [
      'code' => $code,
      'message' => $message,
    ];
  }

  /**
   * Clear current error.
   */
  protected function clearError() {
    $this->error = NULL;
  }

  /**
   * Saves the options form.
   */
  public function saveOptions($values) {
    $options = [];

    foreach (array_keys($this->getOptionsForm()) as $option) {
      $options[$option] = $values[$option];
    }
    return $options;
  }

  /**
   * Internal function for finding tokens.
   */
  protected function findTokens($string) {
    preg_match_all('/\[([\w:]+)\]/', $string, $matches);
    return $matches[1];
  }

  /**
   * Internal function for applying replacements.
   */
  protected function applyReplacements($string, array $replacements = [], array $tokens = []) {
    foreach ($replacements as $token => $replace) {
      $string = str_replace('[' . $token . ']', $replace, $string);
    }

    $string = $this->token->replace($string, $tokens);
    return $string;
  }

}
