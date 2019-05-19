<?php

/**
 * @file
 * Contains \Drupal\tve\src\Twig\TVEExtension.
 */
namespace Drupal\tve\Twig;

class TVEExtension extends \Twig_Extension {

  /**
   * Returns the name of the extension.
   *
   * @return string The extension name
   */
  public function getName() {
    return 'tve';
  }

  /**
   * {@inheritdoc}
  */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('views_embed_view', array($this, 'tve')),
    ];
  }

  public function tve($viewName, $viewId = 'default') {

    $output = '';

    if (func_num_args() === 2) {
      // No arguments passed to tve()
      $output = views_embed_view($viewName, $viewId);
    }
    else {
      $args = array_slice(func_get_args(), 2);
      $output = views_embed_view($viewName, $viewId, implode(',', $args));
    }

    return $output;
  }

}
