<?php

namespace Drupal\big_pipe_sessionless;

use Drupal\big_pipe_sessionless\EventSubscriber\HtmlResponseBigPipeSessionlessSubscriber;
use Drupal\big_pipe_sessionless\StackMiddleware\BigPipeSessionlessPageCache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @see \Drupal\big_pipe_sessionless\EventSubscriber\HtmlResponseBigPipeSessionlessSubscriber
 */
class BigPipeSessionlessServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('html_response.big_pipe_subscriber')
      ->setClass(HtmlResponseBigPipeSessionlessSubscriber::class)
      ->addArgument(new Reference('big_pipe_sessionless'))
      ->addArgument(new Reference('session_configuration'));

    $container->getDefinition('http_middleware.page_cache')
      ->setClass(BigPipeSessionlessPageCache::class);
  }

}
