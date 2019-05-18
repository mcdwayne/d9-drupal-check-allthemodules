<?php

/**
 * @file
 * The main class of the PowerTagging Corpus module.
 */

namespace Drupal\powertagging_corpus;
use Drupal\file\Entity\File;
use Drupal\powertagging\PowerTagging;
use Drupal\semantic_connector\Api\SemanticConnectorPPTApi;
use Drupal\semantic_connector\SemanticConnector;

/**
 * A collection of static functions offered by the PoolParty Taxonomy Manager module.
 */
class PowerTaggingCorpus {

  /**
   * Push a single entity into the corpus of a PoolParty project.
   *
   * @param array $entity_info
   *   Associative array of information about the entity containing following
   *   properties:
   *   - "id" --> The ID of the entity.
   *   - "entity_type" --> The entity type.
   *   - "bundle" --> The bundle of the entity.
   *   - "content_type" --> basically the bundle, but using the vocabulary ID for
   *     taxonomy terms instead of the machine name.
   * @param array $field_ids
   *   Array of field IDs to use as the content which gets pushed into the corpus.
   * @param array $corpus_details
   *   Associative array of information about the corpus containing following
   *   properties:
   *   - "connection_id" --> The ID of the PowerTagging connection to use.
   *   - "project_id" --> The ID of the project to use.
   *   - "corpus_id" --> The ID of the corpus to use.
   */
  public static function pushEntityToCorpus($entity_info, $field_ids, $corpus_details) {
    /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
    $entity = \Drupal::entityTypeManager()->getStorage($entity_info['entity_type'])->load($entity_info['id']);
    if (empty($entity)) {
      return;
    }

    // Get the fields to use for the content pushing in the correct format.
    $field_config = array();
    foreach ($field_ids as $tag_field_name) {
      if ($tag_field_name) {
        $info = PowerTagging::getInfoForTaggingField([
          'entity_type_id' => $entity_info['entity_type'],
          'bundle' => $entity_info['bundle'],
          'field_type' => $tag_field_name,
        ]);
        if (!empty($info)) {
          $field_config[$tag_field_name] = $info;
        }
      }
    }

    // Extract the text to push into the corpus.
    $entity_content = PowerTagging::extractEntityContent($entity, $field_config);

    $connection = SemanticConnector::getConnection('pp_server', $corpus_details['connection_id']);
    /** @var SemanticConnectorPPTApi $ppt_api */
    $ppt_api = $connection->getApi('PPT');
    // Add the text to the corpus.
    if (!empty($entity_content['text'])) {
      $ppt_api->addDataToCorpus($corpus_details['project_id'], $corpus_details['corpus_id'], $entity->label(), $entity_content['text'], 'text');
    }

    // Add files into the corpus if any were extracted.
    if (!empty($entity_content['file_ids'])) {
      foreach ($entity_content['file_ids'] as $file_id) {

        $file = File::load($file_id);
        // Use only existing files.
        if (file_exists($file->getFileUri())) {
          $ppt_api->addDataToCorpus($corpus_details['project_id'], $corpus_details['corpus_id'], $file->getFilename(), $file, 'file');
        }
      }
    }
  }
}
