<?php

namespace Drupal\micro_simple_sitemap\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\Entity\Site;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check access on the variant micro site sitemap.
 */
class MicroSimpleSitemapAccess implements ContainerInjectionInterface {

  /**
   * The micro site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * MicroSimpleSitemapAccess constructor.
   *
   * @param \Drupal\micro_site\SiteNegotiatorInterface $negotiator
   *   The micro site negotiator.
   */
  public function __construct(SiteNegotiatorInterface $negotiator) {
    $this->negotiator = $negotiator;
  }

  /**
   * The constructor.
   *
   * @inheritdoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('micro_site.negotiator')
    );
  }

  /**
   * Control access on the variant type micro_site route.
   */
  public function access(AccountInterface $account, $variant) {
    $active_site = $this->negotiator->getActiveSite();
    $site_id = $this->getSiteIdFromVariant($variant);
    $site = Site::load($site_id);
    if ($site instanceof SiteInterface && !$active_site instanceof SiteInterface) {
      return AccessResult::forbidden('micro site variant can be accessed only from the micro site related.');
    }
    if ($active_site instanceof SiteInterface) {
      if ($active_site->id() != $site_id) {
        return AccessResult::forbidden('micro site variant can be accessed only from the micro site related.');
      }
      if (!$active_site->isPublished()) {
        return AccessResult::forbidden('micro site variant can be accessed only if the micro site is published.');
      }
    }
    return AccessResult::allowed();
  }

  /**
   * Get the site id from the variant name.
   *
   * @param string $variant
   *   The variant name.
   *
   * @return null|int
   *   The site id.
   */
  protected function getSiteIdFromVariant($variant) {
    $site_id = NULL;
    $parts = explode('-', $variant);
    if (isset($parts[1])) {
      $site_id = $parts[1];
    }
    return $site_id;
  }

}
