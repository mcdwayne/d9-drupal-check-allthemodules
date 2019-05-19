<?php

namespace Drupal\simplenews_stats\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\simplenews_stats\SimplenewsStatsPage;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\simplenews_stats\SimplenewsStatsEngine;
use Drupal\simplenews_stats\SimplenewsStatsAllowedLinks;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;

/**
 * Provides route responses for hits and stats page.
 */
class SimplenewsStatsController extends ControllerBase {

  /**
   * SimplenewsStatsEngine.
   *
   * @var \Drupal\simplenews_stats\SimplenewsStatsEngine
   */
  protected $simplenewsStatsEngine;

  /**
   * SimplenewsStatsAllowedLinks.
   *
   * @var \Drupal\simplenews_stats\SimplenewsAllowedLinks
   */
  protected $simplenewsStatsAllowedLinks;

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * SimplenewsStatsController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request stack.
   * @param \Drupal\simplenews_stats\SimplenewsStatsEngine $simplenewsStatsEngine
   *   The simplenews stats engine.
   * @param Drupal\simplenews_stats\SimplenewsStatsAllowedLinks $simplenewsStatsAllowedLinks
   *   The simplenews stats Allowed links manager.
   */
  public function __construct(RequestStack $request, SimplenewsStatsEngine $simplenewsStatsEngine, SimplenewsStatsAllowedLinks $simplenewsStatsAllowedLinks) {
    $this->request                     = $request;
    $this->simplenewsStatsEngine       = $simplenewsStatsEngine;
    $this->simplenewsStatsAllowedLinks = $simplenewsStatsAllowedLinks;
  }

  public static function create(ContainerInterface $container) {
    $request                     = $container->get('request_stack');
    $simplenewsStatsEngine       = $container->get('simplenews_stats.engine');
    $simplenewsStatsAllowedLinks = $container->get('simplenews_stats.allowedlinks');

    return new static($request, $simplenewsStatsEngine, $simplenewsStatsAllowedLinks);
  }

  /**
   * Send image.
   */
  public function hitView() {
    $response = new Response();
    $image    = file_get_contents(drupal_get_path('module', 'simplenews_stats') . '/image/simple.png');
    $response->setContent($image);
    $response->headers->set('Content-Type', 'image/png');
    $response->headers->set('Content-Transfer-Encoding', 'binary');
    return $response;
  }

  /**
   * Catch click and redirect to link.
   *
   * @param string $tag 
   *   The tag.
   */
  public function hitClick($tag) {

    $entities = $this->simplenewsStatsEngine->getTagEntities($tag);
    if ($entities === FALSE) {
      return new RedirectResponse('/');
    }

    // Log click and redirect to the external link if it allowed.
    $link = $this->request->getCurrentRequest()->query->get('link');
    if ($this->simplenewsStatsAllowedLinks->isLinkExist($entities['entity'], $link)) {
      $this->simplenewsStatsEngine->addStatTags($tag, $link);

      // Use TrustedRedirectResponse for this external redirection.
      return new TrustedRedirectResponse(Url::fromUri($link)->toString());
    }

    // Redirect to the entity if the link is not allowed.
    return new RedirectResponse($entities['entity']->toUrl()->toString());
  }

  /**
   * Stats page callback.
   *
   * @param Drupal\Core\Entity\EntityInterface $node 
   *   The node used by simplenews.
   */
  public function stats(EntityInterface $node) {
    $simplenewsStatPage = new SimplenewsStatsPage($node);
    return $simplenewsStatPage->getpage();
  }

}
