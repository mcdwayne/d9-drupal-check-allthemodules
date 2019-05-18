<?php

namespace Drupal\file_utility\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for content_entity_example_contact entity.
 *
 * @ingroup file_utility
 */
class FileUtilityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('File Utility Entity implements a File Utility model. These are fieldable entities. You can manage the fields on the <a href="@adminlink">File Utility admin page</a>.', [
        '@adminlink' => \Drupal::urlGenerator()
          ->generateFromRoute('content_entity_file_utility.file_utility_settings'),
      ]),
    ];

    $build += parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Id');
    $header['name'] = $this->t('Name');
    $header['email'] = $this->t('Email');
    $header['ip_address'] = $this->t('IP Address');
    $header['count'] = $this->t('Count');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\file_utility\Entity\Contact */
    $row['id'] = $entity->id();
    $row['name'] = $entity->link();
    $row['email'] = $entity->email->value;
    $row['ip_address'] = $entity->ip_address->value;
    $row['count'] = $entity->count->value;
    return $row + parent::buildRow($entity);
  }

}
