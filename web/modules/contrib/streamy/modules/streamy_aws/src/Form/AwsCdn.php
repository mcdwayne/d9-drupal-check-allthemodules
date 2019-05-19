<?php

namespace Drupal\streamy_aws\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\streamy\StreamyCDNManager;
use Drupal\streamy\StreamyFactory;
use Drupal\streamy\StreamyFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AwsCDN.
 *
 * @package Drupal\streamy_ui\Form
 */
class AwsCdn extends ConfigFormBase {

  const PLUGIN_ID = 'awscdn';

  use StreamyFormTrait;

  /**
   * Drupal\streamy\StreamyCDNManager definition.
   *
   * @var \Drupal\streamy\StreamyCDNManager
   */
  protected $pluginManagerStreamyStreamycdnmanager;

  /**
   * @var \Drupal\streamy\StreamyFactory
   */
  protected $streamyFactory;

  /**
   * @var \Drupal\streamy\StreamyCDNManager
   */
  protected $streamyCDNManager;

  /**
   * AwsCDN constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\streamy\StreamyFactory             $streamyFactory
   */
  public function __construct(ConfigFactoryInterface $config_factory, StreamyFactory $streamyFactory, StreamyCDNManager $streamyCDNManager) {
    parent::__construct($config_factory);
    $this->setUp($streamyFactory, $streamyCDNManager);
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('streamy.factory'),
      $container->get('plugin.manager.streamy.streamycdnmanager')
    );
  }


  /**
   * @param \Drupal\streamy\StreamyFactory    $streamyFactory
   * @param \Drupal\streamy\StreamyCDNManager $streamyCDNManager
   */
  protected function setUp(StreamyFactory $streamyFactory, StreamyCDNManager $streamyCDNManager) {
    $this->streamyFactory = $streamyFactory;
    $this->streamyCDNManager = $streamyCDNManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'streamy_aws.awscdn',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cdn_awscdn';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('streamy_aws.awscdn');
    $pluginConfig = (array) $config->get('plugin_configuration');

    $schemes = $this->streamyFactory->getSchemesSettings();

    foreach ($schemes as $scheme => $schemeConfig) {
      $schemeType = $this->schemeIsPrivate($schemeConfig) ? 'Private' :
        'Public';
      $form[$scheme] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Configuration for: <strong>@schemetype</strong> (@visibility)', [
          '@schemetype' => strip_tags($this->getSchemeSetting('name', $schemeConfig)),
          '@visibility' => $schemeType,
        ]),
        '#tree'  => TRUE,
      ];

      $form[$scheme]['streamy_markup'] = [
        '#markup' => '<p>' . strip_tags($this->getSchemeSetting('longDescription', $schemeConfig)) . '</p>',
      ];
      $form[$scheme]['streamy_protocol_markup'] = [
        '#markup' => '<h6>' . $this->getProtocol($scheme) . '</h6>',
      ];
      $form[$scheme]['enabled'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('CDN Enabled'),
        '#default_value' => $this->getPluginConfigurationSingleValue('enabled', $scheme, $pluginConfig),
      ];
      $form[$scheme]['url'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('CDN Url'),
        '#description'   => $this->t('Insert your CloudFront URL without http(s)://'),
        '#size'          => 64,
        '#default_value' => $this->getPluginConfigurationSingleValue('url', $scheme, $pluginConfig),
      ];
      $form[$scheme]['https'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('HTTPS only'),
        '#description'   => $this->t('Serve files through this CDN only if the page is surfed under HTTPS'),
        '#default_value' => $this->getPluginConfigurationSingleValue('https', $scheme, $pluginConfig),
      ];

    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();

    foreach ($values as $scheme => $streamValues) {
      if (is_array($streamValues) && array_key_exists('url', $streamValues)) {
        /*
         * Check if this schema has become mandatory.
         * Manually check if the current stream has been filled in any of its fields.
         * If so we must consider this as a usable set of config.
         */
        if ($form_state->getValue([$scheme, 'enabled']) == 1 && empty(trim($form_state->getValue([$scheme, 'url'])))) {
          $form_state->setError($form[$scheme], $this->t('You need to specify a valid CDN URL in order to enable it.'));
        } elseif ($form_state->getValue([$scheme, 'enabled']) == 1) {
          // Removing trailing slash on URL.
          $form_state->setValue([$scheme, 'url'], rtrim($form_state->getValue([$scheme, 'url']), '/'));

          $ensure = $this->ensureStreamyCDNPlugin($scheme, self::PLUGIN_ID, [$scheme => $streamValues]);
          if (!$ensure) {
            $form_state->setError($form[$scheme],
                                  $this->t('Failed to validate the current CDN, please check the information entered and try again.'));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $keysToParse = ['enabled', 'url', 'https'];
    $this->config('streamy_aws.awscdn')
         ->set('plugin_configuration',
               $this->buildNestedPluginConfiguration($keysToParse, $form_state, FALSE))
         ->save();
  }

}
