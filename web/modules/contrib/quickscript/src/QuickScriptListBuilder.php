<?php

/**
 * @file
 * Contains \Drupal\quickscript\QuickScriptListBuilder.
 */

namespace Drupal\quickscript;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Quick Script entities.
 *
 * @ingroup quickscript
 */
class QuickScriptListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort('last_run', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['machine_name'] = $this->t('Machine Name');
    $header['last_run'] = $this->t('Last Run');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\quickscript\Entity\QuickScript */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.quickscript.edit_form', array(
          'quickscript' => $entity->id(),
        )
      )
    );
    $row['machine_name'] = $entity->machine_name->value;
    if ($entity->last_run->value) {
      $row['last_run'] = \Drupal::service('date.formatter')->formatTimeDiffSince($entity->last_run->value) . ' ago';
    }
    else {
      $row['last_run'] = 'Never';
    }
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $operations['run'] = [
      'title' => $this->t('Run'),
      'weight' => 0,
      'url' => $entity->urlInfo('execute'),
    ];
    return $operations;
  }

}
