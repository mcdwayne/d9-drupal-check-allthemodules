<?php
/**
 * @file
 * Contains Drupal\domain_redirect\Controller\DomainRedirectListBuilder.
 */

namespace Drupal\domain_redirect\Controller;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of domain redirect entities.
 *
 * @package Drupal\domain_redirect\Controller
 *
 * @ingroup domain_redirect
 */
class DomainRedirectListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['redirect_domain'] = $this->t('Domain');
    $header['redirect_destination'] = $this->t('Destination');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Extract the redirect destination.
    $url = Url::fromUri($entity->getDestination()['uri']);

    // Build a link.
    $link = [
      '#title' => $url->toString(),
      '#type' => 'link',
      '#url' => $url,
    ];

    // Add the rows.
    $row['redirect_domain'] = $entity->getDomain();
    $row['redirect_destination'] = drupal_render($link);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Check if the destination domain setting is missing.
    if (!\Drupal::config('domain_redirect.settings')->get('destination_domain')) {
      drupal_set_message($this->t('This module must be configured before it can be used. <a href="@settings">Click here</a> to configure the settings.', [
        '@settings' => Url::fromRoute('domain_redirect.settings')->toString(),
      ]), 'error');
    }

    // Build the output.
    $build['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('This module allows you to redirect a given domain to an internal Drupal path on a different domain.'),
    ];
    $build[] = parent::render();
    $build[0]['table']['#empty'] = $this->t('There are no redirects yet.');
    return $build;
  }
}
