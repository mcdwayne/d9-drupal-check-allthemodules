<?php

namespace Drupal\concurrent_url_negotiation;

use Drupal\Core\Config\ConfigException;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class ConcurrentUrlNegotiationConfig.
 *
 * @package Drupal\concurrent_url_negotiation
 */
class ConcurrentUrlNegotiationConfig {

  /**
   * Domain literal that matches any domain.
   *
   * @var string
   */
  const DOMAIN_ANY = '{domain-any}';

  /**
   * The concurrent URL negotiation config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Cached configuration only for the negotiations.
   *
   * @var array
   */
  protected $urlConfig;

  /**
   * UrlNegotiationConfig constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *    Configuration factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->getEditable('concurrent_url_negotiation.config');
  }

  /**
   * Determines whether cross domain authentication is possible.
   *
   * @return bool
   *    Possible or not.
   */
  public function isCrossAuthEnabled() {
    return (bool) $this->config->get('cross_auth_enabled');
  }

  /**
   * Determines whether cross domain authentication is enabled.
   *
   * @return bool
   *    Enabled or not.
   */
  public function isCrossAuthPossible() {
    return (bool) $this->config->get('cross_auth_possible');
  }

  /**
   * Gets a list of distinct domains from the negotiations.
   *
   * @return array
   *    Distinct domains.
   */
  public function getDistinctDomains() {
    // Acquire all domain names set for negotiations.
    $domains = [];
    foreach ($this->getNegotiations() as $negotiation) {
      $domains[] = $negotiation['domain'];
    }

    // Remove duplicate domains.
    $domains = array_unique($domains);

    // As DOMAIN_ANY matches all domains it is not a distinct, so remove it.
    if (($key = array_search(static::DOMAIN_ANY, $domains)) !== FALSE) {
      unset($domains[$key]);
    }

    return $domains;
  }

  /**
   * Gets the URL negotiations for each language.
   *
   * @return mixed
   *    Negotiations in format of {lang-code} => [domain => .., prefixes => ..].
   */
  public function getNegotiations() {
    if (empty($this->urlConfig)) {
      $this->urlConfig = $this->config->get('url_negotiations') ?: [];
    }

    return $this->urlConfig;
  }

  /**
   * Sets the URL negotiations in a controlled manner.
   *
   * @param mixed $negotiations
   *    The URL negotiations.
   *
   * @throws ConfigException
   *    When wrong negotiation format is provided.
   */
  public function setNegotiations($negotiations) {
    foreach ($negotiations as $negotiation) {
      if (
        !array_key_exists('prefixes', $negotiation) ||
        !array_key_exists('domain', $negotiation) ||
        !is_array($negotiation['prefixes']) ||
        !is_string($negotiation['domain'])
      ) {
        throw new ConfigException('Malformed URL negotiation configuration save attempt.');
      }
    }

    // When updating negotiations always determine whether cross authentication
    // is possible and save it in configuration as-well for performance reasons.
    $this->urlConfig = $negotiations;
    $this->config
      ->set('url_negotiations', $negotiations)
      ->set('cross_auth_possible', count($this->getDistinctDomains()) > 1)
      ->save();
  }

  /**
   * Enables or disables cross domain authentication.
   *
   * @param bool $state
   *    TRUE = enabled, FALSE = disabled.
   *
   * @throws ConfigException
   *    When $state is not of type boolean.
   */
  public function setCrossAuthState($state) {
    if (!is_bool($state)) {
      throw new ConfigException('Cross domain authentication state should be a boolean.');
    }

    $this->config->set('cross_auth_enabled', $state)->save();
  }

}
