<?php

namespace Drupal\twig_backlink;

use Drupal\node\Entity\Node;
use Drupal\Core\Url;

/**
 * Twig extension add a new twig_backlink function to build a list of links of
 * the parent entities.
 */
class TwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('twig_backlink', [$this, 'twigBacklink']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'twig_backlink';
  }

  /**
   * Returns the render array of parents nodes.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return null|array
   *   A render array for the field or NULL if the value does not exist.
   */
  public function twigBacklink($field_name) {
    $current_nid = \Drupal::routeMatch()->getParameter('node');
    if(!is_null($current_nid)) {
      $nid = $current_nid->nid->value;
    } else {
      $nid = '';
    }

    $links = [];

    // Get the list of node ids.
    $nids = $this->getNids($nid, $field_name);

    // Get the label from the settings.
    $config = \Drupal::config('twig_backlink.settings');
    $label = $config->get('label');

    if($nids) {
      foreach($nids as $nid) {
        $nodeobj = Node::load($nid);
        $title = $nodeobj->getTitle();

        $options = array('attributes' => array('class' => 'backlink-link'));
        $links['backlink_links'] = [
          '#title' => $title,
          '#type' => 'link',
          '#url' => Url::fromRoute('entity.node.canonical',
            ['node' => $nid], $options)
        ];
      }
    }

    $build = [
      '#theme' => 'item_list',
      '#title' => $label,
      '#items' => $links,
      '#attributes' => ['class' => ['backlink-list']],
    ];

    return $build;
  }

  /**
   * Returns the parent nid of the referenced nodes.
   *
   * @return array|int
   */
  public function getNids($nid, $field_name) {
    $query = \Drupal::service('entity_type.manager')->getStorage('node')->getQuery()
      // Referenced ID
      ->condition($field_name, $nid)
      ->condition('status', 1)
      ->execute();

    return $query;
  }
}
