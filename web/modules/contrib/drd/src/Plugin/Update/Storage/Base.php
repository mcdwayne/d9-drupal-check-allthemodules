<?php

namespace Drupal\drd\Plugin\Update\Storage;

use Drupal\Core\Form\FormStateInterface;
use Drupal\drd\Entity\CoreInterface;
use Drupal\drd\Plugin\Update\Base as UpdateBase;
use Drupal\drd\Update\PluginBuildInterface;
use Drupal\drd\Update\PluginDeployInterface;
use Drupal\drd\Update\PluginFinishInterface;
use Drupal\drd\Update\PluginProcessInterface;
use Drupal\drd\Update\PluginStorageInterface;
use Drupal\drd\Update\PluginTestInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Abstract DRD Update plugin to implement general functionality.
 */
abstract class Base extends UpdateBase implements PluginStorageInterface {

  /**
   * The filesystem object.
   *
   * @var FileSystem
   */
  protected $fs;

  /**
   * The Drupal root directory.
   *
   * @var string
   */
  protected $drupalDirectory;

  /**
   * The project's root directory.
   *
   * @var string
   */
  protected $workingDirectory;

  /**
   * The full log text for the full update process.
   *
   * @var string
   */
  private $logText = '';

  /**
   * The build plugin.
   *
   * @var \Drupal\drd\Update\PluginBuildInterface
   */
  private $buildPlugin;

  /**
   * The processing plugin.
   *
   * @var \Drupal\drd\Update\PluginProcessInterface
   */
  private $processPlugin;

  /**
   * The test plugin.
   *
   * @var \Drupal\drd\Update\PluginTestInterface
   */
  private $testPlugin;

  /**
   * The deploy plugin.
   *
   * @var \Drupal\drd\Update\PluginDeployInterface
   */
  private $deployPlugin;

  /**
   * The finish plugin.
   *
   * @var \Drupal\drd\Update\PluginFinishInterface
   */
  private $finishPlugin;

  /**
   * The core entity which will get updated.
   *
   * @var \Drupal\drd\Entity\CoreInterface
   */
  private $core;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fs = new Filesystem();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'drupalroot' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $element = parent::buildConfigurationForm($form, $form_state);

    $element['drupalroot'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drupal Root'),
      '#default_value' => $this->configuration['drupalroot'],
      '#description' => $this->t('Relative path to Drupal root directory from the working directory without leading or trailing slash.'),
      '#weight' => 80,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['drupalroot'] = trim($this->getFormValue($form_state, 'drupalroot'), '/');
  }

  /**
   * {@inheritdoc}
   */
  final public function stepPlugins(
    PluginBuildInterface $build,
    PluginProcessInterface $process,
    PluginTestInterface $test,
    PluginDeployInterface $deploy,
    PluginFinishInterface $finish) {
    $this->buildPlugin = $build;
    $this->processPlugin = $process;
    $this->testPlugin = $test;
    $this->deployPlugin = $deploy;
    $this->finishPlugin = $finish;
  }

  /**
   * {@inheritdoc}
   */
  public function scriptHooks() {
    return [
      'preUpdate' => $this->t('At the very beginning'),
      'postUpdate' => $this->t('At the very end'),
      'prePrepare' => $this->t('Before preparing working directory'),
      'postPrepare' => $this->t('After preparing working directory'),
      'preSave' => $this->t('Before saving working directory'),
      'postSave' => $this->t('After saving working directory'),
    ] + parent::scriptHooks();
  }

