<?php

namespace Drupal\google_vision;

/**
 * Interface for GoogleVisionApi.
 */
interface GoogleVisionApiInterface {

  /**
   * Function to retrieve labels for given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function labelDetection($filepath);

  /**
   * Function to detect landmarks within a given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function landmarkDetection($filepath);

  /**
   * Function to detect logos of famous brands within a given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function logoDetection($filepath);

  /**
   * Function to detect explicit content within a given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function safeSearchDetection($filepath);

  /**
   * Function to retrieve texts for given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function opticalCharacterRecognition($filepath);

  /**
   * Function to fetch faces from a given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function faceDetection($filepath);

  /**
   * Function to retrieve image attributes for given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function imageAttributesDetection($filepath);

}
