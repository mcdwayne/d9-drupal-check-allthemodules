<?php

namespace Drupal\views_restricted;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\views\ViewEntityInterface;
use Drupal\views_ui\ViewUI;

class ViewEditForm extends \Drupal\views_ui\ViewEditForm {

  public function form(array $form, FormStateInterface $form_state) {
    $build = parent::form($form, $form_state);
    $display_id = $this->displayID;
    // Parent disables caching, so fortunately we can neglect that here.
    $view = ViewsRestrictedHelper::extractViewsUi($form_state);
    if ($viewsRestricted = ViewsRestrictedHelper::extractViewsRestricted($view)) {
      $accessResult = $viewsRestricted->access($view);
      ViewsRestrictedHelper::removeBuildIfNoAccess($build, $accessResult);
      if ($build) {
        self::massageAllLinks($build, $viewsRestricted);
        self::massageFormDisplayDropdown($build, $viewsRestricted, $view, $display_id);
      }
    }
    else {
      throw new \LogicException(sprintf('Could not find required views restricted plugin.'));
    }
    return $build;
  }

  /**
   * @param \Drupal\views_ui\ViewUI $view
   * @param string $display
   *
   * @return array
   */
  public function getDisplayDetails($view, $display) {
    $build = parent::getDisplayDetails($view, $display);
    if ($viewsRestricted = ViewsRestrictedHelper::extractViewsRestricted($view)) {
      $display_id = $this->displayID;
      // Filter the buckets.
      foreach (Element::children($build['columns']) as $column) {
        $columnHasActiveBuckets = FALSE;
        if (isset($build['columns'][$column])) {
          $buckets = &$build['columns'][$column];
          foreach (Element::children($buckets) as $type) {
            $accessResult = $viewsRestricted->access($view, NULL, $type);
            ViewsRestrictedHelper::removeBuildIfNoAccess($buckets[$type], $accessResult);
            if ($accessResult->isAllowed()) {
              $columnHasActiveBuckets = TRUE;
            }
          }
        }
        if (!$columnHasActiveBuckets) {
          ViewsRestrictedHelper::removeBuild($buckets);
        }
      }
      // Rewrite links. We have to do this here as ajax calls getDisplayTab().
      self::massageAllLinks($build, $viewsRestricted);
      self::massageSettingsTopDisplayDropdown($build, $viewsRestricted, $view, $display_id);
      self::massageSettingsTopDisplayName($build);
      self::massageSettingsTop($build['top']);
    }
    else {
      throw new \LogicException(sprintf('Could not find required views restricted plugin.'));
    }
    return $build;
  }

  public function renderDisplayTop(ViewUI $view) {
    $build = parent::renderDisplayTop($view);
    if ($viewsRestricted = ViewsRestrictedHelper::extractViewsRestricted($view)) {
      $display_id = $this->displayID;
      // Rewrite links. We have to do this here as ajax calls renderDisplayTop().
      self::massageAllLinks($build, $viewsRestricted);
      self::massageDisplayTopTabs($build, $viewsRestricted, $view, $display_id);
      self::massageDisplayTopAdd($build, $viewsRestricted, $view, $display_id);
      self::massageDisplayTopExtraActions($build);
    }
    else {
      throw new \LogicException(sprintf('Could not find required views restricted plugin.'));
    }
    return $build;
  }

  protected function actions(array $form, FormStateInterface $form_state) {
    $build = parent::actions($form, $form_state);
    $display_id = $this->displayID;
    $view = ViewsRestrictedHelper::extractViewsUi($form_state);
    if ($viewsRestricted = ViewsRestrictedHelper::extractViewsRestricted($view)) {
      self::massageActions($build, $viewsRestricted, $view, $display_id);
    }
    else {
      throw new \LogicException(sprintf('Could not find required views restricted plugin.'));
    }
    return $build;
  }


  private static function massageAllLinks(array &$build, ViewsRestrictedInterface $views_restricted) {
    // Add our views_restricted parameter to all links.
    $id = $views_restricted->getPluginId();
    Urlifyer::urlify($build);
    array_walk_recursive($build, function (&$element) use ($id) {
      $routes = ViewsRestrictedHelper::getRouteAlter();
      if ($element instanceof Url) {
        // Set our plugin.
        $routeName = $element->getRouteName();
        if (isset($routes[$routeName])) {
          $element->setRouteParameter('views_restricted', $id);
        }
        // Hide if no access.
        if (!$element->access()) {
          $element = [];
        }
      }
    });
  }

