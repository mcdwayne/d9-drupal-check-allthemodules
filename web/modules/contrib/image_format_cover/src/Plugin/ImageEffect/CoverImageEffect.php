<?php

namespace Drupal\image_format_cover\Plugin\ImageEffect;

use Drupal\Component\Utility\Image;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\Plugin\ImageEffect\ResizeImageEffect;

/**
 * Scales an image resource.
 *
 * @ImageEffect(
 *   id = "image_cover",
 *   label = @Translation("Cover"),
 *   description = @Translation("Cover will maintain the aspect-ratio of the original image. If only a single dimension is specified, the other dimension will be calculated.")
 * )
 */
class CoverImageEffect extends ResizeImageEffect {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    $configuration = $this->configuration;

    $sx = $configuration['width'] / $image->getWidth();
    $sy = $configuration['height'] / $image->getHeight();
    $sa = max($sx, $sy);
    $configuration['width'] = ceil($image->getWidth() * $sa);
    $configuration['height'] = ceil($image->getHeight() * $sa);

    if (!$image->scale($configuration['width'], $configuration['height'], $configuration['upscale'])) {
      $this->logger->error('Image cover failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', ['%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()]);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions, $uri) {
    $configuration = $this->configuration;
    if ($dimensions['width'] && $dimensions['height']) {
      Image::scaleDimensions($dimensions, $configuration['width'], $configuration['height'], $configuration['upscale']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = [
      '#theme' => 'image_cover_summary',
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
      'upscale' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['width']['#required'] = TRUE;
    $form['height']['#required'] = TRUE;
    $form['upscale'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['upscale'],
      '#title' => t('Allow Upscaling'),
      '#description' => t('Let scale make images larger than their original size.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
// TODO:
    if ($form_state->isValueEmpty('width') && $form_state->isValueEmpty('height')) {
      $form_state->setErrorByName('data', $this->t('Width and height can not both be blank.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['upscale'] = $form_state->getValue('upscale');
  }

}
