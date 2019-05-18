<?php

/**
 * @file
 * Contains Drupal\lockr\Plugin\KeyProvider\LockrKeyProvider.
 */

namespace Drupal\lockr\Plugin\KeyProvider;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Lockr\Exception\LockrApiException;
use Lockr\Lockr;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\key\KeyInterface;
use Drupal\key\Plugin\KeyPluginFormInterface;
use Drupal\key\Plugin\KeyProviderBase;
use Drupal\key\Plugin\KeyProviderSettableValueInterface;

/**
 * Adds a key provider that allows a key to be stored in Lockr.
 *
 * @KeyProvider(
 *   id = "lockr",
 *   label = "Lockr",
 *   description = @Translation("The Lockr key provider stores the key in Lockr key management service."),
 *   storage_method = "lockr",
 *   key_value = {
 *     "accepted" = TRUE,
 *     "required" = TRUE
 *   }
 * )
 */
class LockrKeyProvider extends KeyProviderBase implements KeyProviderSettableValueInterface, KeyPluginFormInterface {

  /** @var ConfigFactoryInterface */
  protected $configFactory;

  /** @var Lockr */
  protected $lockr;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    Lockr $lockr
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configFactory = $config_factory;
    $this->lockr = $lockr;
  }

  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('lockr.lockr')
    );
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
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ) {
    $info = $this->lockr->getInfo();

    if (!$info) {
      $form['need_register'] = [
        '#prefix' => '<p>',
        '#markup' => $this->t('This site has not yet registered with Lockr, please <a href="@link">click here to register</a>.',
          ['@link' => Url::fromRoute('lockr.admin')->toString()]),
        '#suffix' => '</p>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue(KeyInterface $key) {
    return $this->lockr->getSecretValue($key->id());
  }

  /**
   * {@inheritdoc}
   */
  public function setKeyValue(KeyInterface $key, $key_value) {
    $this->lockr->createSecretValue(
      $key->id(),
      $key_value,
      $key->label(),
      $this->configFactory->get('lockr.settings')->get('region')
    );
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteKeyValue(KeyInterface $key) {
    $this->lockr->deleteSecretValue($key->id());
    return TRUE;
  }

}
