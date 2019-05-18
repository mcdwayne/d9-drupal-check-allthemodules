<?php
namespace Drupal\cloudwords\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;

use Symfony\Component\Routing\Route;

class CloudwordsLanguageParamConverter implements ParamConverterInterface {
  public function convert($value, $definition, $name, array $defaults) {
    module_load_include('inc', 'cloudwords', 'cloudwords.languages');
    $map = _cloudwords_map_cloudwords_drupal();
    $list = cloudwords_language_list();

    if (isset($map[$value])) {
      return new \Drupal\cloudwords\CloudwordsLanguage([
        'languageCode' => $value,
        'display' => $list[$map[$value]],
      ]);
    }

    return FALSE;
  }

  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'cloudwords_language');
  }
}