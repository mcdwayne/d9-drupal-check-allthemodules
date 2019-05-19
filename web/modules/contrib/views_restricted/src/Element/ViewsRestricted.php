<?php

namespace Drupal\views_restricted\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\views\Entity\View;
use Drupal\views_restricted\ViewsRestrictedHelper;
use Drupal\views_ui\ViewUI;

/**
 * Provides a render element to display a restricted views UI.
 *
 * Properties:
 * - #view: The view ID, or the view.
 * - #display: The view display name.
 * - #views_restricted: The views_restricted plugin ID.
 *
 * Usage Example:
 * @code
 * $build['view'] = [
 *   '#view' => 'content',
 *   '#display' => 'page',
 *   '#views_restricted' => 'views_restricted_simple',
 * ];
 * @endcode
 *
 * @RenderElement("views_restricted")
 */
class ViewsRestricted extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        [get_class($this), 'preRenderViewsRestrictedElement'],
      ],
      '#view' => NULL,
      '#display' => 'default',
      '#views_restricted' => 'views_restricted_legacy',
    ];
  }

  /**
   * Views restricted element pre render callback.
   *
   * @param array $element
   *   An associative array containing the properties of the entity element.
   *
   * @return array
   *   The modified element.
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function preRenderViewsRestrictedElement(array $element) {
    /** @var \Drupal\Core\Entity\EntityFormBuilderInterface $entityFormBuilder */
    $entityFormBuilder = \Drupal::service('entity.form_builder');
    /** @var \Drupal\views_restricted\ViewsRestrictedPluginManager $viewsRestrictedPluginManager */
    $viewsRestrictedPluginManager = \Drupal::service('plugin.manager.views_restricted');

    $view = $element['#view'];
    if (!is_object($view)) {
      $view = View::load($view);
    }
    $display_id = $element['#display'];
    $views_restricted_id = $element['#views_restricted'];
    /** @var \Drupal\views_restricted\ViewsRestrictedInterface $viewsRestricted */
    $viewsRestricted = $viewsRestrictedPluginManager->createInstance($views_restricted_id);
    $viewUI = new ViewUI($view);
    ViewsRestrictedHelper::setViewsRestrictedId($viewUI, $views_restricted_id);

    $element['view'] = $entityFormBuilder->getForm($viewUI, 'edit', ['display_id' => $display_id]);
    $element['view']['#access'] = $viewsRestricted->access($view, $display_id);
    return $element;
  }

}
