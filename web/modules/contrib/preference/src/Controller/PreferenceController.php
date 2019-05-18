<?php

/**
 * @file
 * Contains \Drupal\preference\Controller\PreferenceController.
 */

namespace Drupal\preference\Controller;

use Drupal\preference\Update;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
         

/**
 * Controller routines for preference routes.
 */
class PreferenceController extends ControllerBase{
    protected $update;

    /**
     * {@inheritdoc}
     */
    public function __construct(Update $update) {
        $this->update = $update;
    }

    
    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
      return new static(
        $container->get('preference.update')
      );
    }

    /**
     * Returns an administrative overview of all books.
     *
     * @return array
     *   A render array representing the administrative page content.
     */
    public function preference_page1_callback() {

        return array('msg' => 'success');       
    }
    
    
    /**
     * Returns an administrative overview of all books.
     *
     * @return array
     *   A render array representing the administrative page content.
     */
    public function preference_update_callback() {
        
        // Then later on (inside your controller's class), you have a function used
        // for the route_name (on a hook_menu() item in your .module file), this
        // function can return a JSON response...
        $res = $this->update->getUpdated();
        $response = new Response();
        $response->setContent(json_encode($res));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
        
        
    }

}
