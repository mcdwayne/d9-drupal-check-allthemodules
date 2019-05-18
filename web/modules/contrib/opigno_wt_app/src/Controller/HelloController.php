<?php

namespace Drupal\opigno_wt_app\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\opigno_wt_app\Model\HelloWorld;
use Symfony\Component\HttpFoundation\Response;

class HelloController extends ControllerBase {
  public function content() {
    return new Response( HelloWorld::getHelloWorld() );
  }
}