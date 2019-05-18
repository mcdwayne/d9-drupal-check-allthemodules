<?php

namespace Drupal\file_downloader;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Component\Utility\NestedArray;

/**
 * Class DownloadOptionPluginBase
 *
 * @package Drupal\file_downloader
 */
abstract class DownloadOptionPluginBase extends ContextAwarePluginBase implements DownloadOptionPluginInterface, PluginWithFormsInterface, ContainerFactoryPluginInterface {
  use ContextAwarePluginAssignmentTrait;
  use PluginWithFormsTrait;

  /**
   * Contains the file system service.
   *
   * @var FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystemInterface $fileSystem) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->fileSystem = $fileSystem;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function deliver(FileInterface $file) {
    $scheme = $this->fileSystem->uriScheme($file->getFileUri());
    return new BinaryFileResponse($file->getFileUri(), 200, $this->getHeaders($file), $scheme !== 'private');
  }

  /**
   * Return the headers for the Binary file response.
   *
   * @param \Drupal\file\FileInterface $file
   *
   * @return array
   */
  private function getHeaders(FileInterface $file) {
    return [
      'Content-Type'              => Unicode::mimeHeaderEncode($file->getMimeType()),
      'Content-Disposition'       => 'attachment; filename="' . $file->getFilename() . '"',
      'Content-Length'            => $file->getSize(),
      'Content-Transfer-Encoding' => 'binary',
      'Pragma'                    => 'no-cache',
      'Cache-Control'             => 'must-revalidate, post-check=0, pre-check=0',
      'Expires'                   => '0',
      'Accept-Ranges'             => 'bytes',
    ];
  }

  /**
   * @inheritdoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $definition = $this->getPluginDefinition();

    $form['settings']['description'] = array(
      '#type' => 'markup',
      '#markup' => $definition['description'],
    );

    // Add context mapping UI form elements.
    $contexts = $form_state->getTemporaryValue('gathered_contexts') ?: [];
    $form['context_mapping'] = $this->addContextAssignmentElement($this, $contexts);

    $form += $this->downloadOptionForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function downloadOptionForm($form, FormStateInterface $form_state) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->downloadOptionValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function downloadOptionValidate($form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $this->downloadOptionSubmit($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function downloadOptionSubmit($form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  protected function baseConfigurationDefaults() {
    return array(
      'id' => $this->getPluginId(),
      'extensions' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationValue($key) {
    return $this->configuration[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->baseConfigurationDefaults(),
      $this->defaultConfiguration(),
      $configuration
    );
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
  public function downloadFileExists(FileInterface $file) {
    return file_exists($file->getFileUri());
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, FileInterface $file) {
    if (!$file->access('view', $account)) {
      return AccessResult::forbidden('User has no permission to view the original file.');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function getFileUri(FileInterface $file) {
    return $file->getFileUri();
  }

}
