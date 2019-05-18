<?php

namespace Drupal\images_optimizer\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\images_optimizer\Helper\OptimizerHelper;
use Drupal\images_optimizer\Optimizer\AbstractProcessOptimizer;
use Drupal\images_optimizer\Optimizer\JpegoptimOptimizer;
use Drupal\images_optimizer\Optimizer\PngquantOptimizer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Configuration form of our module.
 *
 * @package Drupal\images_optimizer\Form
 */
class ConfigurationForm extends ConfigFormBase {

  /**
   * The name of the vertical tabs group.
   *
   * @var string
   */
  const VERTICAL_TABS_GROUP = 'vertical_tabs';

  /**
   * The main configuration name.
   *
   * @var string
   */
  const MAIN_CONFIGURATION_NAME = 'images_optimizer.optimizer.settings';

  /**
   * The select value considered as empty when you select an optimizer.
   *
   * @var string
   */
  const GENERAL_SETTINGS_SELECT_OPTIMIZER_EMPTY_VALUE = '';

  /**
   * The optimizer helper.
   *
   * @var \Drupal\images_optimizer\Helper\OptimizerHelper
   */
  private $optimizerHelper;

  /**
   * Our pngquant optimizer.
   *
   * @var \Drupal\images_optimizer\Optimizer\PngquantOptimizer
   */
  private $pngquantOptimizer;

  /**
   * Our jpegoptim optimizer.
   *
   * @var \Drupal\images_optimizer\Optimizer\JpegoptimOptimizer
   */
  private $jpegoptimOptimizer;

