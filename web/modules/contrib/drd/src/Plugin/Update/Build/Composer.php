<?php

namespace Drupal\drd\Plugin\Update\Build;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drd\Update\PluginStorageInterface;

/**
 * Provides a composer based update build plugin.
 *
 * @Update(
 *  id = "composer",
 *  admin_label = @Translation("Composer"),
 * )
 */
class Composer extends Base {

  /**
   * {@inheritdoc}
   */
  protected function implicitPatching() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'extra command' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $element = parent::buildConfigurationForm($form, $form_state);

    $element['extra_command'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra composer command'),
      '#default_value' => $this->configuration['extra command'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['extra command'] = $this->getFormValue($form_state, 'extra_command');
  }

  /**
   * {@inheritdoc}
   */
  public function build(PluginStorageInterface $storage, array $releases) {
    $json_filename = $storage->getWorkingDirectory() . DIRECTORY_SEPARATOR . 'composer.json';
    $lock_filename = $storage->getWorkingDirectory() . DIRECTORY_SEPARATOR . 'composer.lock';
    $hashes = [
      'json' => @hash_file('md5', $json_filename),
      'lock' => @hash_file('md5', $lock_filename),
    ];
    $composer = Json::decode(file_get_contents($json_filename));

    foreach ($releases as $release) {
      $name = 'drupal/' . (($release->getProjectType() == 'core') ?
        'core' :
        $release->getMajor()->getProject()->getName());
      if (isset($composer['require'][$name])) {
        // Only change version if project was listed already before, otherwise
        // composer update will pull the latest automatically and change the
        // composer.lock accordingly.
        $composer['require'][$name] = $release->getReleaseVersion();
      }
    }

    file_put_contents($json_filename, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL);

    $cache_dir = drupal_realpath(file_directory_temp()) . '/composer-cache';
    if ($this->shell($storage, 'composer config cache-dir ' . $cache_dir)) {
      throw new \Exception('Composer config cache-dir failed.');
    }
    if ($this->shell($storage, 'composer update --no-progress --no-interaction')) {
      throw new \Exception('Composer failed.');
    }
    if (!empty($this->configuration['extra command'])) {
      $command = 'composer ';
      $extra_command = $this->configuration['extra command'];
      if (strpos($extra_command, '--no-interaction') === FALSE) {
        $command .= '--no-interaction ';
      }
      if ($this->shell($storage, $command . $extra_command)) {
        throw new \Exception('Composer extra command failed.');
      }
    }
    if ($this->shell($storage, 'composer config --unset cache-dir')) {
      throw new \Exception('Composer config unset cache-dir.');
    }
    $this->changed = (
      $hashes['json'] != @hash_file('md5', $json_filename) ||
      $hashes['lock'] != @hash_file('md5', $lock_filename)
    );

    return $this;
  }

}
