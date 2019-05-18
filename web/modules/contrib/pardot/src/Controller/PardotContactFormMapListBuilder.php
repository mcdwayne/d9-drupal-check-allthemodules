<?php

namespace Drupal\pardot\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Pardot Contact Form Map entities.
 *
 * @package Drupal\pardot\Controller
 *
 * @ingroup pardot
 */
class PardotContactFormMapListBuilder extends ConfigEntityListBuilder {

  /**
   * Builds the header row for the entity listing.
   *
   * @return array
   *   A render array structure of header strings.
   */
  public function buildHeader() {
    $header['label'] = $this->t('Map Label');
    $header['contact_form_id'] = $this->t('Contact Form ID');
    return $header + parent::buildHeader();
  }

  /**
   * Builds a row for an entity in the entity listing.
   *
   * @param EntityInterface $entity
   *   The entity for which to build the row.
   *
   * @return array
   *   A render array of the table row for displaying the entity.
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['contact_form_id'] = $entity->contact_form_id;

    return $row + parent::buildRow($entity);
  }

  /**
   * Adds introduction to Pardot Campaign list.
   *
   * @return array
   *   Renderable array.
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t("<p>P.ardot Contact Form Mappings</p>"),
    );
    $build[] = parent::render();
    return $build;
  }

}
