<?php

namespace Drupal\block_placeholder\TwigExtension;

use Drupal\Component\Render\MarkupInterface;

/**
 * Define block placeholder twig extensions.
 */
class BlockPlaceholderTwig extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'block_placeholder.twig_extension';
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('remove_html_comments', [$this, 'removeHtmlComments'])
    ];
  }

  /**
   * Remove html comments.
   *
   * @param $string
   *   The string on
   *
   * @return null|string
   */
  public function removeHtmlComments($string) {
    if ($string instanceof MarkupInterface) {
      $string = $string->__toString();
    }
    $output = preg_replace('/<!--(.|\s)*?-->/', '', $string);

    return trim($output);
  }
}
