<?php
/**
 * @file
 * Contains \Drupal\xhprof_sample\XHProfSample\RunFactory.
 */

namespace Drupal\xhprof_sample\XHProfSample;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RunFactory
 */
class RunFactory {

  /**
   * Get the active Run storage service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Configuration service.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Global container service.
   *
   * @return \Drupal\xhprof_sample\XHProfSample\RunInterface
   *   The active Run storage service.
   */
  final public static function getRunStore(ConfigFactoryInterface $config, ContainerInterface $container) {
    $storage = $config->get('xhprof_sample.settings')
      ->get('run_store') ?: 'xhprof_sample.run_file';

    return $container->get($storage);
  }
}
