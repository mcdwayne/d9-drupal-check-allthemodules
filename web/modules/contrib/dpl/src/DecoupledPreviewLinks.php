<?php

namespace Drupal\dpl;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\dpl\Entity\DecoupledPreviewLink;

/**
 * Provides some consumer preview links.
 */
class DecoupledPreviewLinks {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The token replacer.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenReplacer;

  /**
   * Static cache for the preview link instances.
   *
   * @var \Drupal\dpl\PreviewLinkInstance[]
   */
  protected $previewLinkInstances;

  /**
   * DecoupledPreviewLinks constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Utility\Token $tokenReplacer
   *   The token replacer.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    Token $tokenReplacer
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->tokenReplacer = $tokenReplacer;
  }

  /**
   * A list of consumer with preview links.
   *
   * @return \Drupal\dpl\PreviewLinkInstance[]
   *   A list of preview link objects.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If there was an error instantiating the plugins.
   */
  public function getPreviewLinksInstances() {
    if (!empty($this->previewLinkInstances)) {
      return $this->previewLinkInstances;
    }
    $decouple_preview_link_storage = $this->entityTypeManager
      ->getStorage('decoupled_preview_link');
    $ids = $decouple_preview_link_storage
      ->getQuery()
      ->condition('preview_url', '', '<>')
      ->execute();

    $this->previewLinkInstances = array_map(
      function (DecoupledPreviewLink $config) {
        return $config->toPreviewLinkInstance();
      },
      $decouple_preview_link_storage->loadMultiple($ids)
    );
    return $this->previewLinkInstances;
  }

  /**
   * Returns a preview link for a given consumer.
   *
   * @param \Drupal\dpl\PreviewLinkInstance $preview_link
   *   The preview link instance.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity we need preview links for.
   *
   * @return \Drupal\Core\Link
   *   The link object.
   */
  public function getPreviewLink(
    PreviewLinkInstance $preview_link,
    EntityInterface $entity
  ) {
    $preview_url = $preview_link->getPreviewUrl();
    $token_replaced_url = $this->tokenReplacer->replace($preview_url,
      [$entity->getEntityTypeId() => $entity]);
    return Link::fromTextAndUrl($preview_link->getTabLabel() ?: $this->t(
      'Preview @label',
      ['@label' => $preview_link->id()]),
      Url::fromUri($token_replaced_url)
    );
  }

}
