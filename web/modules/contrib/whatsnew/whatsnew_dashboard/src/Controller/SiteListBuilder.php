<?php

namespace Drupal\whatsnew_dashboard\Controller;

use Drupal\whatsnew_dashboard\Entity\Site;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of sites.
 */
class SiteListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header['site_url'] = $this->t('URL');
    $header['site_key'] = $this->t('Key');
    return $header + parent::buildHeader();

  }

  /**
   * {@inheritdoc}
   *
   * SiteListBuilder save implementation requires instance of Site.
   * Signature enforced by EntityListBuilder.
   *
   * @throw InvalidArgumentException.
   */
  public function buildRow(EntityInterface $entity) {

    if (!$entity instanceof Site) {
      throw new Exception();
    }

    $row['site_url'] = $entity->get('site_url');
    $row['site_key'] = $entity->get('site_key');
    return $row + parent::buildRow($entity);

  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    $build = parent::render();
    $build['#empty'] = $this->t('There are no sites being monitored.');
    return $build;
  }

}
