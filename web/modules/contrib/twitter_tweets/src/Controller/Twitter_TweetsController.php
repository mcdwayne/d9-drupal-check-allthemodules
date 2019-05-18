<?php

namespace Drupal\twitter_tweets\Controller;
use Drupal\Core\Controller\ControllerBase;
/** 
 *In case of no default template of module, below markup will be displayed
 */
class Twitter_TweetsController extends ControllerBase {
  public function content() {
    return array('#markup' => 'Hello Twitter!');
  }
  
}