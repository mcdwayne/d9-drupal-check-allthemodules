<?php


namespace Drupal\blazemeter\Controller;

use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\State\State;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp;
use Symfony\Component\HttpFoundation\RequestStack;

class BlazemeterAutocompleteController extends ControllerBase{


  public function autocomplete(Request $request){
    $string = $request->query->get('q');
    $connection = Database::getConnection();
    $router = new RouteProvider($connection, new State(new KeyValueMemoryFactory()), new CurrentPathStack(new RequestStack()), new MemoryBackend('data'), \Drupal::service('path_processor_manager'), \Drupal::service('cache_tags.invalidator'));
    $routes = $router->getRoutesByPattern($string)->count();
    return new JsonResponse($routes);
  }
}