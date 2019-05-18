<?php
namespace Drupal\custom_entity_pager\TwigExtensions;

/**
 * Class PaginatorExtension.
 */
class PaginatorExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'custom_entity_pager.paginator_extension';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('custom_entity_pager_insert', [$this, 'custom_entity_pager_insert']),
    ];
  }

  /**
   * @param string $content_type
   *   name of the content type to build the pager.
   * @param string $field_order
   *   the field by do the order.
   * @param bool $title
   *   show titles.
   * @param string $inner_text
   *   paging text.
   */
  public static function custom_entity_pager_insert($content_type, $field_order = '', $title = TRUE, $inner_text = NULL) {

    $current_nid = NULL;
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof \Drupal\node\NodeInterface) {
      $current_nid = $node->id();
    }

    if (!empty($current_nid)) {

      $elements = \Drupal::service('custom_entity_pager.main_service')->getPaginator($content_type, $current_nid, $field_order);
      $elements['inner_text'] = $inner_text;
      $template = 'paginate';

      if ($title) {
        $template = 'paginate_with_titles';
      }
      return [
        '#theme' => $template,
        '#element' => $elements,
      ];
    }

    return FALSE;
  }

}
