<?php
/**
 * Created by PhpStorm.
 * User: christianfritsch
 * Date: 06.10.15
 * Time: 11:04
 */

namespace Drupal\tracdelight;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;

class TracdelightFactory {

  public static function createTracdelight($httpClient, $entityManager, ConfigFactoryInterface $configFactory )
  {

    $config = $configFactory->get('tracdelight.config');

    return new Tracdelight($httpClient, $entityManager, $config->get('access_key'));
  }
}
