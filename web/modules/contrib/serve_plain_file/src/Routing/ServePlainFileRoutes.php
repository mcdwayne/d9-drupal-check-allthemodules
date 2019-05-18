<?php

namespace Drupal\serve_plain_file\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dynamically generate routes for all configured served files.
 */
class ServePlainFileRoutes {

  /**
   * Provides dynamic routes for each content entity.
   */
  public function routes() {
    $collection = new RouteCollection();
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('served_file');
    /** @var \Drupal\serve_plain_file\Entity\ServedFileInterface[] $served_files */
    $served_files = $storage->loadMultiple();

    foreach ($served_files as $served_file) {
      $name = 'serve_plain_file.served_file.' . $served_file->id() . '.route';
      $path = '/' . $served_file->getPath();
      $defaults = [
        '_controller' => '\Drupal\serve_plain_file\Controller\ServePlainFile::content',
        // Prevent redirects in multilingual sites to the language path prefix.
        '_disable_route_normalizer' => TRUE,
        'id' => $served_file->id(),
      ];

      $requirements = ['_access' => 'TRUE'];
      $options = ['parameters' => [['id' => ['type' => 'string']]]];

      $route = new Route($path, $defaults, $requirements, $options);
      $collection->add($name, $route);
    }

    return $collection;
  }

}
