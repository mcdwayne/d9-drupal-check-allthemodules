<?php

namespace Drupal\og_sm\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\og_sm\SiteManagerInterface;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting Site IDs to full objects.
 *
 * In order to use it you should specify some additional options in your route:
 * @code
 * example.route:
 *   path: foo/{example}
 *   options:
 *     parameters:
 *       example:
 *         type: og_sm:site
 * @endcode
 */
class SiteConverter implements ParamConverterInterface {

  /**
   * The site manager.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

  /**
   * Constructs a new SiteConverter.
   *
   * @param \Drupal\og_sm\SiteManagerInterface $siteManager
   *   The site manager.
   */
  public function __construct(SiteManagerInterface $siteManager) {
    $this->siteManager = $siteManager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $site = $this->siteManager->load($value);
    if ($site) {
      return $site;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['type']) && $definition['type'] === 'og_sm:site';
  }

}
