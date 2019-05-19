<?php
namespace Drupal\taxonomy_facets\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;


class TaxonomyFacetsPathProcessor implements InboundPathProcessorInterface {

  public function processInbound($path, Request $request) {
    if (strpos($path, '/listings/') === 0) {
      $names = preg_replace('|^\/listings\/|', '', $path);
      $names = str_replace('/', ':', $names);
      return "/listings/$names";
    }
    return $path;
  }
}
