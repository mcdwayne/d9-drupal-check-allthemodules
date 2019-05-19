<?php

namespace Drupal\wbm2cm\Plugin\Deriver;

class RestoreDeriver extends ModerationDeriver {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = parent::getDerivativeDefinitions($base_plugin_definition);

    foreach ($this->derivatives as $id => &$derivative) {
      $keys = $this->entityTypeManager->getDefinition($id)->getKeys();

      $derivative['source']['plugin'] = "content_entity_revision:$id";

      foreach (['id', 'revision', 'langcode'] as $key) {
        $key = $keys[$key];
        $derivative['process'][$key] = $key;
      }

      $derivative['process']['moderation_state'][0] += [
        'source' => [
          $keys['id'],
          $keys['revision'],
          $keys['langcode'],
        ],
        'migration' => [
          "wbm2cm_save:$id",
        ],
      ];
      $derivative['destination']['plugin'] = "entity_revision:$id";
      $derivative['migration_dependencies']['required'][] = "wbm2cm_save:$id";
    }
    return $this->derivatives;
  }

}
