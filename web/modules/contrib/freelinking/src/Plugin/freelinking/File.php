<?php

namespace Drupal\freelinking\Plugin\freelinking;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\FreelinkingPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Freelinking file plugin.
 *
 * @Freelinking(
 *   id = "file",
 *   title = @Translation("File"),
 *   weight = 0,
 *   hidden = false,
 *   settings = {
 *     "scheme" = "public"
 *   }
 * )
 */
class File extends FreelinkingPluginBase implements ContainerFactoryPluginInterface {

  /**
   * File system configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $fileSystemConfig;

  /**
   * Stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   The stream wrapper manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, StreamWrapperManagerInterface $streamWrapperManager, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->fileSystemConfig = $configFactory->get('system.file');
    $this->streamWrapperManager = $streamWrapperManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndicator() {
    return '/^file$/i';
  }

  /**
   * {@inheritdoc}
   */
  public function getTip() {
    return $this->t('Click to view a local file.');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'settings' => ['scheme' => $this->fileSystemConfig->get('default_scheme')],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['scheme'] = [
      '#type' => 'select',
      '#title' => $this->t('File System'),
      '#description' => $this->t('Choose the file system to use to lookup files.'),
      '#options' => $this->getSchemeOptions(),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildLink(array $target) {
    $scheme = $this->getConfiguration()['settings']['scheme'];

    // Remove slash if it's present at first character.
    $dest = preg_replace('/^\//', '', $target['dest']);
    $path = $scheme . '://' . $dest;
    $stream_wrapper = $this->loadScheme($scheme);
    $stream_wrapper->setUri($path);

    // Make sure that the file exists on the given scheme.
    if (!$stream_wrapper->realpath()) {
      return [
        '#theme' => 'freelink_error',
        '#plugin' => 'file',
        '#message' => $this->t('File @name not found', ['@name' => basename($path)]),
      ];
    }

    // Check file access.
    $headers = $this->moduleHandler->invokeAll('file_download', [$path]);
    if (in_array(-1, $headers)) {
      return [
        '#theme' => 'freelink_error',
        '#plugin' => 'file',
        '#message' => $this->t('File @name not found', ['@name' => basename($path)]),
      ];
    }

    // Return a link to the file.
    $file_url = $stream_wrapper->getExternalUrl();
    return [
      '#type' => 'link',
      '#title' => isset($target['text']) ? $target['text'] : basename($path),
      '#url' => Url::fromUri($file_url, ['absolute' => TRUE, 'language' => $target['language']]),
      '#attributes' => [
        'title' => $this->getTip(),
      ],
    ];
  }

  /**
   * Get the stream managers keyed by name for the options list.
   *
   * @return array
   *   An array of options.
   */
  protected function getSchemeOptions() {
    $options = [];
    $schemes = $this->streamWrapperManager->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    foreach ($schemes as $scheme => $name) {
      $options[$scheme] = $name;
    }
    return $options;
  }

  /**
   * Load the stream wrapper for a given scheme.
   *
   * @param string $scheme
   *   The scheme to load.
   *
   * @return \Drupal\Core\StreamWrapper\StreamWrapperInterface
   *   The stream wrapper to use.
   */
  protected function loadScheme($scheme) {
    return $this->streamWrapperManager->getViaScheme($scheme);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('stream_wrapper_manager'),
      $container->get('module_handler')
    );
  }

}
