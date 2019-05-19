<?php

namespace Drupal\webform_composite\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Reusable Webform Composites entities.
 */
class WebformCompositeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'webform_reusable_composite';
  }

  /**
   * Builds the header row for the entity listing.
   *
   * @return array
   *   A render array structure of header strings.
   *
   * @see \Drupal\Core\Entity\EntityListController::render()
   */
  public function buildHeader() {
    $header['label'] = $this->t('Composite');
    $header['machine_name'] = $this->t('Machine Name');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * Builds a row for an composite in the listing.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to build the row.
   *
   * @return array
   *   A render array of the table row for displaying the entity.
   *
   * @see \Drupal\Core\Entity\EntityListController::render()
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['machine_name'] = $entity->id();
    $row['description'] = $entity->getDescription();
    return $row + parent::buildRow($entity);
  }

  /**
   * Adds some descriptive text to our entity list.
   *
   * Typically, there's no need to override render(). You may wish to do so,
   * however, if you want to add markup before or after the table.
   *
   * @return array
   *   Renderable array.
   */
  public function render() {
    $build = [
      'description' => [
        '#type' => 'item',
        '#markup' => $this->t("Configure Reusable Composite elements."),
      ],
    ];
    return $build + parent::render();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    // Add an operation for viewing source of composites.
    $operations = parent::getDefaultOperations($entity);
    if ($entity->access('update') && $entity->hasLinkTemplate('source-form')) {
      $operations['source'] = array(
        'title' => $this->t('Source'),
        'weight' => 10,
        'url' => $entity->urlInfo('source-form'),
      );
    }
    return $operations;
  }

}
