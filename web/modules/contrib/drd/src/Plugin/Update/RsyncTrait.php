<?php

namespace Drupal\drd\Plugin\Update;

use AFM\Rsync\Rsync;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drd\Update\PluginStorageInterface;

/**
 * Trait for upstream and downstream RSync operations.
 */
trait RsyncTrait {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    /* @noinspection PhpUndefinedClassInspection */
    return [
      'exclude' => "sites/*/files\nsites/*/private\n",
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /* @noinspection PhpUndefinedClassInspection */
    $element = parent::buildConfigurationForm($form, $form_state);

    $element['exclude'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exclude'),
      '#default_value' => $this->configuration['exclude'],
      '#description' => $this->t('This will be used for the rsync exclude file, see "man rsync" for details.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    /* @noinspection PhpUndefinedClassInspection */
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['exclude'] = $this->getFormValue($form_state, 'exclude');
  }

  /**
   * Execute the RSync command.
   *
   * @param \Drupal\drd\Update\PluginStorageInterface $storage
   *   The update storage plugin.
   * @param bool $fromRemote
   *   TRUE for downstream and FALSE for upstream.
   * @param bool $dry
   *   Set to TRUE if everything should be prepared and logged but the actual
   *   rsync command should not be executed.
   *
   * @throws \Exception
   */
  protected function sync(PluginStorageInterface $storage, $fromRemote, $dry = FALSE) {
    if (!$storage->getCore()->getHost()->supportsSsh()) {
      throw new \Exception('You can only use Rsync if you have configured SSH for the host.');
    }

    $settings = $storage->getCore()->getHost()->getSshSettings();
    $config = [
      'ssh' => [
        'host' => $settings['host'],
        'port' => (int) $settings['port'],
        'username' => $settings['auth']['username'],
      ],
      'remote_origin' => $fromRemote,
      'verbose' => TRUE,
      'archive' => FALSE,
      'recursive' => TRUE,
      'follow_symlinks' => FALSE,
      'compression' => TRUE,
      'option_parameters' => 'l',
    ];
    if (!empty($settings['auth']['file_private_key']) && file_exists($settings['auth']['file_private_key'])) {
      $config['ssh']['public_key'] = $settings['auth']['file_private_key'];
    }
    if (!empty($this->configuration['exclude'])) {
      $excludefile = \Drupal::service('file_system')->tempnam('temporary://', 'exclude');
      file_put_contents($excludefile, $this->configuration['exclude']);
      $config['excludeFrom'] = drupal_realpath($excludefile);
    }
    $remote = $storage->getCore()->getDrupalRoot();
    $local = $storage->getDrupalDirectory();
    if ($fromRemote) {
      $source = $remote;
      $destination = $local;
    }
    else {
      $source = $local;
      $destination = $remote;
    }
    $rsync = new Rsync($config);
    $cmd = $rsync->getCommand($source . DIRECTORY_SEPARATOR, $destination);
    $cmd->addArgument('safe-links');
    $cmd->addArgument('max-size', '2M');
    $storage->log($cmd->getCommand());
    if (!$dry) {
      $cmd->execute();
    }
  }

}
