<?php

namespace Drupal\eat;

use Drupal\node\Entity\Node;

/**
 * Eat class used for reusable methods.
 *
 * @package Drupal\eat
 */
class Eat {

  /**
   * Gets all entity id's form {eat}.
   *
   * @return mixed
   */
  public static function getAllEatEntries() {
    return \Drupal::database()->select('eat', 'e')
      ->fields('e', ['etid'])
      ->execute()
      ->fetchCol();
  }

  /**
   * Return config data and restructure into better structure.
   *
   * @return array
   */
  public static function getEatConfig() {
    $config = \Drupal::configFactory()->get('eat.settings')->getRawData();
    $allowed_config = [];
    $i = 0;
    foreach ((array) $config['eat_item'] as $item) {
      if (!empty($item['#vocab'])) {
        $allowed_config[$i]['entity_type'][] = $item['#entity_type'];
        $allowed_config[$i]['bundle'][] = $item['#bundle'];
        $allowed_config[$i]['vocab'][] = $item['#vocab'];
        $i++;
      }
    }
    return $allowed_config;
  }

  /**
   * Returns all bundles set in config.
   *
   * @return array
   */
  public static function getBundles() {
    $eat_config = self::getEatConfig();
    foreach ((array) $eat_config as $config) {
      $bundle[] = $config['bundle'];
    }
    return $bundle;
  }

  /**
   * Returns vocab name for bundle from config.
   *
   * @param $bundle
   *
   * @return array
   */
  public static function getVocab(&$bundle) {
    $eat_config = self::getEatConfig();

    foreach ((array) $eat_config as $k => $v) {
      if (in_array($bundle, $v['bundle'])) {
        $vocabs[] = $v['vocab'];
      }
    }

    return $vocabs;
  }

  /**
   * Return all created entities of a bundle type based off the eat.settings config set.
   */
  public static function getAllNodesForEat() {
    $bundles = self::getBundles();
    // Nodes for now.
    // @todo: make usable with multiple entity types.
    foreach ($bundles as $key => $value) {
      $nids = \Drupal::entityQuery('node')->condition('type', $value)->execute();
      $nid[] = $nids;
    }
    $merged = array_merge($nid[0], $nid[1]);

    return $merged;
  }

  /**
   * Match up what entities are already set in {eat} compared to entries in {node}.
   */
  public static function matchupEntitiesToSet() {
    $eat_entities = self::getAllNodesForEat();
    $eat_items = self::getAllEatEntries();

    // Combine ids.
    foreach ($eat_items as $item) {
      $i[] = $item;
    }
    $missing_nids = array_diff($eat_entities, $i);

    if (!empty($eat_items) && !in_array($missing_nids, $eat_entities)) {
      foreach ($missing_nids as $nid) {
        $node = Node::load($nid);
        $title = $node->getTitle();
        $bundle = $node->getType();
        // Find out vocab for bundle.
        $vocabs = self::getVocab($bundle);

        // @todo: give better names for variables.
        foreach ((array) $vocabs as $k => $v) {
          foreach ((array) $v as $vocab_name => $vid) {
            foreach ($vid as $eatv) {
              self::createEatEntry($title, $nid, $eatv);
              \Drupal::logger('eat')->notice('Entity added @id', ['@id' => $nid]);
            }
          }
        }

      }
    }
  }

  /**
  * Creates entry in {eat}.
  *
  * @param $title
  * @param $entity_id
  * @param $vid
  * @throws \Exception
  */
  public static function createEatEntry(&$title, &$entity_id, &$vid) {
    eat_add_term($title, $entity_id, $vid);
    \Drupal::logger('eat')->notice('Entity added @id', ['@id' => $entity_id]);
  }

  /**
   * Simple check to see if the term name exists.
   *
   * @param
   * Title : $title
   *
   * @return
   * Returns a term id.
   */
  public static function checkIfExists(&$title) {
    $entity_title = $title[0]['value'];
    return \Drupal::database()->select('taxonomy_term_field_data', 't')
      ->fields('t', ['tid'])
      ->condition('name', $entity_title)
      ->execute()
      ->fetchField();
  }

}