  private static function massageDisplayTopTabs(array &$build, ViewsRestrictedInterface $viewsRestricted, ViewEntityInterface $view, $display_id) {
    // Tabs links will be governed by access, but current tab will always be
    // shown, so we add the option to hide all tabs.
    // This one needs special handling though, as the add-display dropdown
    // relies on the #views-display-menu-tabs #prefix element.
    $access = $viewsRestricted->access($view, $display_id, 'display_tabs');
    if (!$access->isAllowed()) {
      foreach (Element::children($build['tabs']) as &$key) {
        $build['tabs'][$key] = [];
      }
    }
  }

  private static function massageDisplayTopAdd(array &$build, ViewsRestrictedInterface $viewsRestricted, ViewEntityInterface $view, $display_id) {
    // Filter add-display submit buttons.
    $hasLink = FALSE;
    $element = &$build['add_display'];
    foreach (Element::children($element) as $key) {
      $type = "add_display__$key";
      $accessResult = $viewsRestricted->access($view, $display_id, $type);
      if (!$accessResult->isAllowed()) {
        unset($element[$key]);
      }
      else {
        $hasLink = TRUE;
      }
    }
    if (!$hasLink) {
      // Yah, this is a super ugly hack.
      // See Drupal.behaviors.viewsUiRenderAddViewButton in views-admin.js
      $tabsPrefix = &$build['tabs']['#prefix'];
      $tabsPrefix = str_replace(' class="tabs secondary"', ' class="tabs secondary" data-jquery-once-views-ui-render-add-view-button="true"', $tabsPrefix);
    }
  }

  private static function massageDisplayTopExtraActions(array &$build) {
    // Hide main dropdown if no links left.
    $hasLink = FALSE;
    foreach ($build['extra_actions']['#links'] as $item) {
      if (!empty($item['url'])) {
        $hasLink = TRUE;
      }
    }
    if (!$hasLink) {
      $build['extra_actions'] = [];
    }
  }

  private static function massageSettingsTopDisplayName(&$build) {
    $displayTitle =& $build['top']['display_title'];
    if (empty($displayTitle['#link']['#url'])) {
      $displayTitle = [];
    }
  }

  private static function massageSettingsTop(&$top) {
    if (!self::buildHasContent($top)) {
      $top = [];
    }
  }

  private static function buildHasContent($build) {
    foreach ($build as $key => $value) {
      if (substr($key, 0, 1) !== '#') {
        continue;
      }
      if (is_array($value) && $value) {
        return self::buildHasContent($value);
      }
      return isset($value);
    }
    return FALSE;
  }

  private static function massageFormDisplayDropdown(array &$build, ViewsRestrictedInterface $viewsRestricted, ViewEntityInterface $view, $display_id) {
    // Filter display dropdown submit buttons (and "go to page" link).
    $dropdown =& $build['displays']['settings']['settings_content']['tab_content']['details']['top']['actions'];
    if ($dropdown) {
      self::massageDisplayDropdownItem($dropdown, $viewsRestricted, $view, $display_id);
    }
  }

  private static function massageSettingsTopDisplayDropdown(array &$build, ViewsRestrictedInterface $viewsRestricted, ViewEntityInterface $view, $display_id) {
    // On ajax this seems to go here:
    // Filter display dropdown submit buttons (and "go to page" link).
    $dropdown =& $build['top']['actions'];
    if ($dropdown) {
      self::massageDisplayDropdownItem($dropdown, $viewsRestricted, $view, $display_id);
    }
  }

  private static function massageDisplayDropdownItem(array &$build, ViewsRestrictedInterface $viewsRestricted, ViewEntityInterface $view, $display_id, $prefix = 'display__') {
    $hasLink = FALSE;
    foreach (Element::children($build) as $name) {
      $item = &$build[$name];
      if (is_array($item)) {
        if($name === 'duplicate_as') {
          self::massageDisplayDropdownItem($item, $viewsRestricted, $view, $display_id, "{$prefix}{$name}__");
        }
        else {
          $type = "$prefix$name";
          $accessResult = $viewsRestricted->access($view, $display_id, $type);
          if (!$accessResult->isAllowed()) {
            $item = [];
          }
          else {
            $hasLink = TRUE;
          }
        }
      }
    }
    if (!$prefix && !$hasLink) {
      $build = [];
    }
  }

  private static function massageActions(array &$build, ViewsRestrictedInterface $viewsRestricted, ViewEntityInterface $view, $display_id, $prefix = 'display__') {
    // Filter actions submit buttons.
    foreach (Element::children($build) as $key) {
      $type = "actions__$key";
      $accessResult = $viewsRestricted->access($view, $display_id, $type);
      if (!$accessResult->isAllowed()) {
        $build[$key]['#access'] = FALSE;
      }
    }
  }

}
