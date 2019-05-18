<?php
namespace Drupal\cloudwords\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;

use Symfony\Component\Routing\Route;

class CloudwordsFileParamConverter implements ParamConverterInterface {
  public function convert($value, $definition, $name, array $defaults) {
    $pid = $defaults['cloudwords_project']->getId();
    $files = &drupal_static(__FUNCTION__, array());

    $files[$pid] = null;
    try {
      if (!isset($files[$pid])) {
        $files[$pid] = cloudwords_get_api_client()->get_project_reference($pid, $value);
      }
      return $files[$pid];
    }
    catch (CloudwordsApiException $e) {
      // Don't make 2 failed rest calls.
      $files[$pid] = TRUE;
      return FALSE;
    }
  }

  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'cloudwords_file');
  }
}