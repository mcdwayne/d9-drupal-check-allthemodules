<?php

namespace Drupal\twig_renderable\TwigExtension;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Template\Attribute;
use Twig_Extension;
use Twig_SimpleFilter;

class RenderablesExtension extends Twig_Extension {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs \Drupal\twig_renderable\TwigExtension\RenderablesExtension.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  public function getName() {
    return 'twig_renderable';
  }

  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('will_have_output', [$this, 'willHaveOutput'], [
        'needs_context' => TRUE,
        'is_variadic' => TRUE,
      ]),
    ];
  }

  public function getFilters() {
    return [
      new Twig_SimpleFilter('add_class', [$this, 'addClass']),
      new Twig_SimpleFilter('merge_attributes', [$this, 'mergeAttributes']),
    ];
  }

  public function addClass($renderable, $class) {
    if (is_array($renderable)) {
      $renderable['#attributes']['class'][] = $class;
    }
    else if ($renderable instanceof Attribute) {
      $renderable->addClass($class);
    }

    return $renderable;
  }

  public function mergeAttributes($renderables, $attributes) {
    if ($attributes instanceof Attribute) {
      $attributes = $attributes->toArray();
    }

    if (is_array($renderables)) {
      if (isset($renderables['#attributes'])) {
        $renderables['#attributes'] = array_merge_recursive($renderables['#attributes'], $attributes);
      }
      else {
        $renderables['#attributes'] = $attributes;
      }
    }
    else if ($renderables instanceof Attribute) {
      if (isset($attributes['class'])) {
        $renderables->addClass($attributes['class']);
        unset($attributes['class']);
      }
      foreach ($attributes as $name => $value) {
        $renderables->setAttribute($name, $value);
      }
    }

    return $renderables;
  }

  public function willHaveOutput(array &$context, $variable, array $parents = []) {
    array_unshift($parents, $variable);
    $key_exists = NULL;
    $element = &NestedArray::getValue($context, $parents, $key_exists);

    if (is_array($element)) {
      $output = $this->renderer->render($element);
      $element = [
        '#markup' => $output,
      ];

      if (\Drupal::service('twig')->isDebug()) {
        $output = preg_replace('/<!--(.*)-->/Uis', '', $output);
      }

      return trim($output) != '';
    }
    else {
      return trim($element) != '';
    }
  }
}
