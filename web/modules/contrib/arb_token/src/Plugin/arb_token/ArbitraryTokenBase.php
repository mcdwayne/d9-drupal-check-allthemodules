<?php

namespace Drupal\arb_token\Plugin\arb_token;

use Drupal\arb_token\ArbitraryTokenPluginInterface;
use Drupal\arb_token\Entity\ArbitraryToken;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ArbitraryTokenBase.
 */
abstract class ArbitraryTokenBase extends PluginBase implements ArbitraryTokenPluginInterface {

  use StringTranslationTrait;

  /**
   * The token config entity.
   *
   * @var \Drupal\arb_token\Entity\ArbitraryToken
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // TODO: Implement calculateDependencies() method.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // No op.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // No op.
  }

  /**
   * Sets the token config entity used for this plugin.
   *
   * @param \Drupal\arb_token\Entity\ArbitraryToken $token
   *   The token config entity.
   *
   * @return $this
   */
  public function setToken(ArbitraryToken $token) {
    $this->token = $token;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->getPluginDefinition()['label'];
  }

}
