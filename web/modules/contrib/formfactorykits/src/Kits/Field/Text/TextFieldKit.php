<?php

namespace Drupal\formfactorykits\Kits\Field\Text;

use Drupal\formfactorykits\Kits\FormFactoryKit;
use Drupal\formfactorykits\Kits\Traits\DescriptionTrait;
use Drupal\formfactorykits\Kits\Traits\SizeTrait;
use Drupal\formfactorykits\Kits\Traits\ValueTrait;

/**
 * Class TextfieldKit
 *
 * @package Drupal\formfactorykits\Kits\Field\Text
 */
class TextFieldKit extends FormFactoryKit {
  use DescriptionTrait;
  use SizeTrait;
  use ValueTrait;
  const ID = 'textfield';
  const TYPE = 'textfield';
  const MAXLENGTH_KEY = 'maxlength';
  const SIZE_KEY = 'size';
  const AUTOCOMPLETE_ROUTE_NAME_KEY = 'autocomplete_route_name';
  const AUTOCOMPLETE_ROUTE_PARAMETERS_KEY = 'autocomplete_route_parameters';

  /**
   * @param int $length
   *
   * @return static
   */
  public function setMaxLength($length) {
    return $this->set(self::MAXLENGTH_KEY, (int) $length);
  }

  /**
   * @param string $route
   *
   * @return static
   */
  public function setAutoCompleteRoute($route) {
    return $this->set(self::AUTOCOMPLETE_ROUTE_NAME_KEY, $route);
  }

  /**
   * @param array $default
   *
   * @return mixed
   */
  public function getAutoCompleteRouteParameters($default = []) {
    return $this->get(self::AUTOCOMPLETE_ROUTE_PARAMETERS_KEY, $default);
  }

  /**
   * @param array $params
   *
   * @return static
   */
  public function setAutoCompleteRouteParameters(array $params) {
    return $this->set(self::AUTOCOMPLETE_ROUTE_PARAMETERS_KEY, $params);
  }

  /**
   * @param string $key
   * @param string $value
   *
   * @return static
   */
  public function setAutoCompleteRouteParameter($key, $value) {
    $params = $this->getAutoCompleteRouteParameters();
    $params[$key] = (string) $value;
    return $this->setAutoCompleteRouteParameters($params);
  }
}
