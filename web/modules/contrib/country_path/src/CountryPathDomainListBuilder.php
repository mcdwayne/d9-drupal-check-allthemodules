<?php

namespace Drupal\country_path;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\domain\DomainListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Override Domain entities list to show there Country path values.
 */
class CountryPathDomainListBuilder extends DomainListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Call parent's buildRow method.
    $row = parent::buildRow($entity);

    $options = ['absolute' => TRUE, 'https' => $entity->isHttps()];
    $domain_suffix = $entity->getThirdPartySetting('country_path', 'domain_path');
    $domain_path = $entity->getPath();
    $current_path = \Drupal::service('path.current')->getPath();

    if (empty($domain_suffix)) {
      $uri = $domain_path . ltrim($current_path, '/');
    }
    else {
      $uri = $domain_path . $domain_suffix . $current_path;
      $domain_suffix = "/$domain_suffix";
    }

    $hostname = Link::fromTextAndUrl(
      $entity->getCanonical() . $domain_suffix, Url::fromUri($uri, $options)
    )->toString();
    $row['hostname']['#markup'] = $hostname;
    return $row;
  }

}
