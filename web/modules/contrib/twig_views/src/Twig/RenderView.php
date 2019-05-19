<?php

namespace Drupal\twig_views\Twig;

use Drupal\views\Views;

/**
 * Adds extension to render a view.
 *
 * @package Drupal\twig_views\Twig.
 */
class RenderView extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'render_view';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction(
        'render_view',
        [$this, 'renderViewWithTitle'],
        ['is_safe' => ['html']]
      ),
    ];
  }

  /**
   * Provides function to programmatically rendering a view with title.
   *
   * @param string $view
   *   The machine name of view to render.
   * @param string $display
   *   The machine name of display of view to render.
   * @param ...
   *   Any additional parameters will be passed as arguments.
   *
   * @return array|null
   *   The rendered element.
   */
  public static function renderViewWithTitle($view, $display = NULL) {
    // Get function passed arguments.
    $args = func_get_args();
    // Remove $view and $display from the arguments.
    unset($args[0], $args[1]);
    // Throw exception if the display doesn't set.
    if (!isset($display) && empty($display)) {
      throw new \InvalidArgumentException(sprintf('You need to specify the view display.'));
    }
    // Get the view machine id.
    $view = Views::getView($view);
    // Set the display machine id.
    if (!$view->setDisplay($display)) {
      throw new \InvalidArgumentException(sprintf('Invalid display ID %s.', $display));
    }
    // Set View arguments.
    if (is_array($args)) {
      $view->setArguments($args);
    }
    // Get the title.
    $title = $view->getTitle();
    // Get Render.
    $render = $view->render();
    // Prepare Title Render array.
    $the_title_render_array = [
      '#markup'       => t('@title', ['@title' => $title]),
      '#prefix'       => '<h2>',
      '#suffix'       => '</h2>',
      '#allowed_tags' => ['h2'],
    ];
    // View Content.
    $view_content = [
      'view_title'  => $the_title_render_array,
      'view_output' => $render,
    ];
    // Return the view render.
    return render($view_content);
  }

}
