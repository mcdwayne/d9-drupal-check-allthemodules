<?php
/**
 * @file
 * Contains \Drupal\simplepodcast\Routing\RouteSubscriber.
 */
 namespace Drupal\simplepodcast\Routing;

 use Symfony\Component\Routing\Route;
 use Drupal\Core\Site\Settings;

 class SimplePodcastRoutes {

   public function routes() {
     $routes = array();


     $routes['simplepodcast.content'] = new Route(

       \Drupal::config('simplepodcast.settings')->get('rss_path_name'),

       array(
         '_controller' => '\Drupal\simplepodcast\Controller\SimplePodcastController::content',
         '_title' => 'SimplePodcast'
       ),

       array(
         '_permission'  => 'access content',
       )
     );
     return $routes;
   }

 }