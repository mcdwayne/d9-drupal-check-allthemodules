<?php

namespace Drupal\video_sitemap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\video_sitemap\VideoSitemapGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class VideoSitemapController.
 *
 * @package Drupal\video_sitemap\Controller
 */
class VideoSitemapController extends ControllerBase {

  /**
   * The video sitemap generator service.
   *
   * @var \Drupal\video_sitemap\VideoSitemapGenerator
   */
  protected $generator;

  /**
   * VideoSitemapController constructor.
   *
   * @param \Drupal\video_sitemap\VideoSitemapGenerator $generator
   *   The video sitemap generator service.
   */
  public function __construct(VideoSitemapGenerator $generator) {
    $this->generator = $generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('video_sitemap.generator')
    );
  }

  /**
   * Returns the whole sitemap or its requested chunk.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @throws NotFoundHttpException
   *
   * @return object
   *   Returns an XML response.
   */
  public function getSitemap(Request $request) {
    $output = $this->generator->getSitemap($request->query->getInt('page'));
    if (!$output) {
      throw new NotFoundHttpException();
    }

    return new Response($output, Response::HTTP_OK, [
      'content-type' => 'application/xml',
      'X-Robots-Tag' => 'noindex',
    ]);
  }

}
