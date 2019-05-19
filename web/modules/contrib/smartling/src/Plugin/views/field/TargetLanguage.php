<?php

/**
 * @file
 * Contains \Drupal\content_translation\Plugin\views\field\TranslationLink.
 */

namespace Drupal\smartling\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a translation link for an entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("smartling_submission_target_language")
 */
class TargetLanguage extends FieldPluginBase {

  protected $entityStorage;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, $entityStorage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityStorage = $entityStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('smartling_submission')
    );
  }

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Target language');
  }


  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $this->getEntity($values);
    $id = $entity->id();
    $enity_type = $entity->getEntityType()->id();

    $submissions = $this->entityStorage->loadByProperties([
      'entity_type' => $enity_type,
      'entity_id' => $id,
    ]);
    $locales = [];
    foreach ($submissions as $submission) {
      $locales[] = $submission->get('target_language')->value;
    }

    $locales = implode(', ', $locales);
    return $this->sanitizeValue($locales);
  }


}
