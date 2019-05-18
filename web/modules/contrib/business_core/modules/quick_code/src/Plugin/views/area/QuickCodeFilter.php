<?php

namespace Drupal\quick_code\Plugin\views\area;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\area\AreaPluginBase;

/**
 * Views area handlers. Insert a quick_code filter inside of an area.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("quick_code_filter")
 */
class QuickCodeFilter extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['quick_code_type'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $entities = \Drupal::entityTypeManager()->getStorage('quick_code_type')
      ->loadMultiple();
    $options = array_map(function ($entity) {
      return $entity->label();
    }, $entities);
    $form['quick_code_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Quick code type'),
      '#default_value' => $this->options['quick_code_type'],
      '#description' => $this->t('The quick code type.'),
      '#options' => $options,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    $type_id = $this->options['quick_code_type'];
    /** @var \Drupal\quick_code\QuickCodeTypeInterface $quick_code_type */
    $type = \Drupal::entityTypeManager()
      ->getStorage('quick_code_type')
      ->load($type_id);

    // TODO: check access
    $add = [
      '#type' => 'link',
      '#title' => $this->t('Add %type', ['%type' => $type->label()]),
      '#url' => Url::fromRoute('entity.quick_code.add_form', ['quick_code_type' => $type_id], ['query' => \Drupal::destination()->getAsArray()]),
      '#attributes' => [
        'class' => ['button', 'button-action', 'button--small'],
      ],
    ];

    if ($type->getHierarchy()) {
      $filter = $this->buildTree();
    }
    else {
      $filter = $this->buildList();
    }

    $build = [
      'add' => $add,
      'filter' => $filter,
      '#prefix' => '<div class="quick-code-filter">',
      '#suffix' => '</div>',
    ];
    $build['#attached']['library'][] = 'quick_code/quick_code_filter';

    return $build;
  }

  public function buildTree() {
    $build =  [
      '#theme' => 'quick_code_tree',
      '#items' => $this->buildChildren(),
      '#attributes' => new Attribute(['class' => ['quick-code-tree']]),
    ];

    return $build;
  }

  public function buildChildren($parent = NULL) {
    $entity_type_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\quick_code\QuickCodeStorageInterface $quick_code_storage */
    $quick_code_storage = $entity_type_manager->getStorage('quick_code');

    $quick_code_type_id = $this->options['quick_code_type'];
    /** @var \Drupal\quick_code\QuickCodeTypeInterface $quick_code_type */
    $quick_code_type = $entity_type_manager->getStorage('quick_code_type')
      ->load($quick_code_type_id);

    $query = $quick_code_storage->getQuery();
    $query->condition('type', $quick_code_type_id);
    if ($parent) {
      $query->condition('parent', $parent);
    }
    else {
      $query->condition('parent', NULL, 'IS NULL');
    }
    if ($quick_code_type->getCode()) {
      $query->sort('code');
    }
    else {
      $query->sort('label');
    }
    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    $parent_ids = [];
    if ($this->view->args) {
      $parents = $quick_code_storage->loadParents($this->view->args[0]);
      $parent_ids = array_keys($parents);
    }

    $entities = $entity_type_manager->getStorage('quick_code')
      ->loadMultiple($ids);
    $items = [];
    foreach ($entities as $entity) {
      $item = [
        'title' => $entity->label(),
        'url' => $this->view->getUrl([$entity->id()]),
      ];
      if (!empty($below = $this->buildChildren($entity->id()))) {
        $item['below'] = $below;

        if (in_array($entity->id(), $parent_ids)) {
          $item['attributes'] = new Attribute(['class' => 'expanded']);
        }
      }

      $items[$entity->id()] = $item;
    }

    return $items;
  }

  public function buildList() {
    // TODO
    return [];
  }

}
