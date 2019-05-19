<?php

namespace Drupal\wisski_core;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\wisski_core\Entity\WisskiBundle;

/**
 * Provides dynamic permissions for nodes of different types.
 */
class WisskiPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;
  
  public function WisskiBundlePermissions() {
    $perms = [];
    
    foreach(WisskiBundle::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }
    
    return $perms;
  } 
  
  protected function buildPermissions(WisskiBundle $type) {
    $type_id = $type->id();
    $type_params = ['%type_id' => $type->id(), '%type_name' => $type->label()];

    return [
      "create $type_id WisskiBundle" => [
        'title' => $this->t('%type_name (%type_id): Create new content', $type_params),
      ],
      "view own $type_id WisskiBundle" => [
        'title' => $this->t('%type_name (%type_id): View own content', $type_params),
      ],
      "view any $type_id WisskiBundle" => [
        'title' => $this->t('%type_name (%type_id): View any content', $type_params),
      ],
      "edit own $type_id WisskiBundle" => [
        'title' => $this->t('%type_name (%type_id): Edit own content', $type_params),
      ],
      "edit any $type_id WisskiBundle" => [
        'title' => $this->t('%type_name (%type_id): Edit any content', $type_params),
      ],
      "delete own $type_id WisskiBundle" => [
        'title' => $this->t('%type_name (%type_id): Delete own content', $type_params),
      ],
      "delete any $type_id WisskiBundle" => [
        'title' => $this->t('%type_name (%type_id): Delete any content', $type_params),
      ],
      "view $type_id WisskiBundle revisions" => [
        'title' => $this->t('%type_name (%type_id): View revisions', $type_params),
        'description' => t('To view a revision, you also need permission to view the content item.'),
      ],
      "revert $type_id WisskiBundle revisions" => [
        'title' => $this->t('%type_name (%type_id): Revert revisions', $type_params),
        'description' => t('To revert a revision, you also need permission to edit the content item.'),
      ],
      "delete $type_id WisskiBundle revisions" => [
        'title' => $this->t('%type_name (%type_id): Delete revisions', $type_params),
        'description' => $this->t('To delete a revision, you also need permission to delete the content item.'),
      ],
    ];
  }
  
}
  