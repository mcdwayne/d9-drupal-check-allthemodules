<?php

namespace Drupal\thumbor_effects_crop\Plugin\ImageEffect;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\crop\CropInterface;
use Drupal\crop\Entity\Crop;
use Drupal\thumbor_effects\Plugin\ImageEffect\ThumborImageEffect;
use Drupal\thumbor_effects_crop\Exception\InvalidCropEffectException;
use Drupal\thumbor_effects_crop\ThumborCropManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Thumbor Smart Imaging effects with manual crop.
 *
 * @ImageEffect(
 *   id = "thumbor_effects_crop",
 *   label = @Translation("Thumbor Effects Crop"),
 *   description = @Translation("Use Thumbor Smart Imaging effects with a manual (aspect ratio) crop.")
 * )
 *
 * @todo think about what happens for smaller images than the image style?
 */
class ThumborCropImageEffect extends ThumborImageEffect {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('image'),
      $container->get('http_client'),
      $container->get('image.factory')
    );
  }

  /**
   * {@inheritdoc}
   *
   * The Responsive images element needs the dimensions of the image before the
   * image is modified.
   *
   * @throws \Drupal\thumbor_effects_crop\Exception\InvalidCropEffectException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function transformDimensions(array &$dimensions, $uri): void {
    $image = $this->imageFactory->get($uri);
    $configuration = self::modifyConfiguration($this->configuration, $image);

    if ($configuration['image_size']['width']) {
      $dimensions['width'] = $configuration['image_size']['width'];

      if ($configuration['image_size']['height']) {
        $dimensions['height'] = $configuration['image_size']['height'];
      }
      return;
    }

    $this->applyEffect($image);

    $dimensions = [
      'width' => $image->getWidth(),
      'height' => $image->getHeight(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $settings = &$form['settings'];

    unset(
      $settings['image_size']['#states'],
      $settings['image_size_enable'],
      $settings['fit_in'],
      $settings['manual_crop_enable'],
      $settings['manual_crop']
    );

    $settings['image_size']['width']['#required'] = TRUE;
    $settings['image_size']['height']['#required'] = TRUE;
    $settings['image_size']['height']['#description'] = $this->t('Fallback height for when no aspect ratio has been selected for the individual image. A value of zero means to proportional size to the original image.');

    return $form;
  }

  /**
   * Get the Thumbor URL for the specified transformations and image.
   *
   * @param array $configuration
   *   The Thumbor Effects configuration.
   * @param \Drupal\Core\Image\ImageInterface $image
   *   The image that needs to be transformed.
   *
   * @return string
   *   The formatted Thumbor URL.
   *
   * @todo get rid of static, see url alter.
   *
   * @throws \Drupal\thumbor_effects_crop\Exception\InvalidCropEffectException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function getUrl(array $configuration, ImageInterface $image): string {
    $configuration = self::modifyConfiguration($configuration, $image);
    return parent::getUrl($configuration, $image);
  }

  /**
   * Modify the configuration of the effect with file specific overwrites.
   *
   * @param array $configuration
   *   The image style effect configuration.
   * @param \Drupal\Core\Image\ImageInterface $image
   *   The image to modify.
   *
   * @return array
   *   The modified configuration.
   *
   * @throws \Drupal\thumbor_effects_crop\Exception\InvalidCropEffectException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected static function modifyConfiguration(array $configuration, ImageInterface $image): array {
    $crop = Crop::findCrop($image->getSource(), NULL);

    if ($crop === NULL) {
      return $configuration;
    }

    $configuration = static::applyAspectRatio($configuration, $crop);

    return $configuration;
  }

  /**
   * Apply the aspect ratio to the image style effect.
   *
   * @param array $configuration
   *   The image style effect configuration.
   * @param \Drupal\crop\CropInterface $crop
   *   The crop for this image's configuration.
   *
   * @return array
   *   The modified configuration.
   *
   * @throws \Drupal\thumbor_effects_crop\Exception\InvalidCropEffectException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected static function applyAspectRatio(array $configuration, CropInterface $crop): array {
    $aspect_ratio = ThumborCropManager::getAspectRatio($crop);

    if ($aspect_ratio === NULL) {
      return $configuration;
    }

    if ($aspect_ratio === '0:0') {
      return $configuration;
    }

    if (empty($configuration['image_size']['width'])) {
      return $configuration;
    }

    $aspect_ratio = explode(':', $aspect_ratio);

    if (count($aspect_ratio) !== 2 || !is_numeric($aspect_ratio[0]) || !is_numeric($aspect_ratio[1]) || $aspect_ratio[0] === '0') {
      throw new InvalidCropEffectException('Invalid aspect ratio');
    }

    $configuration['image_size']['height'] = (string) round($configuration['image_size']['width'] * $aspect_ratio[1] / $aspect_ratio[0]);

    return $configuration;
  }

}
