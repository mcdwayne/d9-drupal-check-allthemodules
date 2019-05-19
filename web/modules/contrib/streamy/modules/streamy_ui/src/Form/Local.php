<?php

namespace Drupal\streamy_ui\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\streamy\StreamyFactory;
use Drupal\streamy\StreamyFormTrait;
use Drupal\streamy\StreamyStreamManager;
use League\Flysystem\MountManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Local.
 *
 * @package Drupal\streamy_ui\Form
 */
class Local extends ConfigFormBase {

  use StreamyFormTrait;

  const PLUGIN_ID = 'local';

  /**
   * @var \Drupal\streamy\StreamyFactory
   */
  protected $streamyFactory;

  /**
   * Local constructor.
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
      'streamy.local',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'streamystream_local';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('streamy.local');
    $pluginConfig = (array) $config->get('plugin_configuration');

    $schemes = $this->streamyFactory->getSchemesSettings();
    $levels = $this->streamyFactory->getSchemeLevels();

    foreach ($schemes as $scheme => $schemeConfig) {
      if ($this->schemeIsPrivate($schemeConfig)) {
        $localPathMsg = 'Insert a local path that is not reachable by your web server (private path).';
      } else {
        $localPathMsg = 'Insert a local path relative to this Drupal installation (e.g. sites/default/files/mystorage).</i>';
      }

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

        $form[$scheme][$level]['root'] = [
          '#type'          => 'textfield',
          '#title'         => $this->t('Local path'),
          '#description'   => $this->t('@localpathmsg<br> - Make sure to have the correct read/write permissions on this folder.', ['@localpathmsg' => $localPathMsg]),
          '#default_value' => $this->getPluginConfigurationValue('root', $scheme, $level, $pluginConfig),
        ];
        $form[$scheme][$level]['slow_stream'] = [
          '#type'          => 'checkbox',
          '#title'         => $this->t('Mark as slow Stream'),
          '#description'   => $this->t('Marking this stream as a <strong>slow stream</strong> will avoid to serve files through it.<br>'
                                       .
                                       'By unticking this option you are allowing the files stored on this stream to be accessible through a URL in the front end.<br>'
                                       .
                                       '<strong>Note: Make sure to enable this checkbox on streams that are powerful enough to serve files without bandwidth or performance issues.</strong>'),
          '#default_value' => $this->getPluginConfigurationValue('slow_stream', $scheme, $level, $pluginConfig),
        ];
      }
    }

    return parent::buildForm($form, $form_state);
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
          if (is_array($streamValues) && array_key_exists('root', $streamValues)) {

            if ($this->checkIfAnyOfThisValuesIsFilled($streamValues, ['root']) && !$form_state->getErrors()) {
              // Removing trailing slash on root
              $form_state->setValue([$scheme, $level, 'root'], rtrim($form_state->getValue([$scheme, $level, 'root']), '/'));

              $ensure = $this->ensureStreamyStreamPlugin($scheme, $level, self::PLUGIN_ID, [$scheme => [$level => $streamValues]]);
              if (!$ensure instanceof MountManager) {
                $form_state->setError($form[$scheme],
                                      $this->t('Failed to retrieve the file list on <strong>%scheme'
                                               . '</strong>. The information entered for this schema '
                                               . ' may not be correct, please check your information and permissions, then try again.' .
                                               '<br>Refer to the error logger for further information.',
                                               ['%scheme' => $scheme]));
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

    $keysToParse = ['root', 'slow_stream'];
    $this->config('streamy.local')
         ->set('plugin_configuration', (array) $this->buildNestedPluginConfiguration($keysToParse, $form_state))
         ->save();
  }

}
