<?php

namespace Drupal\google_cloud_vision\Service;

use Drupal\Core\Config\ConfigFactory;
use Drupal\google_cloud_vision\Model\AnnotateFeaturesInterface;
use Google\Cloud\Core\ServiceBuilder;

/**
 * Class AnnotateService.
 *
 * @package Drupal\google_cloud_vision
 */
class AnnotateService implements AnnotateServiceInterface {

  /**
   * Google Cloud Vision API Client.
   *
   * @var \Google\Cloud\Vision\VisionClient
   */
  protected $visionClient;

  /**
   * Google Cloud Vision Image.
   *
   * @var \Google\Cloud\Vision\Image[]
   */
  protected $images = [];

  /**
   * Annotations from Google Cloud Vision for all $images.
   *
   * @var \Google\Cloud\Vision\Annotation[]
   */
  protected $annotations = [];

  /**
   * AnnotateService constructor.
   *
   * @param \Google\Cloud\Core\ServiceBuilder $serviceBuilder
   *   Google Cloud Service Builder.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   Config factory.
   */
  public function __construct(ServiceBuilder $serviceBuilder, ConfigFactory $config) {
    $googleCloudVision = $config->get('google_cloud_vision.settings');
    $this->visionClient = $serviceBuilder->vision(['keyFilePath' => $googleCloudVision->get('json_key_path')]);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function addImageResource($imageId, $imageResource, AnnotateFeaturesInterface $features, array $config = []) {
    $this->images[$imageId] = $this->visionClient->image($imageResource, $features->getFeatures(), $config);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function addImagePath($imageId, $imagePath, AnnotateFeaturesInterface $features, array $config = []) {
    $imageResource = fopen($imagePath, 'rb+');
    $this->addImageResource($imageId, $imageResource, $features, $config);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function addImageContent($imageId, $imageContent, AnnotateFeaturesInterface $features, array $config = []) {
    $this->images[$imageId] = $this->visionClient->image($imageContent, $features->getFeatures(), $config);
  }

  /**
   * {@inheritdoc}
   */
  public function annotate() {
    foreach ($this->images as $imageId => $image) {
      if (isset($this->annotations[$imageId])) {
        continue;
      }
      $this->annotations[$imageId] = $this->visionClient->annotate($image);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnnotations() {
    return $this->annotations;
  }

}
