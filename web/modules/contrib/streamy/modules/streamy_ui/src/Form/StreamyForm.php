<?php

namespace Drupal\streamy_ui\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\streamy\StreamyCDNManager;
use Drupal\streamy\StreamyFactory;
use Drupal\streamy\StreamyFormTrait;
use Drupal\streamy\StreamyStreamManager;
use League\Flysystem\MountManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class StreamyForm.
 *
 * @package Drupal\streamy_ui\Form
 */
class StreamyForm extends ConfigFormBase {

  use StreamyFormTrait;

  /**
   * @var \Drupal\streamy\StreamyStreamManager
   */
  protected $streamyStreamManager;

  /**
   * @var \Drupal\streamy\StreamyCDNManager
   */
  protected $streamyCDNManager;

  /**
   * @var \Drupal\streamy\StreamyFactory
   */
  protected $streamyFactory;

  /**
   * StreamyForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\streamy\StreamyStreamManager       $streamyStreamManager
   * @param \Drupal\streamy\StreamyCDNManager          $streamyCDNManager
   * @param \Drupal\streamy\StreamyFactory             $streamyFactory
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              StreamyStreamManager $streamyStreamManager,
                              StreamyCDNManager $streamyCDNManager,
                              StreamyFactory $streamyFactory) {
    parent::__construct($config_factory);

    $this->streamyStreamManager = $streamyStreamManager;
    $this->streamyCDNManager = $streamyCDNManager;
    $this->setUp($streamyFactory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.streamy.streamystreammanager'),
      $container->get('plugin.manager.streamy.streamycdnmanager'),
      $container->get('streamy.factory')
    );
  }

  /**
   * @param \Drupal\streamy\StreamyFactory $streamyFactory
   */
  protected function setUp(StreamyFactory $streamyFactory) {
    $this->streamyFactory = $streamyFactory;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'streamy.streamy',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'streamy_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('streamy.streamy');
    $pluginConfig = (array) $config->get('plugin_configuration');

    $schemes = $this->streamyFactory->getSchemesSettings();

    foreach ($schemes as $scheme => $schemeConfig) {
      $schemeType = $this->schemeIsPrivate($schemeConfig) ? 'Private' :
        'Public';
      $form[$scheme] = [
        '#type'  => 'fieldset',
        '#title' => '<h2>' . strip_tags($this->getSchemeSetting('name', $schemeConfig)) . ' (' . $schemeType . ')' . '</h2>',
        '#tree'  => TRUE,
      ];
      $form[$scheme]['streamy_markup'] = [
        '#markup' => '<p>' . strip_tags($this->getSchemeSetting('longDescription', $schemeConfig)) . '</p>',
      ];
      $form[$scheme]['streamy_protocol_markup'] = [
        '#markup' => '<h6>' . $this->getProtocol($scheme) . '</h6>',
      ];

      $form[$scheme]['master'] = [
        '#type'          => 'select',
        '#options'       => $this->getStreamyStreamPlugins(),
        '#title'         => $this->t('Master Stream'),
        '#description'   => $this->t('- Read will happen first<br>- A quick stream should be selected here to keep high your site performance<br>'
                                     . '- Write operations happen in sync with <i>Slave</i>'),
        '#default_value' => $this->getPluginConfigurationSingleValue('master', $scheme, $pluginConfig),
      ];
      $form[$scheme]['slave'] = [
        '#type'          => 'select',
        '#options'       => $this->getStreamyStreamPlugins(),
        '#title'         => $this->t('Slave Stream'),
        '#description'   => $this->t('- This is a replica/fallback stream<br>- Read on this stream will only happen if the Master Stream fails<br>'
                                     . '- Write operations happen in sync with <i>Master</i>'),
        '#default_value' => $this->getPluginConfigurationSingleValue('slave', $scheme, $pluginConfig),
      ];

      // If no CDN plugin do not show the select but a message.
      if (!$this->schemeIsPrivate($schemeConfig)) {
        $CDNPluginsAreAvailable = $this->streamyCDNManager->getDefinitions();
        if ($CDNPluginsAreAvailable) {
          $form[$scheme]['cdn_markup'] = [
            '#markup' => $this->t('<h2>CDN</h2>'),
          ];
          $form[$scheme]['cdn_wrapper'] = [
            '#type'          => 'select',
            '#options'       => $this->getStreamyCDNPlugins($scheme),
            '#title'         => $this->t('CDN Wrapper'),
            '#description'   => $this->t('If a CDN is selected the content will be always served through it so make sure to have your CDN in sync with the storage stream.'),
            '#default_value' => $this->getPluginConfigurationSingleValue('cdn_wrapper',
                                                                         $scheme,
                                                                         $pluginConfig),
          ];
        } else {
          $form[$scheme]['cdn_markup'] = [
            '#markup' => $this->t('<strong>There is no CDN plugin installed.</strong>'),
          ];
        }
      }
      $form[$scheme]['disableFallbackCopy'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Disable Fallback Copy'),
        '#description'   => $this->t('By default in the reading process if a file is not found in the main stream it will be copied from the slave stream.' .
                                     '<br>By ticking this option a file will never be copied on the master stream if this is missing for any reason.'),
        '#default_value' => $this->getPluginConfigurationSingleValue('disableFallbackCopy',
                                                                     $scheme,
                                                                     $pluginConfig),
      ];
      $form[$scheme]['enabled'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Enable Stream'),
        '#description'   => $this->t('Make this stream available to be used as storage'),
        '#default_value' => $this->getPluginConfigurationSingleValue('enabled',
                                                                     $scheme,
                                                                     $pluginConfig),
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * @return array
   */
  protected function getStreamyCDNPlugins($scheme) {
    $plugins = [];
    $plugins[''] = $this->t('- Disabled -');
    $pluginDefinitions = $this->streamyCDNManager->getDefinitions();
    foreach ($pluginDefinitions as $pluginName => $pluginValue) {
      $plugin = $this->streamyCDNManager->createInstance($pluginName);
      if ($plugin->ensure($scheme)) {
        $plugins[$pluginName] = $pluginValue['name'];
      }
    }
    return $plugins;
  }

  /**
   * @return array
   */
  protected function getStreamyStreamPlugins() {
    $plugins = [];
    $plugins[''] = $this->t('- Disabled -');
    $pluginDefinitions = $this->streamyStreamManager->getDefinitions();
    foreach ($pluginDefinitions as $pluginName => $pluginValue) {
      $plugins[$pluginName] = $pluginValue['name'];
    }
    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // If these are not equal, then we're adding a new context and should not override an existing context.
    $values = $form_state->getValues();
    foreach ($values as $scheme => $streamValues) {
      if (is_array($streamValues) && array_key_exists('master', $streamValues)) {
        //        if (!empty($form_state->getValue([$scheme, 'master'])) &&
        //            ($form_state->getValue([$scheme, 'master']) == $form_state->getValue([$scheme, 'slave']))
        //        ) {
        //          $form_state->setError($form[$scheme]['slave'],
        //                                $this->t('<strong>Slave stream</strong> can\'t be equal to <strong>Master stream</strong>. Please select another stream and try again.'));
        //        }

        // Check whether the selected Master plugin can be set as Master
        if ($this->streamyStreamManager->hasDefinition($form_state->getValue([$scheme, 'master']))
        ) {
          $plugin = $this->streamyStreamManager->createInstance($form_state->getValue([$scheme, 'master']));
          if (!$plugin->allowAsMasterStream()) {
            $form_state->setError($form[$scheme]['master'],
                                  $this->t('The selected plugin cannot be set as <strong>Master stream</strong>. Please select another master stream and try again.'));
          }
        }

        if ((empty($form_state->getValue([$scheme, 'master'])) && !empty($form_state->getValue([$scheme, 'slave']))) ||
            (empty($form_state->getValue([$scheme, 'slave'])) && !empty($form_state->getValue([$scheme, 'master'])))
        ) {
          $form_state->setError($form[$scheme],
                                $this->t('You must select both <strong>Master</strong> and <strong>Slave</strong> in order to get a stream working.'));
        }

        if ($this->checkIfAnyOfThisValuesIsFilled($streamValues, ['master', 'slave', 'cdn_wrapper', 'enabled']) &&
            !$form_state->getErrors()
        ) {
          $this->ensurePluginsAreWorking($scheme, $form, $form_state);
        }
      }

    }
  }

  /**
   * @param string                               $scheme
   * @param array                                $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function ensurePluginsAreWorking(string $scheme, array &$form, FormStateInterface $form_state) {
    $typesToEnsure = $this->getStreamTypesToEnsure();
    foreach ($typesToEnsure as $key => $settings) {
      if ($settings['type'] == 'mount') {
        $ensureResult = $this->ensureStreamyStreamPlugin($scheme, $key, $form_state->getValue([$scheme, $key]));
        $valid = $ensureResult instanceof MountManager;
      } else if ($settings['type'] == 'cdn') {
        $cdn = $form_state->getValue([$scheme, $key]);
        // CDN is not mandatory
        if (!$cdn) {
          continue;
        }
        $ensureResult = $this->ensureStreamyCDNPlugin($scheme, $cdn);
        $valid = (bool) $ensureResult;
      } else {
        $valid = FALSE;
      }
      if (!$valid) {
        $form_state->setError($form[$scheme][$key],
                              $this->t('The selected plugin <strong>%plugin</strong> for the schema <strong>%scheme</strong>:<strong>%key</strong> '
                                       . 'seems to be not correctly configured.'
                                       . ' Please check the plugin settings and try to save this page again.',
                                       ['%scheme' => $scheme, '%key' => $key, '%plugin' => $form_state->getValue([$scheme, $key])]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $keysToParse = ['master', 'slave', 'cdn_wrapper', 'enabled', 'disableFallbackCopy'];

    $this->config('streamy.streamy')
         ->set('plugin_configuration', $this->buildNestedPluginConfiguration($keysToParse, $form_state, FALSE))
         ->save();
  }

  /**
   * Returns the types of stream to verify with its type (mount|cdn).
   *
   * @return array
   */
  protected function getStreamTypesToEnsure() {
    return [
      'master'      => [
        'type' => 'mount',
      ],
      'slave'       => [
        'type' => 'mount',
      ],
      'cdn_wrapper' => [
        'type' => 'cdn',
      ],
    ];
  }

}
