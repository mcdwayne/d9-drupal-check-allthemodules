<?php

namespace Drupal\streamy_dropbox\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\streamy\StreamyFactory;
use Drupal\streamy\StreamyFormTrait;
use Drupal\streamy\StreamyStreamManager;
use League\Flysystem\MountManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Dropbox.
 *
 * @package Drupal\streamy_ui\Form
 */
class Dropbox extends ConfigFormBase {

  use StreamyFormTrait;

  const PLUGIN_ID = 'dropbox';

  /**
   * @var \Drupal\streamy\StreamyFactory
   */
  protected $streamyFactory;

  /**
   * @var \Drupal\streamy\StreamyStreamManager
   */
  protected $streamyStreamManager;

  /**
   * Dropbox constructor.
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
   * @param \Drupal\streamy\StreamyFactory $streamyFactory
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
      'streamy_dropbox.dropbox',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dropbox_stream';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('streamy_dropbox.dropbox');
    $pluginConfig = (array) $config->get('plugin_configuration');

    $schemes = $this->streamyFactory->getSchemesSettings();
    $levels = $this->streamyFactory->getSchemeLevels();

    foreach ($schemes as $scheme => $schemeConfig) {
      $schemeType = $this->schemeIsPrivate($schemeConfig) ? 'Private' :
        'Public';
      $form[$scheme] = [
        '#type'  => 'fieldset',
        '#title' => '<strong>' . strip_tags($this->getSchemeSetting('name', $schemeConfig)) . ' (' . $schemeType . ')' .
                    '</strong>',
        '#tree'  => TRUE,
      ];

      $form[$scheme]['streamy_protocol_markup'] = [
        '#markup' => '<h6>' . $this->getProtocol($scheme) . '</h6>',
      ];
      foreach ($levels as $level) {
        $form[$scheme][$level]['streamy_level_markup'] = [
          '#markup' => '<hr><h3>' . $this->t('Configuration for: ') . ucfirst($level) . '</h3>',
        ];
        $form[$scheme][$level]['streamy_markup'] = [
          '#markup' => '<p>' . strip_tags($this->getSchemeSetting('longDescription', $schemeConfig)) . '</p>',
        ];

        $form[$scheme][$level]['accesstoken'] = [
          '#type'          => 'textfield',
          '#size'          => 64,
          '#title'         => $this->t('Dropbox OAuth2 Token'),
          '#description'   => $this->t('Your Dropbox access token. You may need to create an app in order to get one.<br>' .
                                       'Please refer to : <a href="https://www.dropbox.com/developers/apps" target="_blank">https://www.dropbox.com/developers/apps</a>'),
          '#default_value' => $this->getPluginConfigurationValue('accesstoken', $scheme, $level, $pluginConfig),
        ];
        $form[$scheme][$level]['secret'] = [
          '#type'          => 'textfield',
          '#size'          => 32,
          '#title'         => $this->t('Dropbox Secret'),
          '#description'   => $this->t('Your Dropbox secret.'),
          '#default_value' => $this->getPluginConfigurationValue('secret', $scheme, $level, $pluginConfig),
        ];
        $form[$scheme][$level]['prefix'] = [
          '#type'          => 'textfield',
          '#size'          => 32,
          '#title'         => $this->t('Path Prefix (optional)'),
          '#default_value' => $this->getPluginConfigurationValue('prefix', $scheme, $level, $pluginConfig),
        ];
        $form[$scheme][$level]['slow_stream'] = [
          '#type'          => 'checkbox',
          '#title'         => $this->t('Mark as Slow Stream'),
          '#description'   => $this->t('Marking this stream as a <strong>Slow Stream</strong> will avoid to serve file through it.<br>'
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
          if (is_array($streamValues) && array_key_exists('accesstoken', $streamValues)) {

            if ($this->checkIfAnyOfThisValuesIsFilled($streamValues, ['accesstoken', 'secret']) && !$form_state->getErrors()) {
              $ensure = $this->ensureStreamyStreamPlugin($scheme, $level, self::PLUGIN_ID, [$scheme => [$level => $streamValues]]);

              if (!$ensure instanceof MountManager) {
                $form_state->setError($form[$scheme][$level],
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

    $keysToParse = ['accesstoken', 'secret', 'prefix', 'slow_stream'];
    $this->config('streamy_dropbox.dropbox')
         ->set('plugin_configuration',
               $this->buildNestedPluginConfiguration($keysToParse, $form_state))
      //         ->set('accesstoken', $form_state->getValue('accesstoken'))
      //         ->set('secret', $form_state->getValue('secret'))
      //         ->set('prefix', $form_state->getValue('prefix'))
      //         ->set('slow_stream', $form_state->getValue('slow_stream'))
         ->save();
  }

}
