<?php

namespace Drupal\twig_render_this\TwigExtension;

use Drupal\Core\Entity\EntityInterface;

/**
 * Twig Render This filter.
 */
class RenderThis extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'twig_render_this.twig_extension';
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('renderThis', [$this, 'renderThisFilter']),
    ];
  }

  /**
   * Returns the rendered array for a single entity field.
   *
   * @param object $content
   *   Entity or Field object.
   * @param string $view_mode
   *   Name of the display mode.
   *
   * @return null|array
   *   A rendered array for the field or NULL if the value does not exist.
   */
  public static function renderThisFilter($content, $view_mode = 'default') {
    if ($content instanceof EntityInterface) {
      $view_builder = \Drupal::entityTypeManager()
        ->getViewBuilder($content->getEntityTypeId());
      return $view_builder->view($content, $view_mode);
    }
    elseif ($content instanceof FieldItemInterface ||
      $content instanceof FieldItemListInterface ||
      method_exists($content, 'view')
    ) {
      return $content->view($view_mode);
    }
    else {
      return t('Twig Render This: Unsupported content.');
    }
  }

}
