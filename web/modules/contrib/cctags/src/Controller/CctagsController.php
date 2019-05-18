<?php

namespace Drupal\cctags\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds an static callback page.
 */
class CctagsController extends ControllerBase {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * Call back for route static_content.
   */
  public function content($cctid, $page_amount, $page_mode, $page_extra_class, $page_vocname) {
    $pager = NULL;
    $extra_class = ($page_extra_class)?' '. $page_extra_class:'';
    $terms = cctags_get_level_tags($cctid, 'page');
    $amount = $page_amount;
    $page1 = \Drupal::request()->query->get('page', 0);
    $count_terms = 0;
    foreach ($terms as $k => $v) {
      $count_terms += count($v)-3;
    }

    $page = (!isset($page1) || $amount==0 || $count_terms < $amount)? 0 : $page1;
    $mode = 'full';
    $content = [
      '#theme' => 'cctags_level',
      '#terms' => $terms,
      '#amount' => $amount,
      '#page' => $page,
      '#mode' => $mode,
      '#vocname' => $page_vocname,
      '#out' => 'page',
    ];

    $items = _cctags_get_settings($cctid);
    $item = $items[$cctid];
    $taxonomy_terms = [];
    $terms = [];

    foreach ($item['item_data'] as $key => $value) {
      // Vocabulary is checked.
      if ($value['cctags_select_' . $key]) {
        $vocabulary = $this->entityManager->getStorage('taxonomy_term')->loadTree($key);
        unset($value['cctags_select_' . $key]);
        $levels_checked = [];
        for($i=0; $i<count($value); $i++) {
          if($value['level_' . $i]) {
            $levels_checked[] = $i;
          }
        }
        foreach($vocabulary as $term) {
          if (in_array($term->depth, $levels_checked )) {
            $terms[$key][$term->depth]['link'][] = Link::fromTextAndUrl($term->name, Url::fromUri('base:/taxonomy/term/' . $term->tid));
            $taxonomy_terms[] = Link::fromTextAndUrl($term->name, Url::fromUri('base:/taxonomy/term/' . $term->tid))->toString();
          }
        }
      }
    }

    return array(
      '#theme' => 'cctags_page',
      '#extra_class' => $extra_class,
      '#content' => $content,
      '#taxonomy_terms' => $taxonomy_terms,
      '#pager' => $pager,
    );

  }
}
