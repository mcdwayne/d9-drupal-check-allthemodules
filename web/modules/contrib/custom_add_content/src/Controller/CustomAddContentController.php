<?php

namespace Drupal\custom_add_content\Controller;

use Drupal\node\Controller\NodeController;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Returns responses for Node routes.
 */
class CustomAddContentController extends NodeController implements ContainerInjectionInterface {

  /**
   * Displays add content links for available content types.
   *
   * Redirects to node/add/[type] if only one content type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the node types that can be added; however,
   *   if there is only one node type defined for the site, the function
   *   will return a RedirectResponse to the node add page for that one node
   *   type.
   */
  public function addPage() {
    $level = 4;
    $menu_name = 'custom-add-content-page';

    $renderer = \Drupal::config('custom_add_content.config')->get('custom_add_content_renderer');

    $menu_tree = \Drupal::menuTree();
    $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
    $parameters->setMaxDepth($level + 1);
    $tree = $menu_tree->load($menu_name, $parameters);

    if (is_array($tree) && count($tree) > 0) {

      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ];
      $tree = $menu_tree->transform($tree, $manipulators);
      $menu = $menu_tree->build($tree);

      if ($renderer == 0) {
        $markup = \Drupal::service('renderer')->render($menu);
      }
      else {
        // Call to custom twig template.
        return [
          '#theme' => 'custom_add_content_page_add',
          '#menu_name' => $menu['#menu_name'],
          '#items' => $menu['#items'],
          '#attached' => [
            'library' => ['custom_add_content/custom_add_content'],
          ],
        ];
      }
    }
    else {
      $markup = '<p>' . $this->t('Please, make sure custom_add_content_page menu has links.') . '</p>';
    }

    $build = [
      '#type' => 'markup',
      '#markup' => $markup,
    ];

    return $build;
  }

}
