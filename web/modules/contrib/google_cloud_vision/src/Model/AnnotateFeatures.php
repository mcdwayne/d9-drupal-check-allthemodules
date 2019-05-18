<?php

namespace Drupal\google_cloud_vision\Model;

/**
 * Class AnnotateFeatures.
 *
 * @package Drupal\google_cloud_vision
 */
class AnnotateFeatures implements AnnotateFeaturesInterface {

  /**
   * Face detection feature name.
   */
  private const FACE_DETECTION = 'FACE_DETECTION';

  /**
   * Landmark detection feature name.
   */
  private const LANDMARK_DETECTION = 'LANDMARK_DETECTION';

  /**
   * Logo detection feature name.
   */
  private const LOGO_DETECTION = 'LOGO_DETECTION';

  /**
   * Label detection feature name.
   */
  private const LABEL_DETECTION = 'LABEL_DETECTION';

  /**
   * Text detection feature name.
   */
  private const TEXT_DETECTION = 'TEXT_DETECTION';

  /**
   * Document text detection feature name.
   */
  private const DOCUMENT_TEXT_DETECTION = 'DOCUMENT_TEXT_DETECTION';

  /**
   * Safe search detection feature name.
   */
  private const SAFE_SEARCH_DETECTION = 'SAFE_SEARCH_DETECTION';

  /**
   * Image properties detection feature name.
   */
  private const IMAGE_PROPERTIES_DETECTION = 'IMAGE_PROPERTIES';

  /**
   * Crop hints detection feature name.
   */
  private const CROP_HINTS_DETECTION = 'CROP_HINTS';

  /**
   * Web detection feature name.
   */
  private const WEB_DETECTION = 'WEB_DETECTION';

  /**
   * List of enabled features get data for.
   *
   * @var string[]
   */
  protected $features = [];

  /**
   * {@inheritdoc}
   */
  public function setFaceDetection($get) {
    $this->setFeature(self::FACE_DETECTION, $get);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFeature($feature, $get) {
    if (!$get) {
      $this->removeFeature($feature);
      return $this;
    }
    $this->addFeature($feature);
    return $this;
  }

  /**
   * Remove a feature to get data for.
   *
   * @param string $feature
   *   Feature name to remove from the list of features.
   *
   * @return \Drupal\google_cloud_vision\Model\AnnotateFeatures
   *   Current AnnotateFeatures instance.
   */
  private function removeFeature($feature) {
    if (isset($this->features[$feature])) {
      unset($this->features[$feature]);
    }
    return $this;
  }

  /**
   * Add a feature to get data for.
   *
   * @param string $feature
   *   Feature name to add to the list of features.
   *
   * @return \Drupal\google_cloud_vision\Model\AnnotateFeatures
   *   Current AnnotateFeatures instance.
   */
  private function addFeature($feature) {
    $this->features[$feature] = $feature;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLandMarkDetection($get) {
    $this->setFeature(self::LANDMARK_DETECTION, $get);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLogoDetection($get) {
    $this->setFeature(self::LOGO_DETECTION, $get);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabelDetection($get) {
    $this->setFeature(self::LABEL_DETECTION, $get);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTextDetection($get) {
    $this->setFeature(self::TEXT_DETECTION, $get);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDocumentTextDetection(bool $get) {
    $this->setFeature(self::DOCUMENT_TEXT_DETECTION, $get);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSafeSearchDetection($get) {
    $this->setFeature(self::SAFE_SEARCH_DETECTION, $get);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setImagePropertiesDetection($get) {
    $this->setFeature(self::IMAGE_PROPERTIES_DETECTION, $get);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCropHintDetection($get) {
    $this->setFeature(self::CROP_HINTS_DETECTION, $get);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setWebDetection($get) {
    $this->setFeature(self::WEB_DETECTION, $get);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFeatures() {
    return array_values($this->features);
  }

}
