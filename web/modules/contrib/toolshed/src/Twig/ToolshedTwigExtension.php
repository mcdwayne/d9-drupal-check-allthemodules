<?php

namespace Drupal\toolshed\Twig;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\Element;
use Drupal\views\Views;
use Drupal\views\ViewExecutable;

/**
 * Add empty checks and render functions for Twig.
 *
 * Adds a cleaner implementation in Twig for:
 *   + Checking for empty, with checks for Twig debug comments.
 *   + Is a view empty (has no results).
 *   + Render if a specified child item is not empty.
 */
class ToolshedTwigExtension extends \Twig_Extension {

  /**
   * The Twig environment service.
   *
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

  /**
   * Drupal active renderer and render context.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Current Twig configurations.
   *
   * @var array
   */
  protected $twigOpts;

  /**
   * Create a new instance of the twig extensions for adding Toolshed utilities.
   *
   * @param \Drupal\Core\Template\TwigEnvironment $twig
   *   The Twig environment and setttings.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Drupal renderer services. Transforms Drupal render arrays and objects
   *   into HTML.
   * @param array $twig_options
   *   Twig options passed to the twig environment.
   */
  public function __construct(TwigEnvironment $twig, RendererInterface $renderer, array $twig_options) {
    $this->twig = $twig;
    $this->renderer = $renderer;
    $this->twigOpts = $twig_options;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'toolshed.twig_utils';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    $funcs = [];

    return $funcs;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    $fitlers[] = new \Twig_SimpleFilter('is_element_empty', [$this, 'isElementEmpty']);
    $filters[] = new \Twig_SimpleFilter('is_view_empty', [$this, 'isViewEmpty']);

    return $filters;
  }

  /**
   * Determine if an element is empty in terms of rendered content.
   *
   * @param mixed $element
   *   Element to examine to check for empty value.
   *
   * @return bool
   *   If element can be considered empty or not. TRUE implies that
   *   the content is considered empty.
   */
  public function isElementEmpty(array $element) {
    ksm($this->twig);

    $type = !empty($element['#type']) ? $element['#type'] : @$element['#theme'];

    switch ($type) {
      case 'container':
      case 'form_element':
      case 'fieldset':
      case 'details':
      case 'field':
        foreach (Element::getVisibleChildren($element) as $child) {
          if (!$this.isElementEmpty($element[$child])) {
            return FALSE;
          }
        }
        return TRUE;

      case 'link':
      case 'form':
      case 'select':
      case 'textfield':
      case 'textarea':
      case 'radios':
      case 'checkbox':
      case 'checkboxes':
      case 'submit':
      case 'button':
        return FALSE;

      case 'links':
        return empty($element['#links']) && empty($element['#header']);

      case 'item_list':
        return empty($element['#items']);

      case 'view':
        return $this->isViewEmpty($element);

      case 'block':
        return $this->isBlockEmpty($element);

      case 'processed_text':
        // TODO: Also check if text only consists of white-space tags
        // such as "<p>", "<br>", and "&nbsp;".
        return empty($element['#text']);

      default:
        foreach (['#markup', '#plain_text'] as $idx) {
          if (isset($element[$idx])) {
            $textVal = $element[$idx] instanceof MarkupInterface ? $element[$idx].toString() : $element[$idx];
            return empty($textVal);
          }
        }

        // If we are not able to determine this information easily, we fallback
        // to the hammer approach of rendering, and checking for empty.
        // TODO: Chip away at this approach for better tests for empty content.
        $str = $this->renderer->render($element);
    }

    /*
    // If the Twig debug flag is enabled, there are comments that need to be
    // removed from the outpute before checking for empty.
    if (!empty($isDebug)) {
    // TODO: strip comments.
    }
     */

    return FALSE;
  }

  /**
   * Determine if a view should be considered empty (has no results).
   *
   * @param mixed $view
   *   Either a ViewExecutable object, or an array or string that can be used
   *   to determine and load the correct views object.
   *
   * @return bool
   *   TRUE if the view query is returning empty results. This filter ignores
   *   if the view is configure to return an empty message or still expected
   *   to render even if empty.
   *
   *   TODO: Add check for empty behavior configured for view display.
   */
  public function isViewEmpty($view) {
    if (empty($view)) {
      return TRUE;
    }

    if (!$view instanceof ViewExecutable) {
      if (is_string($view)) {
        list($viewId, $displayId) = explode(':', $view);
        $args = func_get_args();
        array_shift($args);
      }
      elseif (is_array($view)) {
        if (isset($view['#type']) && $view['#type'] === 'view') {
          $viewId = $view['#name'];
          $displayId = $view['#display_id'];
          $args = !empty($view['#arguments']) ? $view['#arguments'] : [];
        }
        else {
          list($viewId, $displayId) = $view;
          $args = func_get_args();
          array_shift($args);
        }
      }

      // If either of these are missing, skip and consider the view empty.
      if (empty($viewId) || empty($displayId)) {
        return TRUE;
      }

      // Try to load and set the view information.
      $view = Views::getView($viewId);
      if (!$view || !$view->setDisplay($displayId)) {
        return TRUE;
      }

      if (!empty($args)) {
        $view->setArguments($args);
      }
    }

    // Execute the view and check if there are any results. No results
    // means that the view is empty.
    return !$view->execute() || empty($view->result);
  }

  /**
   * Render if child is considered not empty.
   *
   * @param array $element
   *   Render array to render if the child is not empty.
   * @param array $parents
   *   Array of parent keys to the child relative to $element.
   *
   * @return array
   *   Render array to render. Will be the value of element if the child is
   *   not empty, but an empty array (render nothing) if child is determined
   *   to be empty. Empty is determined by static::isElementEmpty().
   */
  public function renderIfChild(array $element, array $parents) {
    throw new \NotImplementedException('ToolshedTwigExtension::renderIfChild() is not implemented yet.');
    return NULL;
  }

}
