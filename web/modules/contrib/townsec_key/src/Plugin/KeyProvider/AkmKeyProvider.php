<?php

namespace Drupal\townsec_key\Plugin\KeyProvider;

use RuntimeException;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use TownsendSecurity\Akm;

use Drupal\key\KeyInterface;
use Drupal\key\Plugin\KeyProviderBase;
use Drupal\key\Plugin\KeyPluginFormInterface;

/**
 * Adds a key provider that allows a key to be stored in the Alliance
 * Key Manager.
 *
 * @KeyProvider(
 *   id = "akm",
 *   label = "Townsend AKM",
 *   description = @Translation("Access a key stored in the Townsend AKM."),
 *   storage_method = "akm",
 *   key_value = {
 *     "accepted" = FALSE,
 *     "required" = FALSE
 *   }
 * )
 */
class AkmKeyProvider extends KeyProviderBase implements KeyPluginFormInterface {

  /** @var TownsendSecurity\Akm */
  protected $akm;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    Akm $akm,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->akm = $akm;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $container->get('townsec_key.akm'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyValue(KeyInterface $key) {
    $config = $this->getConfiguration();
    $key_name = $config['key_name'];

    try {
      $key_value = $this->akm->getKeyValue($key_name);
    }
    catch (RuntimeException $e) {
      return '';
    }

    return $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'key_name' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['key_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key Name'),
      '#maxlength' => 40,
      '#default_value' => $config['key_name'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $key_name = $form_state->getValue('key_name');

    try {
      $this->akm->getKeyValue($key_name);
    }
    catch (RuntimeException $e) {
      if ($e->getCode() === 3275) {
        $form_state->setErrorByName('key_name', 'Key does not exist.');
      }
      else {
        $form_state->setErrorByName('', 'AKM connection failed.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $configFactory = \Drupal::configFactory();
    if (count($configFactory->listAll('townsec_key.akm_server.')) < 1) {
      return [$this->t('AKM key provider requires at least one AKM server.')];
    }

    return [];
  }

}
