<?php

namespace Drupal\docker\Controller;

class DockerController {

  /**
   * Returns the main page.
   */
  public function dockerPage() {
    $items = array(
      l('Builds', 'docker/builds'),
      l('Hosts', 'docker/hosts')
    );
    return array(
      '#theme'=> 'item_list',
      '#items' => $items
    );
    return array($build);
  }
}