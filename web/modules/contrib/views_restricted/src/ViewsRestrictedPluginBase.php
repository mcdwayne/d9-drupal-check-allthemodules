<?php

namespace Drupal\views_restricted;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\views\ViewEntityInterface;
use Symfony\Component\Routing\Route;

/**
 * Base class for views_restricted plugins.
 */
abstract class ViewsRestrictedPluginBase extends PluginBase implements ViewsRestrictedInterface {

  public function access(ViewEntityInterface $view, $display_id = NULL, $type = NULL, $table = NULL, $field = NULL, $alias = NULL, Route $route = NULL, RouteMatch $route_match = NULL) {
    if (!$display_id) {
      $display_id = $view->getExecutable()->current_display;
    }
    $result = $this->getAccess($view, $display_id, $type, $table, $field, $alias, $route, $route_match);
    if (ViewsRestrictedHelper::isDebugMode()) {
      $query = ViewsRestrictedHelper::makeInfoString($view, $display_id, $type, $table, $field, $alias);
      if ($result->isAllowed()) {
        \Drupal::messenger()->addMessage(t('Views restricted plugin @plugin allowed: @access', ['@plugin' => $this->getPluginId(), '@access' => $query]), MessengerInterface::TYPE_STATUS);
      }
      else {
        \Drupal::messenger()->addMessage(t('Views restricted plugin @plugin denied: @access', ['@plugin' => $this->getPluginId(), '@access' => $query]), MessengerInterface::TYPE_WARNING);
      }
      
      if (($queryMatch = ViewsRestrictedHelper::getBacktraceQuery()) && (0 === strpos($query, $queryMatch))) {
        $t_args = ['@query' => $query, '@backtrace' => ViewsRestrictedHelper::printableBacktrace(debug_backtrace())];
        \Drupal::messenger()->addMessage(t('Views restricted debug backtrace for @query:<pre>@backtrace</pre>', $t_args));
      }
    }
    return $result;
  }

  /**
   * @param \Drupal\views\ViewEntityInterface $view
   * @param string|null $display_id
   * @param string|null $type
   * @param string|null $table
   * @param string|null $field
   * @param string|null $alias
   * @param \Symfony\Component\Routing\Route|NULL $route
   * @param \Drupal\Core\Routing\RouteMatch|NULL $route_match
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  abstract public function getAccess(ViewEntityInterface $view, $display_id = NULL, $type = NULL, $table = NULL, $field = NULL, $alias = NULL, Route $route = NULL, RouteMatch $route_match = NULL);

}
