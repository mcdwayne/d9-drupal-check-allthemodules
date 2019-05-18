<?php

namespace Drupal\auto_retina\Entity;

use Drupal\image\Entity\ImageStyle;

/**
 * Image style class to support retina generation.
 */
class RetinaImageStyle extends ImageStyle {

  /**
   * {@inheritdoc}
   */
  public function __construct($values, $entity_type) {

    // TODO How to inject?
    $this->configFactory = \Drupal::service('config.factory');
    $this->imageFactory = \Drupal::service('image.factory');
    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function createDerivative($original_uri, $derivative_uri) {

    // Figure out if we are going to alter the quality of the generated
    // magnified image per our settings.
    $quality_multiplier = $this->getThirdPartySetting('auto_retina', 'quality_multiplier');
    $must_alter_quality = $quality_multiplier != 1;


    // If we must alter quality, first see if image_style_quality is doing
    // something for this image.  If that module controls the quality then we
    // tweak it's settings.
    if ($must_alter_quality && \Drupal::moduleHandler()
        ->moduleExists('image_style_quality')) {
      // First check the effects on this style and make sure the
      // image_style_quality effect is not already used.  If it is we should not
      // alter it; it takes precedence.
      foreach ($this->getEffects() as $effect) {
        if ($effect->getPluginId() === 'image_style_quality') {
          $config = $effect->getConfiguration();
          $config['data']['quality'] *= $quality_multiplier;
          $effect->setConfiguration($config);
          $must_alter_quality = FALSE;
        }
      }
    }

    // If we still must alter quality then do it at the system level.
    if ($must_alter_quality) {
      if ($this->imageFactory->getToolkitId() === 'imagemagick') {
        $config = $this->configFactory->get('imagemagick.settings');
        $config->setModuleOverride([
          'quality' => $quality_multiplier * $config->get('quality'),
        ]);
      }
      else {
        $config = $this->configFactory->get('system.image.gd');
        $config->setModuleOverride([
          'jpeg_quality' => $quality_multiplier * $config->get('jpeg_quality'),
        ]);
      }
    }

    \Drupal::moduleHandler()
      ->alter('auto_retina_create_derivative', $this, $original_uri, $derivative_uri);

    // If the source file doesn't exist, return FALSE without creating folders.
    $image = $this->imageFactory->get($original_uri);
    if (!$image->isValid()) {
      return FALSE;
    }

    // Get the folder for the final location of this style.
    $directory = \Drupal::service("file_system")->dirname($derivative_uri);

    // Build the destination folder tree if it doesn't already exist.
    if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      \Drupal::logger('image')
        ->error('Failed to create style directory: %directory', ['%directory' => $directory]);
      return FALSE;
    }

    foreach ($this->getEffects() as $effect) {
      $effect->applyEffect($image);
    }

    if (!$image->save($derivative_uri)) {
      if (file_exists($derivative_uri)) {
        \Drupal::logger('image')
          ->error('Cached image file %destination already exists. There may be an issue with your rewrite configuration.', ['%destination' => $derivative_uri]);
      }
      return FALSE;
    }

    return TRUE;
  }

}