  /**
   * ConfigurationForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\images_optimizer\Helper\OptimizerHelper $optimizer_helper
   *   The optimizer helper.
   * @param \Drupal\images_optimizer\Optimizer\PngquantOptimizer $pngquant_optimizer
   *   Our pngquant optimizer.
   * @param \Drupal\images_optimizer\Optimizer\JpegoptimOptimizer $jpegoptim_optimizer
   *   Our jpegoptim optimizer.
   */
  public function __construct(ConfigFactoryInterface $config_factory, OptimizerHelper $optimizer_helper, PngquantOptimizer $pngquant_optimizer, JpegoptimOptimizer $jpegoptim_optimizer) {
    parent::__construct($config_factory);

    $this->optimizerHelper = $optimizer_helper;
    $this->pngquantOptimizer = $pngquant_optimizer;
    $this->jpegoptimOptimizer = $jpegoptim_optimizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('images_optimizer.helper.optimizer'),
      $container->get('images_optimizer.optimizer.pngquant'),
      $container->get('images_optimizer.optimizer.jpegoptim')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'images_optimizer_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $optimizersBySupportedMimeType = $this->optimizerHelper->getBySupportedMimeType();
    if (empty($optimizersBySupportedMimeType)) {
      $form['message'] = [
        '#type' => 'markup',
        '#markup' => $this->t('There is no registered optimizers.'),
      ];

      return $form;
    }

    $form = parent::buildForm($form, $form_state);

    $form[self::VERTICAL_TABS_GROUP] = ['#type' => 'vertical_tabs'];

    $mainConfiguration = $this->config(self::MAIN_CONFIGURATION_NAME);

    $this->addGeneralSettings($form, $optimizersBySupportedMimeType, $mainConfiguration);

    $rawMainConfiguration = $mainConfiguration->getRawData();

    $this->addPngquantOptimizerSettings($form, $rawMainConfiguration);
    $this->addJpegoptimOptimizerSettings($form, $rawMainConfiguration);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::MAIN_CONFIGURATION_NAME,
      $this->pngquantOptimizer->getConfigurationName(),
      $this->jpegoptimOptimizer->getConfigurationName(),
    ];
  }

  /**
   * Add the general settings to the configuration form.
   *
   * @param array $form
   *   The structure of the main configuration form.
   * @param array $optimizersBySupportedMimeType
   *   The optimizers by supported by mime type.
   * @param \Drupal\Core\Config\Config $mainConfiguration
   *   The main configuration (editable).
   */
  private function addGeneralSettings(array &$form, array $optimizersBySupportedMimeType, Config $mainConfiguration) {
    ksort($optimizersBySupportedMimeType);

    $form['mime_types'] = [
      '#type' => 'value',
      '#value' => array_keys($optimizersBySupportedMimeType),
    ];

    $form['general_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#group' => self::VERTICAL_TABS_GROUP,
    ];

    $form['general_settings']['select_optimizers'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select the optimizers you want to use for each supported mime type'),
    ];

    foreach ($optimizersBySupportedMimeType as $mime_type => $optimizers) {
      $form['general_settings']['select_optimizers'][$mime_type] = [
        '#type' => 'select',
        '#title' => $mime_type,
        '#empty_option' => $this->t('- None -'),
        '#empty_value' => self::GENERAL_SETTINGS_SELECT_OPTIMIZER_EMPTY_VALUE,
        '#options' => $this->getSelectOptimizersOptions($optimizers),
        '#default_value' => $mainConfiguration->get($mime_type),
      ];
    }

    $form['#submit'][] = function ($form, FormStateInterface $form_state) use ($mainConfiguration) {
      foreach ($form_state->getValue('mime_types') as $mime_type) {
        $value = $form_state->getValue($mime_type, self::GENERAL_SETTINGS_SELECT_OPTIMIZER_EMPTY_VALUE);
        if (self::GENERAL_SETTINGS_SELECT_OPTIMIZER_EMPTY_VALUE === $value) {
          $value = NULL;
        }

        $mainConfiguration->set($mime_type, $value);
      }

      $mainConfiguration->save(TRUE);
    };
  }

  /**
   * Get the built select options for the optimizers selection.
   *
   * If two optimizers have the same name, their label will be suffixed with
   * their service id.
   *
   * @param \Drupal\images_optimizer\Optimizer\OptimizerInterface[] $optimizers
   *   The optimizers indexed by their service id (value => label).
   *
   * @return array
   *   The select options.
   */
  private function getSelectOptimizersOptions(array $optimizers) {
    $stackByName = [];

    /** @var \Drupal\images_optimizer\Optimizer\OptimizerInterface $optimizer */
    foreach ($optimizers as $serviceId => $optimizer) {
      $name = $optimizer->getName();
      if (!isset($stackByName[$name])) {
        $stackByName[$name] = [];
      }

      $stackByName[$name][] = $serviceId;
    }

    $options = [];
    foreach ($stackByName as $name => $serviceIds) {
      if (count($serviceIds) > 1) {
        foreach ($serviceIds as $serviceId) {
          $options[$serviceId] = sprintf('%s (%s)', $name, $serviceId);
        }
      }

      $options[reset($serviceIds)] = $name;
    }

    return $options;
  }

  /**
   * Add our pngquant optimizer settings to the configuration form.
   *
   * @param array $form
   *   The structure of the main configuration form.
   * @param array $rawMainConfiguration
   *   The raw main configuration.
   */
  private function addPngquantOptimizerSettings(array &$form, array $rawMainConfiguration) {
    if (!in_array(PngquantOptimizer::SERVICE_ID, $rawMainConfiguration)) {
      return;
    }

    list($root_key, $form_config_keys) = $this->prepareProcessOptimizerSettings(PngquantOptimizer::SERVICE_ID, $this->pngquantOptimizer, $form);

    $sub_form = &$form[$root_key];
    $sub_form[$form_config_keys['binary_path']] += [
      '#type' => 'textfield',
      '#title' => $this->t('Binary path'),
      '#description' => $this->t('The full path to the pngquant binary on your server.'),
    ];

    $sub_form[$form_config_keys['minimum_quality']] += [
      '#type' => 'number',
      '#title' => $this->t('Minimum quality'),
      '#description' => $this->t('Corresponds to the "min" of the "--quality" option. Please check the pngquant documentation for more information.'),
      '#min' => 1,
      '#max' => 100,
    ];

    $sub_form[$form_config_keys['maximum_quality']] += [
      '#type' => 'number',
      '#title' => $this->t('Maximum quality'),
      '#description' => $this->t('Corresponds to the "max" of the "--quality" option. Please check the pngquant documentation for more information.'),
      '#min' => 1,
      '#max' => 100,
    ];

    $sub_form[$form_config_keys['timeout']] += [
      '#type' => 'number',
      '#title' => $this->t('Timeout'),
      '#description' => $this->t('The process timeout in seconds.'),
      '#min' => 1,
    ];
  }

  /**
   * Add our jpegoptim optimizer settings to the configuration form.
   *
   * @param array $form
   *   The structure of the main configuration form.
   * @param array $rawMainConfiguration
   *   The raw main configuration.
   */
  private function addJpegoptimOptimizerSettings(array &$form, array $rawMainConfiguration) {
    if (!in_array(JpegoptimOptimizer::SERVICE_ID, $rawMainConfiguration)) {
      return;
    }

    list($root_key, $form_config_keys) = $this->prepareProcessOptimizerSettings(JpegoptimOptimizer::SERVICE_ID, $this->jpegoptimOptimizer, $form);

    $sub_form = &$form[$root_key];
    $sub_form[$form_config_keys['binary_path']] += [
      '#type' => 'textfield',
      '#title' => $this->t('Binary path'),
      '#description' => $this->t('The full path to the jpegoptim binary on your server.'),
    ];

    $sub_form[$form_config_keys['quality']] += [
      '#type' => 'number',
      '#title' => $this->t('Quality'),
      '#description' => $this->t('Corresponds to the "max" option. Please check the jpegoptim documentation for more information.'),
      '#min' => 1,
      '#max' => 100,
    ];

    $sub_form[$form_config_keys['timeout']] += [
      '#type' => 'number',
      '#title' => $this->t('Timeout'),
      '#description' => $this->t('The process timeout in seconds.'),
      '#min' => 1,
    ];
  }

  /**
   * Prepare the main configuration form for a process optimizer sub form.
   *
   * @param string $serviceId
   *   The process optimizer service id.
   * @param \Drupal\images_optimizer\Optimizer\AbstractProcessOptimizer $processOptimizer
   *   The process optimizer.
   * @param array $form
   *   The structure of the main configuration form.
   *
   * @return array
   *   An array containing the sub form root key and its config keys.
   */
  private function prepareProcessOptimizerSettings($serviceId, AbstractProcessOptimizer $processOptimizer, array &$form) {
    $root_key = str_replace('.', '_', $serviceId);
    $configuration_name = $processOptimizer->getConfigurationName();
    if (!is_string($configuration_name)) {
      // Our process optimizers have a configuration.
      throw new \LogicException('Expected a string.');
    }

    $configuration = $this->config($configuration_name);

    $content = file_get_contents(sprintf('%s/../../config/install/%s.yml', __DIR__, $configuration_name));
    if (FALSE === $content) {
      throw new ParseException('The file could not be read.');
    }

    $default_configs = Yaml::parse($content);
    if (!is_array($default_configs)) {
      // Our process optimizers configuration is an array.
      throw new \LogicException('Expected an array.');
    }

    $config_keys = array_keys($default_configs);
    $form_config_keys = array_combine($config_keys, array_map(function ($config_key) use ($root_key) {
      return sprintf('%s_%s', $root_key, $config_key);
    }, $config_keys));

    $form[$root_key] = [
      '#type' => 'details',
      '#title' => $this->t('%optimizer_name optimizer settings', ['%optimizer_name' => $processOptimizer->getName()]),
      '#group' => self::VERTICAL_TABS_GROUP,
    ];

    foreach ($form_config_keys as $config_key => $form_config_key) {
      $form[$root_key][$form_config_key] = [
        '#default_value' => $configuration->get($config_key),
      ];
    }

    $form['#submit'][] = function ($form, FormStateInterface &$form_state) use ($form_config_keys, $configuration, $default_configs) {
      foreach ($form_config_keys as $config_key => $form_config_key) {
        $configuration->set($config_key, $form_state->getValue($form_config_key, $default_configs[$config_key]));
      }

      $configuration->save(TRUE);
    };

    return [
      $root_key,
      $form_config_keys,
    ];
  }

}
