<?php

namespace Drupal\drd\Plugin\Update;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\drd\Entity\Script;
use Drupal\drd\Update\PluginInterface;
use Drupal\drd\Update\PluginStorageInterface;
use mikehaertl\shellcommand\Command as ShellCommand;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Abstract DRD Update plugin to implement general functionality.
 */
abstract class Base extends PluginBase implements PluginInterface {

  /**
   * The id of the parent form element.
   *
   * @var string
   */
  protected $configFormParent;

  /**
   * List of conditions for element visibility.
   *
   * @var array
   */
  protected $condition;

  /**
   * Most recent shell output for logging.
   *
   * @var string
   */
  protected $lastShellOutput = '';

  /**
   * {@inheritdoc}
   */
  final public function setConfigFormContext($parent, array $condition) {
    $this->configFormParent = $parent;
    $this->condition = $condition;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  final public function getConfiguration() {
    return NestedArray::mergeDeep($this->defaultConfiguration(), (isset($this->configuration) ? $this->configuration : []));
  }

  /**
   * {@inheritdoc}
   */
  final public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function scriptHooks() {
    return [
      'prePlugin' => $this->t('Before this plugin'),
      'postPlugin' => $this->t('After this plugin'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = [
      'scripts' => [],
    ];
    foreach ($this->scriptHooks() as $scriptHook => $label) {
      $config['scripts'][$scriptHook] = '';
    }
    return $config;
  }

  /**
   * Get the Update plugin type.
   *
   * @return string
   *   The Update plugin type.
   */
  protected function getType() {
    $parts = explode('\\', get_class($this));
    array_pop($parts);
    return strtolower(array_pop($parts));
  }

  /**
   * Get the value of a form element.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return mixed
   *   The value of the form element.
   */
  protected function getFormValue(FormStateInterface $form_state) {
    $args = func_get_args();
    array_shift($args);
    array_unshift($args, $this->pluginId);
    array_unshift($args, $this->getType());
    return $form_state->getValue($args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['scripts'] = [
      '#type' => 'details',
      '#title' => $this->t('Scripts'),
      '#open' => FALSE,
      '#weight' => 99,
    ];
    foreach ($this->scriptHooks() as $scriptHook => $label) {
      $element['scripts'][$scriptHook] = [
        '#type' => 'select',
        '#title' => $label,
        '#options' => Script::getSelectList(),
        '#default_value' => $this->configuration['scripts'][$scriptHook],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->scriptHooks() as $scriptHook => $label) {
      $value = $this->getFormValue($form_state, 'scripts', $scriptHook);
      if (!empty($value) && !file_exists($value)) {
        $form_state->setError($form[$this->configFormParent][$this->getType()][$this->pluginId]['scripts'][$scriptHook], $this->t('Script not found.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->scriptHooks() as $scriptHook => $label) {
      $this->configuration['scripts'][$scriptHook] = $this->getFormValue($form_state, 'scripts', $scriptHook);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(PluginStorageInterface $storage) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  final public function executeScript(PluginStorageInterface $storage, $hook) {
    if ($hook == 'prePlugin' || $hook == 'postPlugin') {
      $action = ($hook == 'prePlugin') ? 'Start' : 'Finish';
      $def = $this->getPluginDefinition();
      $storage->log($action . ' step ' . $this->getType() . ' with plugin ' . $def['admin_label']);
    }
    if (!empty($this->configuration['scripts'][$hook])) {
      $script = Script::load($this->configuration['scripts'][$hook]);
      $storage->log('Start script ' . $hook . ': ' . $script->label());
      $script->execute([
        'storage' => $storage,
        'hook' => $hook,
      ], $storage->getWorkingDirectory());
      $output = $script->getOutput();
      if (!empty($output)) {
        $storage->log($output);
      }
      $storage->log('Finish script ' . $hook);
    }
    return $this;
  }

  /**
   * Execute a shell command and capture the output.
   *
   * @param \Drupal\drd\Update\PluginStorageInterface $storage
   *   The update storage plugin.
   * @param string $cmd
   *   The command to execute.
   * @param string $workingDir
   *   The optional working directory where the command should be executed.
   *
   * @return int
   *   The exit code of the command.
   */
  protected function shell(PluginStorageInterface $storage, $cmd, $workingDir = NULL) {
    $this->lastShellOutput = '';
    if (!isset($workingDir)) {
      $workingDir = $storage->getWorkingDirectory();
    }
    $fs = new Filesystem();
    if (!$fs->exists($workingDir)) {
      $fs->mkdir($workingDir);
    }
    $storage->log('Shell command [' . $workingDir . ']: ' . $cmd);
    $command = new ShellCommand($cmd);
    $command->procCwd = $workingDir;
    $success = $command->execute();
    $this->lastShellOutput = $command->getOutput();
    $storage->log($this->lastShellOutput);
    if (!$success) {
      $storage->log('[Error]: ' . $command->getError());
    }
    return $command->getExitCode();
  }

}
