<?php

namespace Drupal\default_content_moderation\Commands;

use Drush\Commands\DrushCommands;
use Symfony\Component\Finder\Finder;

/**
 * Class ImportCommands
 */
class ImportCommands extends DrushCommands {

  /**
   * @command default-content-moderation-import
   * @aliases dcmi
   */
  public function import() {
    global $config;
    $folder = $config['content_directory'] . "/node";

    $finder = new Finder();
    $finder->files()->in($folder)->name('*.json');;

    $serializer = \Drupal::service('serializer');

    foreach ($finder as $file) {
      $contents = $file->getContents();
      $decoded = $serializer->decode($contents, 'hal_json');
      $state = $decoded['moderation_state'][0]['value'];
      $uuid = $decoded['uuid'][0]['value'];

      if ($state) {
        /** @var \Drupal\node\Entity\Node $node */
        $node = \Drupal::service('entity.repository')
          ->loadEntityByUuid('node', $uuid);

        $node->set('moderation_state', $state);
        $node->save();

        $this->logger()->success(dt('Set @title to @state',
          ['@title' => $node->getTitle(), '@state' => $state]));
      }
    }
  }
}
