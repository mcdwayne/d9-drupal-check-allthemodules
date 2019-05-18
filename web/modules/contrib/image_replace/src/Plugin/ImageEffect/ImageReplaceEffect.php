<?php

/**
 * @file
 * Contains \Drupal\image_replace\Plugin\ImageEffect\ImageReplaceEffect.
 */

namespace Drupal\image_replace\Plugin\ImageEffect;

use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\ImageEffectBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Rotates an image resource.
 *
 * @ImageEffect(
 *   id = "image_replace",
 *   label = @Translation("Replace image"),
 *   description = @Translation("Swap the original image if a replacement image was configured."),
 * )
 */
class ImageReplaceEffect extends ImageEffectBase {

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ImageFactory $image_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);

    $this->setConfiguration($configuration);
    $this->imageFactory = $image_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('image'),
      $container->get('image.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    $configuration = $this->getConfiguration();
    $replacement_file = image_replace_get($configuration['data']['image_style'], $image->getSource());
    if ($replacement_file) {
      $toolkit_id = $image->getToolkitId();
      $replacement_image = $this->imageFactory->get($replacement_file, $toolkit_id);
      if ($replacement_image) {
        $image->apply('image_replace', array('replacement_image' => $replacement_image));
      }
    }
  }

}
