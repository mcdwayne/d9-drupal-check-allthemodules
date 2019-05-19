<?php

namespace Drupal\views_restricted;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Database\Connection;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Converts parameters for upcasting database record IDs to full std objects.
 *
 * @DCG
 * To use this converter specify parameter type in a relevant route as follows:
 * @code
 * views_restricted.views_restricted_parameter_converter:
 *   path: example/{record}
 *   defaults:
 *     _controller: '\Drupal\views_restricted\Controller\ViewsRestrictedController::build'
 *   requirements:
 *     _access: 'TRUE'
 *   options:
 *     parameters:
 *       record:
 *        type: views_restricted
 * @endcode
 *
 * Note that for entities you can make use of existing parameter converter
 * provided by Drupal core.
 * @see \Drupal\Core\ParamConverter\EntityConverter
 */
class ViewsRestrictedParamConverter implements ParamConverterInterface {

  /** @var ViewsRestrictedPluginManager */
  protected $viewsRestrictedPluginManager;

  /**
   * Constructs a new ViewsRestrictedParamConverter.
   *
   * @param ViewsRestrictedPluginManager $viewsRestrictedPluginManager
   */
  public function __construct(ViewsRestrictedPluginManager $viewsRestrictedPluginManager) {
    $this->viewsRestrictedPluginManager = $viewsRestrictedPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    try {
      return $this->viewsRestrictedPluginManager->createInstance($value);
    } catch (PluginException $e) {
    }
    return $this->viewsRestrictedPluginManager->createInstance('views_restricted_legacy');
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['type']) && $definition['type'] === 'views_restricted';
  }

}
