<?php

namespace Drupal\drd\Entity\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Domain entities.
 *
 * @ingroup drd
 */
class Domain extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Domain ID');
    $header['name'] = $this->t('Name');
    $header['config'] = $this->t('Config Link');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $domain) {
    /* @var \Drupal\drd\Entity\DomainInterface $domain */
    $row['id'] = $domain->id();
    $row['name'] = Link::fromTextAndUrl(
      $domain->label(),
      new Url(
        'entity.drd_domain.edit_form', [
          'drd_domain' => $domain->id(),
        ]
      )
    );
    if ($domain->isInstalled()) {
      $row['config'] = $domain->getRemoteSetupLink($this->t('Configure'));
    }
    else {
      $row['config'] = '';
    }
    return $row + parent::buildRow($domain);
  }

}
