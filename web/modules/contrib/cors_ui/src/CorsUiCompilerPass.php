<?php

namespace Drupal\cors_ui;

use Drupal\Core\Cache\ListCacheBinsPass;
use Drupal\Core\DependencyInjection\Compiler\CorsCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The CORS UI compiler pass.
 */
class CorsUiCompilerPass extends CorsCompilerPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    // Required to load config at this early stage. @todo, find a better way
    // https://www.drupal.org/node/2794109.
    (new ListCacheBinsPass())->process($container);
    $config = $container->get('config.factory')->get('cors_ui.configuration')->get();
    if (!empty($config)) {
      $container->setParameter('cors.config', $config);
    }
    parent::process($container);
  }

}
