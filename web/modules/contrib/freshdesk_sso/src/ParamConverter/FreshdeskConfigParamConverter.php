<?php
namespace Drupal\freshdesk_sso\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\freshdesk_sso\Entity\FreshdeskConfig;
use Symfony\Component\Routing\Route;

class FreshdeskConfigParamConverter implements ParamConverterInterface {
  public function convert($value, $definition, $name, array $defaults) {
    return FreshdeskConfig::load($value);
  }

  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'freshdesk_config');
  }
}