<?php

namespace Drupal\term_node;

use Symfony\Component\HttpFoundation\Request;

interface ResolverInterface {

  /**
   * The path that should be used.
   * @param string $path
   * @param int $entity_id
   *
   * @return string
   */
  public function getPath($path, $entity_id);

}
