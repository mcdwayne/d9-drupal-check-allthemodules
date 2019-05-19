<?php

namespace Drupal\views_restricted\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\views_restricted\ViewsRestrictedHelper;
use Drupal\views_restricted\ViewsRestrictedInterface;
use Drupal\views_ui\ViewUI;
use Symfony\Component\Routing\Route;

class ViewsUIAlterViewsRestrictedConverter implements ParamConverterInterface {

  /** @var ParamConverterInterface */
  private $viewsUiConverter;

  /**
   * ViewsUIConverterDecorator constructor.
   *
   * @param \Drupal\Core\ParamConverter\ParamConverterInterface $viewsUiConverter
   */
  public function __construct(\Drupal\Core\ParamConverter\ParamConverterInterface $viewsUiConverter) {
    $this->viewsUiConverter = $viewsUiConverter;
  }

  public function convert($value, $definition, $name, array $defaults) {
    // We have higher priority, so replace ViewUIConverter.
    $value = $this->viewsUiConverter->convert($value, $definition, $name, $defaults);
    if (empty($defaults['views_restricted'])) {
      throw new \LogicException(sprintf('Could not find required views restricted plugin.'));
    }
    $viewsRestrictedId = $defaults['views_restricted'];
    // Maybe it's already converted.
    if ($viewsRestrictedId instanceof ViewsRestrictedInterface) {
      $viewsRestrictedId = $viewsRestrictedId->getPluginId();
    }
    ViewsRestrictedHelper::setViewsRestrictedId($value, $viewsRestrictedId);
    return $value;
  }

  public function applies($definition, $name, Route $route) {
    return $this->viewsUiConverter->applies($definition, $name, $route);
  }

}
