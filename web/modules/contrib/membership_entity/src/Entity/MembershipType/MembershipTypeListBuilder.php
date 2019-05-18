<?php
declare(strict_types=1);

namespace Drupal\membership_entity\Entity\MembershipType;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of Membership type entities.
 */
class MembershipTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Type');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->t('@label (@id)', [
      '@label' => $entity->label(),
      '@id' => $entity->id(),
    ]);
    $row['description'] = $entity->description();
    return $row + parent::buildRow($entity);
  }

  /**
   * @inheritDoc
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There are no Membership types yet. <a href=":link">Add a new membership type.</a>', [
      ':link' => Url::fromRoute('entity.membership_entity_type.add_form')->toString(),
    ]);
    return $build;
  }

  /**
   * @inheritDoc
   */
  protected function getTitle() {
    return $this->t('Membership types');
  }
}
