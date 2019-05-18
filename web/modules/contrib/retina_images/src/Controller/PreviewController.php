<?php

/**
 * @file
 * Controller for alternate preview image.
 */

namespace Drupal\retina_images\Controller;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Image\ImageFactory;
use Drupal\image\ImageStyleInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PreviewController
 * @package Drupal\retina_images\Controller
 */
class PreviewController extends ControllerBase {

  /**
   * Image settings config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a ImageStyleDownloadController object.
   *
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ImageFactory $image_factory, LoggerInterface $logger, ImmutableConfig $immutableConfig) {
    $this->imageFactory = $image_factory;
    $this->logger = $logger;
    $this->config = $immutableConfig;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('image.factory'),
      $container->get('logger.factory')->get('image'),
      $container->get('config.factory')->get('image.settings')
    );
  }

  /**
   * Deliver a retina version of the preview image.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\image\ImageStyleInterface $image_style
   */
  public function preview(Request $request, ImageStyleInterface $image_style) {
    $original_path = $this->config->get('preview_image');

    // Set up derivative file information.
    $preview_file = $image_style->buildUri($original_path);

    // Create derivative if necessary.
    if (!file_exists($preview_file)) {
      $image_style->createDerivative($original_path, $preview_file);
    }

    $preview_image = $this->imageFactory->get($preview_file);

    $path_info = pathinfo($preview_file);
    $filename = $path_info['basename'];

    $variables['derivative'] = [
      'url' => file_url_transform_relative(file_create_url($preview_file)),
      'width' => $preview_image->getWidth(),
      'height' => $preview_image->getHeight(),
    ];
    $image_style->transformDimensions($variables['derivative'], $preview_file);

    $image = [
      '#theme' => 'image',
      '#uri' => $variables['derivative']['url'] . '?cache_bypass=' . REQUEST_TIME,
      '#attributes' => [
        'width' => $variables['derivative']['width'],
        'height' => $variables['derivative']['height'],
      ]
    ];

    $image_tag = drupal_render($image);

    $output = <<<EOT
<html>
  <head>
  <title>$filename {$variables['derivative']['width']}&times;{$variables['derivative']['height']} pixels</title>
  </head>
  <body>
    $image_tag
  </body>
</html>

EOT;


    return Response::create($output);
  }
}
