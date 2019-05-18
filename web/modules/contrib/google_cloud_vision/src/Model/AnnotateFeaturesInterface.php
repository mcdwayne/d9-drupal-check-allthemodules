<?php

namespace Drupal\google_cloud_vision\Model;

/**
 * Interface AnnotateFeaturesInterface.
 *
 * @package Drupal\google_cloud_vision
 */
interface AnnotateFeaturesInterface {

  /**
   * Turn face detection data on/off.
   *
   * @param bool $get
   *   Whether to get the feature or not.
   *
   * @return \Drupal\google_cloud_vision\Model\AnnotateFeaturesInterface
   *   Current AnnotateFeatures instance.
   */
  public function setFaceDetection($get);

  /**
   * Turn landmark detection data on/off.
   *
   * @param bool $get
   *   Whether to get the feature or not.
   *
   * @return \Drupal\google_cloud_vision\Model\AnnotateFeaturesInterface
   *   Current AnnotateFeatures instance.
   */
  public function setLandMarkDetection($get);

  /**
   * Turn logo detection data on/off.
   *
   * @param bool $get
   *   Whether to get the feature or not.
   *
   * @return \Drupal\google_cloud_vision\Model\AnnotateFeaturesInterface
   *   Current AnnotateFeatures instance.
   */
  public function setLogoDetection($get);

  /**
   * Turn label detection on/off.
   *
   * @param bool $get
   *   Whether to get the feature or not.
   *
   * @return \Drupal\google_cloud_vision\Model\AnnotateFeaturesInterface
   *   Current AnnotateFeatures instance.
   */
  public function setLabelDetection($get);

  /**
   * Turn text detection on/off.
   *
   * @param bool $get
   *   Whether to get the feature or not.
   *
   * @return \Drupal\google_cloud_vision\Model\AnnotateFeaturesInterface
   *   Current AnnotateFeatures instance.
   */
  public function setTextDetection($get);

  /**
   * Turn document text detection on/off.
   *
   * @param bool $get
   *   Whether to get the feature or not.
   *
   * @return \Drupal\google_cloud_vision\Model\AnnotateFeaturesInterface
   *   Current AnnotateFeatures instance.
   */
  public function setDocumentTextDetection(bool $get);

  /**
   * Turn safe search detection on/off.
   *
   * @param bool $get
   *   Whether to get the feature or not.
   *
   * @return \Drupal\google_cloud_vision\Model\AnnotateFeaturesInterface
   *   Current AnnotateFeatures instance.
   */
  public function setSafeSearchDetection($get);

  /**
   * Turn image properties detection on/off.
   *
   * @param bool $get
   *   Whether to get the feature or not.
   *
   * @return \Drupal\google_cloud_vision\Model\AnnotateFeaturesInterface
   *   Current AnnotateFeatures instance.
   */
  public function setImagePropertiesDetection($get);

  /**
   * Set crop hint detection on/off.
   *
   * @param bool $get
   *   Whether to get the feature or not.
   *
   * @return \Drupal\google_cloud_vision\Model\AnnotateFeaturesInterface
   *   Current AnnotateFeatures instance.
   */
  public function setCropHintDetection($get);

  /**
   * Set web detection on/off.
   *
   * @param bool $get
   *   Whether to get the feature or not.
   *
   * @return \Drupal\google_cloud_vision\Model\AnnotateFeaturesInterface
   *   Current AnnotateFeatures instance.
   */
  public function setWebDetection($get);

  /**
   * Get the features to send in a Google Vision Annotate Request.
   *
   * @return string[]
   *   List of features to get data for.
   */
  public function getFeatures();

  /**
   * Set a feature to get annotation date for.
   *
   * @param string $feature
   *   Feature name.
   * @param bool $get
   *   Whether to get the feature or not.
   *
   * @return \Drupal\google_cloud_vision\Model\AnnotateFeaturesInterface
   *   Current AnnotateFeatures instance.
   */
  public function setFeature($feature, $get);

}
