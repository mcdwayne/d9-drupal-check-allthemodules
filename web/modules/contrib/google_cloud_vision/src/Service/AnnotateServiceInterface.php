<?php

namespace Drupal\google_cloud_vision\Service;

use Drupal\google_cloud_vision\Model\AnnotateFeaturesInterface;

/**
 * Interface AnnotateServiceInterface.
 *
 * @package Drupal\google_cloud_vision
 */
interface AnnotateServiceInterface {

  /**
   * Add an image resource to send to Google Cloud Vision.
   *
   * @param string $imageId
   *   Identification for the image to reference the correct annotation.
   * @param resource $imageResource
   *   File resource of the image.
   * @param \Drupal\google_cloud_vision\Model\AnnotateFeaturesInterface $features
   *   Google Vision Features to get data for.
   * @param array $config
   *   Image context and  max results settings.
   *
   * @return \Drupal\google_cloud_vision\Service\AnnotateServiceInterface
   *   This instance of the AnnotateService.
   */
  public function addImageResource($imageId, $imageResource, AnnotateFeaturesInterface $features, array $config = []);

  /**
   * Add an image path to get the resource for to send to Google Cloud Vision.
   *
   * @param string $imageId
   *   Identification for the image to reference the correct annotation.
   * @param string $imagePath
   *   File location of the image.
   * @param \Drupal\google_cloud_vision\Model\AnnotateFeaturesInterface $features
   *   Google Vision Features to get data for.
   * @param array $config
   *   Image context and  max results settings.
   *
   * @return \Drupal\google_cloud_vision\Service\AnnotateServiceInterface
   *   This instance of the AnnotateService.
   */
  public function addImagePath($imageId, $imagePath, AnnotateFeaturesInterface $features, array $config = []);

  /**
   * Add a single image content to send to Google Cloud Vision.
   *
   * @param string $imageId
   *   Identification for the image to reference the correct annotation.
   * @param string $imageContent
   *   String image data.
   * @param \Drupal\google_cloud_vision\Model\AnnotateFeaturesInterface $features
   *   Google Vision Features to get data for.
   * @param array $config
   *   Image context and  max results settings.
   *
   * @return \Drupal\google_cloud_vision\Service\AnnotateServiceInterface
   *   This instance of the AnnotateService.
   */
  public function addImageContent($imageId, $imageContent, AnnotateFeaturesInterface $features, array $config = []);

  /**
   * Send the request and get the annotations for the images.
   *
   * @return \Drupal\google_cloud_vision\Service\AnnotateServiceInterface
   *   Annotate the images and return them.
   */
  public function annotate();

  /**
   * Get the requested annotations for the images.
   *
   * @return \Google\Cloud\Vision\Annotation[]
   *   Google Cloud Image Annotation.
   */
  public function getAnnotations();

}
