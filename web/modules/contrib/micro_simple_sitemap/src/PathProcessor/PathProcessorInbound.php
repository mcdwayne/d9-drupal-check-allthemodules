<?php

namespace Drupal\micro_simple_sitemap\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PathProcessorMicroSiteSitemapVariant.
 */
class PathProcessorInbound implements InboundPathProcessorInterface {

  /**
   * The site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a Micro Site Negotiator object.
   *
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The site negotiator.
   */
  public function __construct(SiteNegotiatorInterface $site_negotiator) {
    $this->negotiator = $site_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if ($path === '/sitemap.xml') {
      $active_site = $this->negotiator->getActiveSite();
      if ($active_site instanceof SiteInterface) {
        $variant_name = $this->getVariantName($active_site);
        $path = '/sitemaps/' . $variant_name . '/sitemap.xml';
      }
    }
    return $path;
  }

  /**
   * Get the sitemap variant name given a micro site.
   *
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   The micro site entity.
   *
   * @return string
   *   The variant name for a micro site entity.
   */
  public function getVariantName(SiteInterface $site) {
    return 'site-' . $site->id();
  }

}
