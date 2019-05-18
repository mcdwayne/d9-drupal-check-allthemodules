<?php

namespace Drupal\frontend;

/**
 * Provides an interface definition a page entity.
 */
interface PageInterface extends ContainerInterface {

  public function getLayout();

  public function getPath();

}
