<?php

namespace Drupal\image_max_size_crop\Plugin\ImageEffect;

use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\ImageEffect\CropImageEffect;

/**
 * Crop an image resource with respect for maximum size, and with only one dimension required.
 *
 * @ImageEffect(
 *   id = "image_max_size_crop",
 *   label = @Translation("Maximum size crop"),
 *   description = @Translation("Cropping will remove portions of an image to make it the specified dimensions. This style only resizes when the image dimension(s) is larger than the spedified dimension(s).")
 * )
 */
class MaxSizeCropImageEffect extends CropImageEffect {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    // Set the desired dimensions - if they don't change do nothing
    $desired_width = $image->getWidth();
    $desired_height = $image->getHeight();
    if ($this->configuration['width'] && $this->configuration['width'] < $image->getWidth()) {
      $desired_width = $this->configuration['width'];
    }
    if ($this->configuration['height'] && $this->configuration['height'] < $image->getHeight()) {
      $desired_height = $this->configuration['height'];
    }
    if ($image->getWidth() == $desired_width && $image->getHeight() == $desired_height) {
      return TRUE;
    }
    list($x, $y) = explode('-', $this->configuration['anchor']);
    $x = image_filter_keyword($x, $image->getWidth(), $desired_width);
    $y = image_filter_keyword($y, $image->getHeight(), $desired_height);
    if (!$image->crop($x, $y, $desired_width, $desired_height)) {
      $this->logger->error('Image crop failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()));
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['width']['#required']);
    unset($form['height']['#required']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    if ($form_state->isValueEmpty('width') && $form_state->isValueEmpty('height')) {
      $form_state->setErrorByName('data', $this->t('Width and height can not both be blank.'));
    }
  }

}
