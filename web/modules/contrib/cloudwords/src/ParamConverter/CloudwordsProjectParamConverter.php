<?php
namespace Drupal\cloudwords\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\Core\Logger;
use Symfony\Component\Routing\Route;

class CloudwordsProjectParamConverter implements ParamConverterInterface {
  public function convert($value, $definition, $name, array $defaults) {
    $projects = &drupal_static(__FUNCTION__, []);
    try {
      if (!isset($projects[$value])) {
        $projects[$value] = cloudwords_get_api_client()->get_project($value);
      }
      return $projects[$value];
    }
    catch (CloudwordsApiException $e) {
      // Log API error
      Logger('cloudwords')->error($e->getErrorMessage(), []);

      // Project not found, return empty object
      return new CloudwordsDrupalProject([]);
    }
  }

  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'cloudwords_project');
  }
}