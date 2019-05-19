<?php

namespace Drupal\social_course;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class SocialCourseServiceProvider.
 */
class SocialCourseServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('social_group.route_subscriber');
    $definition->setClass('Drupal\social_course\Routing\RouteSubscriber');
  }

}
