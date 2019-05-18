<?php

namespace Drupal\imgix\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\image\ImageEffectInterface;
use Imgix\UrlBuilder;
use Psr\Log\LoggerInterface;

/**
 * Provides an class for creating Imgix Styles.
 */
class ImgixStyles implements ImgixStylesInterface {

  /**
   * Entity type manager.
   *
   * @var EntityTypeManagerInterface;
   */
  protected $entityTypeManager;

  /**
   * Config factory.
   *
   * @var ConfigFactoryInterface;
   */
  protected $config;

  /**
   * Image Factory.
   *
   * @var ImageFactory;
   */
  protected $imageFactory;

  /**
   * Logger.
   *
   * @var LoggerInterface;
   */
  protected $logger;

  /**
   * The selected image style.
   *
   * @var ImageStyle;
   */
  public $style;

  /**
   * The selected image.
   *
   * @var ImageInterface;
   */
  public $image;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Entity Type Manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Config Factory.
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   The image factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $config_factory,
    ImageFactory $imageFactory,
    LoggerInterface $logger
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->config = $config_factory->get('imgix.settings');
    $this->imageFactory = $imageFactory;
    $this->logger = $logger;
  }

  /**
   * Inherit.
   *
   * @param string $id
   *   The style to load.
   */
  public function loadStyle($id) {
    $this->style = $this
      ->entityTypeManager
      ->getStorage('image_style')
      ->load($id);
  }

  /**
   * Inherit.
   *
   * @param string $uri
   *   The uri for the image to load.
   */
  public function loadImage($uri) {
    $this->image = $this->imageFactory->get($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function buildUrl() {
    $uri = $this->image->getSource();

    // Pass trough if not enabled.
    if ($this->config->get('enable') == FALSE) {
      return $this->style->buildUrl($uri);
    }

    // Get the normal url.
    $full_path = parse_url(file_create_url($uri));

    $builder = new UrlBuilder(
      $this->config->get('source_domain'),
      $this->config->get('https'),
      $this->config->get('secure_url_token')
    );

    // Final path depends on mapping type.
    switch ($this->config->get('mapping_type')) {
      case 's3':
        // Strip out the bucket. Quite wobbly, this.
        $bucket = explode('/', ltrim($full_path['path'], '/'))[0];
        $final_path = '/' . ltrim(str_replace($bucket, '', $full_path['path']), '/');
        break;

      case 'webproxy':
        // Full url for webproxy. The url gets encoded later on.
        $final_path = file_create_url($uri);
        break;

      case 'webfolder':
      default:
        // If it's a webfolder mapping, then path must be the relative path.
        $final_path = $full_path['path'];
        break;
    }

    // Replace current domain with given Mapping URL.
    // TODO: Untested.
    if ($this->config->get('mapping_url')) {
      $final_path = str_replace($full_path['scheme'] . '://' . $full_path['host'], $this->config->get('mapping_url'), $final_path);
    }

    return $builder->createURL(
      $final_path,
      $this->getParamsFromStyle()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions) {
    /** @var ImageEffectInterface $effect */
    foreach ($this->style->getEffects() as $effect) {
      $effect->transformDimensions($dimensions, $this->image->getSource());
    }
  }

  /**
   * Given an ImageStyle, construct an array of imgix params.
   */
  protected function getParamsFromStyle() {
    // Todo: Make this a config.
    $defaults = [
      'auto' => 'format',
      'fit' => 'max',
    ];

    $default_dimensions = [
      'width' => $this->image->getWidth(),
      'height' => $this->image->getHeight(),
    ];

    $params = [
      'w' => $default_dimensions['width'],
      'h' => $default_dimensions['height'],
    ] + $defaults;

    foreach ($this->style->getEffects() as $effect) {
      $params = $this->convertEffect($effect) + $params;
    }

    return $params;
  }

  /**
   * Convert core effects to imgix effects.
   *
   * @param \Drupal\image\ImageEffectInterface $effect
   *   The effect to act on.
   *
   * @return array
   *   Array of imgix params.
   */
  protected function convertEffect(ImageEffectInterface $effect) {
    $params = [];

    $effect_config = $effect->getConfiguration()['data'];

    switch ($effect->getPluginId()) {
      default:
        $this->logger->notice('Unhandled effect: %effect.',
          array(
            '%effect' => $effect->getPluginId(),
          ));
        break;
      case 'image_scale':

        $params['w'] = $effect_config['width'];
        $params['h'] = $effect_config['height'];
        if ($effect_config['upscale']) {
          $params['fit'] = 'max';
        }
        break;

      case 'image_crop':
        $params['w'] = $effect_config['width'];
        $params['h'] = $effect_config['height'];
        $params['fit'] = 'crop';

        switch ($effect_config['anchor']) {
          case 'left-top':
            $params['crop'] = 'top,left';
            break;

          case 'center-top':
            $params['crop'] = 'top,center';
            break;

          case 'right-top':
            $params['crop'] = 'top,right';
            break;

          case 'left-center':
            $params['crop'] = 'center,left';
            break;

          case 'center-center':
            $params['crop'] = 'center';
            break;

          case 'right-center':
            $params['crop'] = 'center,right';
            break;

          case 'left-bottom':
            $params['crop'] = 'bottom,left';
            break;

          case 'center-bottom':
            $params['crop'] = 'bottom,center';
            break;

          case 'right-bottom':
            $params['crop'] = 'bottom,right';
        }
        break;

      case 'image_scale_and_crop':
        $params['w'] = $effect_config['width'];
        $params['h'] = $effect_config['height'];
        $params['fit'] = 'min';
        break;

      case 'image_resize':
        $params['w'] = $effect_config['width'];
        $params['h'] = $effect_config['height'];
        $params['fit'] = 'scale';
        break;
    }

    return $params;
  }

}
