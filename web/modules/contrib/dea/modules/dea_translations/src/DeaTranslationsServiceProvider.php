<?php

namespace Drupal\dea_translations;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;


class DeaTranslationsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    /** @var \Symfony\Component\DependencyInjection\Definition $content_translation_overview_access */
    $content_translation_overview_access = $container->getDefinition('content_translation.overview_access');
    /** @var \Symfony\Component\DependencyInjection\Definition $dea_overview_access */
    $dea_overview_access = $container->getDefinition('dea_translations.overview_access');

    // replace Core's Content Translation Access Manager by DEA's one
    $content_translation_overview_access->setClass($dea_overview_access->getClass());
    $content_translation_overview_access->setArguments($dea_overview_access->getArguments());

    /** @var \Symfony\Component\DependencyInjection\Definition $content_translation_manage_access */
    $content_translation_manage_access = $container->getDefinition('content_translation.manage_access');
    /** @var \Symfony\Component\DependencyInjection\Definition $dea_manage_access */
    $dea_manage_access = $container->getDefinition('dea_translations.manage_access');

    // replace Core's Content Translation Access Manager by DEA's one
    $content_translation_manage_access->setClass($dea_manage_access->getClass());
    $content_translation_manage_access->setArguments($dea_manage_access->getArguments());
  }
}