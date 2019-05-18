<?php

namespace Drupal\gpx_track_elevation\ParamConverter;
 
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;
 
class widWPTConverter implements ParamConverterInterface {
  
  public function convert($value, $definition, $name, array $defaults) {
    return \Drupal::database()->select('gpx_track_elevation', 'w')->condition('w.wid', $value, '=')->fields('w')->execute()->fetchAssoc();
  
  }
 
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'wid');
  }
}