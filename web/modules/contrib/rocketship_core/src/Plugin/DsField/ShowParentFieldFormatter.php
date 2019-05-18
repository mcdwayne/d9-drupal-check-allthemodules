<?php

namespace Drupal\rocketship_core\Plugin\DsField;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * DSFIELD.
 *
 * Plugin that renders a field from the entity the paragraph is attached to
 * as though it was part of the paragraph.
 *
 * @DsField(
 *   id = "show_parent_field",
 *   title = @Translation("Show parent field"),
 *   entity_type = "paragraph",
 *   provider = "rocketship_core"
 * )
 */
class ShowParentFieldFormatter extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'parent_field' => '',
      'parent_view_mode' => 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary($settings) {
    $config = $this->getConfiguration();

    $summary = [];
    if (!empty($config['parent_field'])) {
      $summary[] = 'Showing parent field: ' . $config['parent_field'];
    }
    if (!empty($config['parent_view_mode'])) {
      $summary[] = 'Using parent view mode: ' . $config['parent_view_mode'];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $settings['parent_field'] = [
      '#title' => t('Parent field'),
      '#type' => 'textfield',
      '#default_value' => $config['parent_field'],
      '#required' => TRUE,
    ];

    $settings['parent_view_mode'] = [
      '#title' => t('Parent view mode'),
      '#type' => 'textfield',
      '#default_value' => $config['parent_view_mode'],
      '#required' => TRUE,
    ];

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->entity();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $parent */
    $parent = $this->getHighestLevelParentEntity($entity);

    $build = [];
    $cache_tags = $entity->getCacheTags();
    $cache_tags = Cache::mergeTags($cache_tags, $parent->getCacheTags());

    $config = $this->getConfiguration();

    $field = $config['parent_field'];
    $view_mode = $config['parent_view_mode'];

    if ($parent->hasField($field)) {
      $build = $parent->get($field)->view($view_mode);
    }

    if (!isset($build['#cache']['tags'])) {
      $build['#cache']['tags'] = [];
    }

    $build['#cache']['tags'] = Cache::mergeTags($build['#cache']['tags'], $cache_tags);

    return $build;
  }

  /**
   * Get highest parent.
   *
   * Recursively fetches the parent entity until top is reached and then
   * returns that one.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Parent.
   */
  protected function getHighestLevelParentEntity(EntityInterface $entity) {
    if (method_exists($entity, 'getParentEntity')) {
      $parent = $entity->getParentEntity();
      if ($parent) {
        return $this->getHighestLevelParentEntity($parent);
      }

      // Empty parent, assume this level is fine.
      return $entity;
    }

    // Already highest level as far as we can tell.
    return $entity;
  }

}
