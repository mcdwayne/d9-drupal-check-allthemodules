<?php

namespace Drupal\micro_site;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\micro_site\Routing\SiteRouteProvider;

/**
 * Overrides the router.route_provider service.
 *
 * Point to our customized one and adds url.site to the
 * required_cache_contexts renderer configuration.
 *
 * @see https://www.drupal.org/node/2662196#comment-10838164
 */
class MicroSiteServiceProvider extends ServiceProviderBase implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('router.route_provider');
    $definition->setClass(SiteRouteProvider::class);

    if ($container->hasParameter('renderer.config')) {
      $renderer_config = $container->getParameter('renderer.config');

      if (!in_array('url.site', $renderer_config['required_cache_contexts'])) {
        $renderer_config['required_cache_contexts'][] = 'url.site';
      }
      // Permissions related to site context are based on user referenced by site.
      // We need so to add the user as a cache context.
      if (!in_array('user', $renderer_config['required_cache_contexts'])) {
        $renderer_config['required_cache_contexts'][] = 'user';
      }

      $container->setParameter('renderer.config', $renderer_config);
    }
  }

}
