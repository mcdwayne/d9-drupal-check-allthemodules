<?php

namespace Drupal\media_entity_usage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media_entity\MediaInterface;

class MediaUsageController extends ControllerBase {

  use StringTranslationTrait;

  public function referencesPage(MediaInterface $media) {
    /** @var \Drupal\media_entity_usage\Service\MediaUsageInfo $info */
    $info = \Drupal::service('media_entity_usage.reference_info');
    $refs = $info->getRefsList($media);
    if (!$refs) {
      return [
        'description' => [
          '#prefix' => '<h4>',
          '#suffix' => '</h4>',
          '#markup' => $this->t('This media has no references.')
        ],
      ];
    }
    $table = $info->buildRefsTable($refs);
    return [
      'description' => [
        '#prefix' => '<h4>',
        '#suffix' => '</h4>',
        '#markup' => $this->t('This media is referenced at @count place(s).', ['@count' => count($refs)])
      ],
      'table' => $table
    ];
  }

  public function referencesTitle(MediaInterface $media) {
    return t('Browse media "@media" references', ['@media' => $media->label()]);
  }
}