  /**
   * {@inheritdoc}
   */
  final public function execute(CoreInterface $core, array $releases, $dry, $showlog) {
    $this->core = $core;
    $result = TRUE;
    try {
      $this
        ->log('Start')
        ->executeScript($this, 'preUpdate')
        ->executeScript($this, 'prePlugin')
        ->setWorkingDirectory()
        ->executeScript($this, 'prePrepare')
        ->prepareWorkingDirectory()
        ->executeScript($this, 'postPrepare');
      $this->buildPlugin
        ->executeScript($this, 'prePlugin')
        ->build($this, $releases)
        ->patch($this)
        ->executeScript($this, 'postPlugin');
      if ($this->buildPlugin->hasChanged()) {
        $this->processPlugin
          ->executeScript($this, 'prePlugin')
          ->process($this)
          ->executeScript($this, 'postPlugin');
      }
      if ($this->processPlugin->hasSucceeded()) {
        $this->testPlugin
          ->executeScript($this, 'prePlugin')
          ->test($this)
          ->executeScript($this, 'postPlugin');
      }
      if ($dry) {
        if ($this->testPlugin->hasSucceeded()) {
          $this->deployPlugin
            ->executeScript($this, 'prePlugin')
            ->dryRun($this)
            ->executeScript($this, 'postPlugin');
        }
        if ($this->deployPlugin->hasSucceeded()) {
          $this->finishPlugin
            ->executeScript($this, 'prePlugin')
            ->dryRun($this)
            ->executeScript($this, 'postPlugin');
        }
      }
      else {
        if ($this->testPlugin->hasSucceeded()) {
          $this->deployPlugin
            ->executeScript($this, 'prePlugin')
            ->deploy($this)
            ->executeScript($this, 'postPlugin');
        }
        if ($this->deployPlugin->hasSucceeded()) {
          $this->finishPlugin
            ->executeScript($this, 'prePlugin')
            ->finish($this)
            ->executeScript($this, 'postPlugin');
        }
      }
    }
    catch (\Exception $ex) {
      $result = 'Exception: ' . $ex->getMessage();
      $this->log($result);
    }

    if ($dry) {
      $this->log('Finished dry');
    }
    else {
      try {
        $this->finishPlugin->cleanup($this);
        $this->deployPlugin->cleanup($this);
        $this->testPlugin->cleanup($this);
        $this->processPlugin->cleanup($this);
        $this->buildPlugin->cleanup($this);
        if ($result === TRUE) {
          $this
            ->executeScript($this, 'preSave')
            ->saveWorkingDirectory()
            ->executeScript($this, 'postSave');
        }
        $this
          ->cleanup($this)
          ->executeScript($this, 'postPlugin')
          ->executeScript($this, 'postUpdate')
          ->log('Finish');
      }
      catch (\Exception $ex) {
        $result = 'Exception during save and cleanup: ' . $ex->getMessage();
        $this->log($result);
      }
    }

    if ($showlog) {
      print($this->logText);
    }
    $this->core->saveUpdateLog($this->logText);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  final public function log($log) {
    $logs = is_string($log) ? [$log] : $log;
    foreach ($logs as $log) {
      if (!empty(trim($log))) {
        $t = \Drupal::service('date.formatter')
          ->format(time(), 'custom', 'Y-m-d H:i:s');
        $this->logText .= '[' . $t . '] ' . str_replace("\n", "    \n", $log) . "\n";
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  final public function getCore() {
    return $this->core;
  }

  /**
   * {@inheritdoc}
   */
  public function getDrupalDirectory() {
    return empty($this->configuration['drupalroot']) ?
      $this->workingDirectory :
      $this->workingDirectory . DIRECTORY_SEPARATOR . $this->configuration['drupalroot'];
  }

  /**
   * {@inheritdoc}
   */
  final public function getWorkingDirectory() {
    return $this->workingDirectory;
  }

  /**
   * {@inheritdoc}
   */
  final public function getBuildPlugin() {
    return $this->buildPlugin;
  }

  /**
   * {@inheritdoc}
   */
  final public function getProcessPlugin() {
    return $this->processPlugin;
  }

  /**
   * {@inheritdoc}
   */
  final public function getTestPlugin() {
    return $this->testPlugin;
  }

  /**
   * {@inheritdoc}
   */
  final public function getDeployPlugin() {
    return $this->deployPlugin;
  }

  /**
   * {@inheritdoc}
   */
  final public function getFinishPlugin() {
    return $this->finishPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkingDirectory() {
    if (!isset($this->workingDirectory)) {
      $this->workingDirectory = $this->fs->tempnam(file_directory_temp(), 'drd-update-');
      $this->drupalDirectory = $this->workingDirectory . DIRECTORY_SEPARATOR . $this->configuration['drupalroot'];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareWorkingDirectory() {
    $this->fs->remove($this->workingDirectory);
    $this->fs->mkdir($this->workingDirectory);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function saveWorkingDirectory() {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(PluginStorageInterface $storage) {
    $this->fs->remove($this->workingDirectory);
    return $this;
  }

}
