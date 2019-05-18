<?php

namespace Drupal\file_downloader\Plugin\DownloadOption;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\FileInterface;
use Drupal\file_downloader\Annotation\DownloadOption;
use Drupal\file_downloader\DownloadOptionPluginBase;
use Drupal\image\ImageStyleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Defines a download option plugin.
 *
 * @DownloadOption(
 *   id = "image_style",
 *   label = @Translation("Image Style"),
 *   description = @Translation("Download a file based on a image style."),
 * )
 */
class ImageStyle extends DownloadOptionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Image Style storage object.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $imageStyleStorage;
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystemInterface $fileSystem, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $fileSystem);
    $this->imageStyleStorage = $entityTypeManager->getStorage('image_style');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function deliver(FileInterface $file) {
    $image_style_id = $this->getConfigurationValue('image_style');
    $scheme = $this->fileSystem->uriScheme($file->getFileUri());
    /** @var ImageStyleInterface $image_style */
    $image_style = $this->imageStyleStorage->load($image_style_id);
    $image_style_uri = $image_style->buildUri($file->getFileUri());

    return new BinaryFileResponse($image_style_uri, 200, $this->getHeaders($file, $image_style_uri), $scheme !== 'private');
  }

  /**
   * Return the headers for the Binary file response.
   *
   * @param \Drupal\file\FileInterface $file
   *
   * @return array
   */
  private function getHeaders(FileInterface $file, $image_style_uri) {
    return [
      'Content-Type'              => Unicode::mimeHeaderEncode($file->getMimeType()),
      'Content-Disposition'       => 'attachment; filename="' . $file->getFilename() . '"',
      'Content-Length'            => filesize($image_style_uri),
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
  public function downloadOptionForm($form, FormStateInterface $form_state) {
    $styles = $this->imageStyleStorage->loadMultiple();


    foreach ($styles as $name => $style) {
      $options[$name] = $style->get('label');
    }

    $form['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Style'),
      '#default_value' => $this->getConfigurationValue('image_style'),
      '#options' => $options,
      '#required' => TRUE,
      '#empty_option' => $this->t('Please select a image style.'),
    ];

    return $form;
  }

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    return array(
      'id' => $this->getPluginId(),
      'extensions' => '',
      'image_style' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function downloadOptionSubmit($form, FormStateInterface $form_state) {
    $this->configuration['image_style'] = $form_state->getValue('image_style');
  }

  /**
   * {@inheritdoc}
   */
  public function downloadFileExists(FileInterface $file) {
    if (!parent::downloadFileExists($file)) {
      return FALSE;
    }

    return $this->imageStyleFileExists($file);
  }

  /**
   * Validate if the image style could be created.
   *
   * @param \Drupal\file\FileInterface $file
   * @return bool
   */
  private function imageStyleFileExists(FileInterface $file) {
    $image_style_id = $this->getConfigurationValue('image_style');

    if (empty($image_style_id)) {
      return FALSE;
    }

    /** @var ImageStyleInterface $image_style */
    $image_style = $this->imageStyleStorage->load($image_style_id);
    $image_style_uri = $image_style->buildUri($file->getFileUri());

    $status = file_exists($image_style_uri);
    if (!$status) {
      $image_style->createDerivative($file->getFileUri(), $image_style_uri);
      $status = file_exists($image_style_uri);
    }

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileUri(FileInterface $file) {
    $image_style_id = $this->getConfigurationValue('image_style');
    /** @var ImageStyleInterface $image_style */
    $image_style = $this->imageStyleStorage->load($image_style_id);
    return $image_style->buildUri($file->getFileUri());
  }

}
