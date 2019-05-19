<?php

namespace Drupal\wisski_mirador\Controller;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\wisski_core;
use Drupal\wisski_salz\AdapterHelper;
use Drupal\wisski_salz\Plugin\wisski_salz\Engine\Sparql11Engine;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\wisski_core\WisskiCacheHelper;
//optional
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
//optional end

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class MiradorController extends ControllerBase {

  public function start() {

    $form = array();
    
    $form['#markup'] = '<div id="viewer"></div>';
    $form['#allowed_tags'] = array('div', 'select', 'option','a', 'script');
#    #$form['#attached']['drupalSettings']['wisski_jit'] = $wisski_individual;
    $form['#attached']['library'][] = "wisski_mirador/mirador";

    return $form;

/*
    $response = new Response();
    $response->setContent('<!DOCTYPE html>
    <html lang="en">
      <head>
          <title>Simple Viewer</title>
              <meta charset="UTF-8">
                  <meta name="viewport" content="width=device-width, initial-scale=1.0">
                      <style>
                            #viewer {
                                    width: 100%;
                                            height: 100%;
                                                    position: fixed;
                                                          }
                                                              </style>
                                                                  <link rel="stylesheet" type="text/css" href="/libraries/mirador/build/mirador/css/mirador-combined.css">
                                                                      <script src="/libraries/mirador/build/mirador/mirador.js"></script>
                                                                        </head>
                                                                          <body>
                                                                              <div id="viewer"></div>
                                                                                  <script type="text/javascript">
                                                                                        $(function() {
                                                                                                Mirador({
                                                                                                          id: "viewer",
                                                                                                                    data: []
                                                                                                                            });
                                                                                                                                  });
                                                                                                                                      </script>
                                                                                                                                        </body>
                                                                                                                                        </html>');
        
    
    return $response;
 */
  }
  
}
