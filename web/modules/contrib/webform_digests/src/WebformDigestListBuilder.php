<?php

namespace Drupal\webform_digests;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\Entity\Webform;

/**
 * Provides a listing of Webform digest entities.
 */
class WebformDigestListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Webform digest');
    $header['id'] = $this->t('Machine name');
    $header['Webform'] = $this->t('webform');
    $header['conditional'] = $this->t('Conditional');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['webform'] = Webform::load($entity->getWebform())->label();
    $row['conditional'] = $entity->isConditional() ? t("Conditional") : t("All submissions");
    return $row + parent::buildRow($entity);
  }

}
