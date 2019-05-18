<?php

namespace Drupal\box;

use Drupal\box\Entity\BoxType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Box entities.
 *
 * @ingroup box
 */
class BoxListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Box ID');
    $header['machine_name'] = $this->t('Machine name');
    $header['title'] = $this->t('Title');
    $header['type'] = [
      'data' => $this->t('Box type'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\box\Entity\BoxInterface $entity */
    $row['id'] = $entity->id();
    $row['machine_name'] = $entity->machineName();
    $row['title'] = $this->l(
      $entity->label(),
      new Url(
        'entity.box.edit_form', [
          'box' => $entity->id(),
        ]
      )
    );
    $type = BoxType::load($entity->bundle());
    $row['type'] = $type ? $type->label() : FALSE;;
    return $row + parent::buildRow($entity);
  }

}
