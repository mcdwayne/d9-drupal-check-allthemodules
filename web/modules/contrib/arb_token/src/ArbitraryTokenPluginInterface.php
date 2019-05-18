<?php

namespace Drupal\arb_token;

use Drupal\arb_token\Entity\ArbitraryToken;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Interface ArbitraryTokenPluginInterface.
 */
interface ArbitraryTokenPluginInterface extends ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Sets the token entity.
   *
   * @return $this
   */
  public function setToken(ArbitraryToken $token);

  /**
   * Gets the token type.
   *
   * @return string
   *   A string identifying the token type, and possibly configuration.
   */
  public function getType();

  /**
   * Sets the string translation service to use.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   *
   * @return $this
   */
  public function setStringTranslation(TranslationInterface $translation);

  /**
   * Provide information about the placeholder token.
   *
   * @see \hook_token_info()
   *
   * @return array
   *   An associative array declaring the token.
   */
  public function tokenInfo();

  /**
   * Provide replacement values for placeholder tokens.
   *
   * @see \hook_tokens()
   *
   * @return array
   *   An associative array of replacement values.
   */
  public function tokens($tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata);

}
