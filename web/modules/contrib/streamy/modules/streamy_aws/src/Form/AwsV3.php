<?php

namespace Drupal\streamy_aws\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\streamy\StreamyFactory;
use Drupal\streamy\StreamyFormTrait;
use Drupal\streamy\StreamyStreamManager;
use League\Flysystem\MountManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AwsV3.
 *
 * @package Drupal\streamy_ui\Form
 */
class AwsV3 extends ConfigFormBase {

  use StreamyFormTrait;

  const PLUGIN_ID = 'awsv3';

  /**
   * @var \Drupal\streamy\StreamyFactory
   */
  protected $streamyFactory;

  /**
   * @var \Drupal\streamy\StreamyStreamManager
   */
  protected $streamyStreamManager;

  /**
   * AwsV3 constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\streamy\StreamyFactory             $streamyFactory
   * @param \Drupal\streamy\StreamyStreamManager       $streamyStreamManager
   */
  public function __construct(ConfigFactoryInterface $config_factory, StreamyFactory $streamyFactory, StreamyStreamManager $streamyStreamManager) {
    parent::__construct($config_factory);
    $this->setUp($streamyFactory, $streamyStreamManager);
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('streamy.factory'),
      $container->get('plugin.manager.streamy.streamystreammanager')
    );
  }

  /**
   * @param \Drupal\streamy\StreamyFactory       $streamyFactory
   * @param \Drupal\streamy\StreamyStreamManager $streamyStreamManager
   */
  protected function setUp(StreamyFactory $streamyFactory, StreamyStreamManager $streamyStreamManager) {
    $this->streamyFactory = $streamyFactory;
    $this->streamyStreamManager = $streamyStreamManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'streamy_aws.awsv3',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'streamystream_awsv3';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('streamy_aws.awsv3');
    $pluginConfig = (array) $config->get('plugin_configuration');

    $schemes = $this->streamyFactory->getSchemesSettings();
    $levels = $this->streamyFactory->getSchemeLevels();

    foreach ($schemes as $scheme => $schemeConfig) {
      $schemeType = $this->schemeIsPrivate($schemeConfig) ? 'Private' :
        'Public';
      $form[$scheme] = [
        '#type'  => 'fieldset',
        '#title' => '<strong>' . strip_tags($this->getSchemeSetting('name', $schemeConfig)) . ' (' . $schemeType . ')' . '</strong>',
        '#tree'  => TRUE,
      ];

      $form[$scheme]['streamy_protocol_markup'] = [
        '#markup' => '<h6>' . $this->getProtocol($scheme) . '</h6>',
      ];
      foreach ($levels as $level) {
        $form[$scheme][$level]['streamy_level_markup'] = [
          '#markup' => $this->t('<hr><h3>Configuration for: @level</h3>', ['@level' => ucfirst($level)]),
        ];
        $form[$scheme][$level]['streamy_markup'] = [
          '#markup' => '<p>' . strip_tags($this->getSchemeSetting('longDescription', $schemeConfig)) . '</p>',
        ];

        $form[$scheme][$level]['aws_key'] = [
          '#type'          => 'textfield',
          '#title'         => $this->t('AWS Key'),
          '#description'   => $this->t('Your AWS key.'),
          '#default_value' => $this->getPluginConfigurationValue('aws_key', $scheme, $level, $pluginConfig),
        ];
        $form[$scheme][$level]['aws_secret'] = [
          '#type'          => 'textfield',
          '#title'         => $this->t('AWS Secret'),
          '#description'   => $this->t('Your AWS secret.'),
          '#default_value' => $this->getPluginConfigurationValue('aws_secret', $scheme, $level, $pluginConfig),
        ];
        $form[$scheme][$level]['aws_region'] = [
          '#type'          => 'select',
          '#options'       => $this->getAwsRegions(),
          '#title'         => $this->t('AWS Region'),
          '#default_value' => $this->getPluginConfigurationValue('aws_region', $scheme, $level, $pluginConfig),
        ];
        $form[$scheme][$level]['aws_bucket'] = [
          '#type'          => 'textfield',
          '#title'         => $this->t('Bucket name'),
          '#description'   => $this->t('Make sure to have the right read/write permissions on this bucket in order to avoid unpredictable behaviors by using this stream.'),
          '#default_value' => $this->getPluginConfigurationValue('aws_bucket', $scheme, $level, $pluginConfig),
        ];
        $form[$scheme][$level]['aws_prefix'] = [
          '#type'          => 'textfield',
          '#title'         => $this->t('Bucket prefix (optional)'),
          '#default_value' => $this->getPluginConfigurationValue('aws_prefix', $scheme, $level, $pluginConfig),
        ];
        $form[$scheme][$level]['slow_stream'] = [
          '#type'          => 'checkbox',
          '#title'         => $this->t('Mark as slow Stream'),
          '#description'   => $this->t('Marking this stream as a <strong>slow stream</strong> will avoid to serve file through it.<br>'
                                       .
                                       'By unticking this option you are allowing the files stored on this stream to be accessible through an URL in the front end.<br>'
                                       .
                                       '<strong>Note: Make sure to enable this checkbox on streams that are powerful enough to serve files without bandwidth or performance issues.</strong>'),
          '#default_value' => $this->getPluginConfigurationValue('slow_stream', $scheme, $level, $pluginConfig),
        ];
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * @return array
   */
  protected function getAwsRegions() {
    return [
      'eu-central-1'   => 'EU (Frankfurt)',
      'eu-west-1'      => 'EU (Ireland)',
      'ap-northeast-2' => 'Asia Pacific (Seoul)',
      'ap-southeast-1' => 'Asia Pacific (Singapore)',
      'ap-southeast-2' => 'Asia Pacific (Sydney)',
      'ap-northeast-1' => 'Asia Pacific (Tokyo)',
      'us-east-1'      => 'US East (N. Virginia)',
      'us-east-2'      => 'US East (Ohio)',
      'us-west-2'      => 'US West (Oregon)',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();
    $availableSchemes = $this->streamyFactory->getSchemes();
    foreach ($values as $scheme => $streamLevels) {
      if (is_string($scheme) && in_array($scheme, $availableSchemes)) {
        foreach ($streamLevels as $level => $streamValues) {
          // Check if this schema has become mandatory
          //        if ($form_state->getValue())
          // Manually check if the current stream has been filled in any of the fields.
          // If so we must consider this as a usable set of config.

          if ($this->checkIfAnyOfThisValuesIsFilled($streamValues, ['aws_key', 'aws_secret', 'aws_bucket'])) {
            // Making bucket name mandatory
            if (empty($form_state->getValue([$scheme, $level, 'aws_bucket']))) {
              $form_state->setError($form[$scheme][$level]['aws_bucket'],
                                    $this->t('You must specify an AWS bucket in order to use this stream.'));
            }

            if (!$form_state->getErrors()) {
              // removing trailing slash on bucket name
              $form_state->setValue([$scheme, $level, 'aws_bucket'], rtrim($form_state->getValue([$scheme, $level, 'aws_bucket']), '/'));

              $ensure = $this->ensureStreamyStreamPlugin($scheme, $level, self::PLUGIN_ID, [$scheme => [$level => $streamValues]]);
              if (!$ensure instanceof MountManager) {
                $form_state->setError($form[$scheme][$level],
                                      $this->t('Failed to retrieve the file list on <strong>' . $scheme .
                                               '</strong>. The information entered for this schema '
                                               . ' may not be correct, please check your information and permissions, then try again.' .
                                               '<br>Refer to the error logger for further information.'));
              }
            }
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

    $keysToParse = [
      'aws_key',
      'aws_secret',
      'aws_region',
      'aws_bucket',
      'aws_prefix',
      'slow_stream',
    ];
    $this->config('streamy_aws.awsv3')
         ->set('plugin_configuration',
               $this->buildNestedPluginConfiguration($keysToParse, $form_state))
      //         ->set('aws_key', $form_state->getValue('aws_key'))
      //         ->set('aws_secret', $form_state->getValue('aws_secret'))
      //         ->set('aws_region', $form_state->getValue('aws_region'))
      //         ->set('aws_bucket', $form_state->getValue('aws_bucket'))
      //         ->set('aws_prefix', $form_state->getValue('aws_prefix'))
      //         ->set('slow_stream', $form_state->getValue('slow_stream'))
         ->save();
  }

}
