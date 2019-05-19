<?php

namespace Drupal\square_pixels_scale\Plugin\ImageEffect;

use Drupal\Component\Utility\Image;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\ConfigurableImageEffectBase;

/**
 * Scales an image resource.
 *
 * @ImageEffect(
 *   id = "square_pixels_scale",
 *   label = @Translation("Square pixels scale"),
 *   description = @Translation("Allows an image to be scaled according to a target number of square pixels.")
 * )
 */
class SquarePixelsScaleImageEffect extends ConfigurableImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    $target_dimensions = $this->calculateTargetDimensions([
      'width' => $image->getWidth(),
      'height' => $image->getHeight(),
    ]);
    
    if (!$image->scale($target_dimensions['width'], $target_dimensions['height'], $this->configuration['upscale'])) {
      $this->logger->error('Square pixels scale failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', ['%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $dimensions['width'] . 'x' . $dimensions['height']]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions, $uri) {
    if ($dimensions['width'] && $dimensions['height']) {
      $target_dimensions = $this->calculateTargetDimensions($dimensions);
      Image::scaleDimensions($dimensions, $target_dimensions['width'], $target_dimensions['height'], $this->configuration['upscale']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = [
      '#theme' => 'square_pixels_scale_summary',
      '#data' => $this->configuration,
    ];
    $summary += parent::getSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'square_pixels' => NULL,
      'max_width' => NULL,
      'max_height' => NULL,
      'upscale' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['square_pixels'] = [
      '#type' => 'number',
      '#title' => $this->t('Total square pixels'),
      '#description' => $this->t('This target number will be approximated by multiplying the width and height of the scaled image.'),
      '#default_value' => $this->configuration['square_pixels'],
      '#field_suffix' => ' ' . t('square pixels'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $form['max_width'] = [
      '#type' => 'number',
      '#title' => t('Maximum width'),
      '#default_value' => $this->configuration['max_width'],
      '#field_suffix' => ' ' . t('pixels'),
      '#required' => FALSE,
      '#min' => 1,
    ];
    $form['max_height'] = [
      '#type' => 'number',
      '#title' => t('Maximum height'),
      '#default_value' => $this->configuration['max_height'],
      '#field_suffix' => ' ' . t('pixels'),
      '#required' => FALSE,
      '#min' => 1,
    ];
    $form['upscale'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['upscale'],
      '#title' => t('Allow Upscaling'),
      '#description' => t('Let an image become larger than its original size.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['square_pixels'] = $form_state->getValue('square_pixels');
    $this->configuration['max_width'] = $form_state->getValue('max_width');
    $this->configuration['max_height'] = $form_state->getValue('max_height');
    $this->configuration['upscale'] = $form_state->getValue('upscale');
  }

  /**
   * Calculate the target dimensions to use for scaling.
   * 
   * @param array $dimensions
   *   The original dimensions of the image
   * 
   * @return array
   *   The target dimensions to use for scaling.
   */
  protected function calculateTargetDimensions($dimensions) {
    // Calculate the aspect percentage.
    $aspect = $dimensions['height'] / $dimensions['width'];

    // Calculate the scale amount.
    $scale = sqrt($this->configuration['square_pixels'] / ($dimensions['width'] * $dimensions['height']));
  
    // Set the scaled width and height.
    $width = $dimensions['width'] * $scale;
    $height = $dimensions['height'] * $scale;

    // If one or more maximums is set and a maximum is exceeded, adjust the
    // height and width as necessary.
    if (($this->configuration['max_width'] && $width > $this->configuration['max_width']) || ($this->configuration['max_height'] && $height > $this->configuration['max_height'])) {
      if (($this->configuration['max_width'] && !$this->configuration['max_height']) || ($this->configuration['max_width'] && $this->configuration['max_height'] && $aspect < $this->configuration['max_height'] / $this->configuration['max_width'])) {
        $width = $this->configuration['max_width'];
        $height = $width * $aspect;
      }
      else {
        $height = $this->configuration['max_height'];
        $width = $height / $aspect;
      }
    }

    return [
      'width' => (int) round($width),
      'height' => (int) round($height),
    ];
  }

}
