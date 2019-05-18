<?php

/**
 * @file
 * Contains \Drupal\elfinder\elFinderProfileListBuilder.
 */

namespace Drupal\elfinder\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Defines a class to build a list of elFinder Profile entities.
 *
 * @see \Drupal\elfinder\Entity\elFinderProfile
 */
class elFinderProfileListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $elfidner_profile) {
    $row['label'] = $elfidner_profile->label();
    //$row['description'] = SafeMarkup::checkPlain($elfinder_profile->get('description'));
    return $row + parent::buildRow($elfidner_profile);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $elfinder_profile) {
    $operations = parent::getDefaultOperations($elfinder_profile);
    $operations['duplicate'] = array(
      'title' => t('Duplicate'),
      'weight' => 15,
      'url' => $elfinder_profile->urlInfo('duplicate-form'),
    );
    return $operations;
  }

}
