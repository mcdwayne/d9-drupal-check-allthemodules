<?php

namespace Drupal\quick_code_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Provides a block to display quick_code tree.
 *
 * @Block(
 *   id = "quick_code_tree_block",
 *   admin_label = @Translation("Quick code tree")
 * )
 */
class TreeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'quick_code_type' => '',
      'url' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;

    $quick_code_types = \Drupal::entityTypeManager()
      ->getStorage('quick_code_type')
      ->loadMultiple();
    $options = array_map(function ($entity) {
      return $entity->label();
    }, $quick_code_types);
    $form['quick_code_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Quick code type'),
      '#options' => $options,
      '#default_value' => $config['quick_code_type'],
    ];

    $token_tree = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['quick_code'],
    ];
    $rendered_token_tree = \Drupal::service('renderer')->render($token_tree);
    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link path'),
      '#description' => $this->t('This field supports tokens. @browse_tokens_link', ['@browse_tokens_link' => $rendered_token_tree]),
      '#default_value' => $config['url'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['quick_code_type'] = $form_state->getValue('quick_code_type');
    $this->configuration['url'] = $form_state->getValue('url');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#theme' => 'quick_code_tree',
      '#items' => $this->buildChildren(),
      '#attributes' => ['class' => ['quick-code-tree']],
      '#attached' => ['library' => ['quick_code_block/tree']],
    ];

    return $build;
  }

  protected function buildChildren($parent = NULL) {
    $entity_type_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\quick_code\QuickCodeStorageInterface $quick_code_storage */
    $quick_code_storage = $entity_type_manager->getStorage('quick_code');

    $quick_code_type_id = $this->configuration['quick_code_type'];
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
    $current_id = \Drupal::routeMatch()->getParameter($quick_code_type->id());
    if (!$current_id) {
      $current_id = \Drupal::routeMatch()->getParameter('field_' . $quick_code_type->id() . '_target_id');
    }
    if ($current_id) {
      $parents = $quick_code_storage->loadParents($current_id);
      $parent_ids = array_keys($parents);
    }

    $entities = $entity_type_manager->getStorage('quick_code')
      ->loadMultiple($ids);
    $items = [];
    foreach ($entities as $entity) {
      $url = $this->configuration['url'];
      if (!empty($url)) {
        $url = Url::fromUserInput(\Drupal::token()->replace($url, ['quick_code' => $entity]));
      }
      else {
        $url = $entity->toUrl();
      }
      $item = [
        'title' => $entity->label(),
        'url' => $url,
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

}
