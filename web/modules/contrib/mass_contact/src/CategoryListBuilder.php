<?php

namespace Drupal\mass_contact;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\mass_contact\Entity\MassContactCategoryInterface;

/**
 * Provides a listing of Mass contact category entities.
 */
class CategoryListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Mass contact category');
    $header['id'] = $this->t('Machine name');
    $header['categories'] = $this->t('Categories');
    $header['selected'] = $this->t('Selected by default');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['categories'] = $this->getCategories($entity);
    $row['selected'] = $entity->getSelected() ? '✔' : '';
    return $row + parent::buildRow($entity);
  }

  /**
   * Generate the categories column value.
   *
   * @param \Drupal\mass_contact\Entity\MassContactCategoryInterface $category
   *   The category entity.
   */
  protected function getCategories(MassContactCategoryInterface $category) {
    $categories = [];
    foreach ($category->getGroupings() as $plugin) {
      $categories[] = $plugin->displayCategories($plugin->getCategories());
    }
    return new FormattableMarkup(implode('<br />', $categories), []);
  }

}
