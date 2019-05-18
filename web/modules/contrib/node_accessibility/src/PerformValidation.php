<?php

namespace Drupal\node_accessibility;

use Drupal\node\Entity\Node;
use Drupal\node_accessibility\TypeSettingsStorage;
use Drupal\node_accessibility\ProblemsStorage;
use Drupal\quail_api\QuailApiValidation;
use Drupal\quail_api\QuailApiSettings;

/**
 * Class PerformValidation.
 */
class PerformValidation {

  /**
   * Performs validation on the given nodes and stores the results.
   *
   * @param array $nodes_or_nids
   *   An array of node objects or node ids.
   * @param string|null $language
   *   (optional) The language to use during validation
   * @param array|null $severity
   *   (optional) An array of booleans representing the qual test display levels
   * @param array|null $standards
   *   (optional) An array of standards to use when validating.
   *   This will override a node type specific standards.
   *
   * @return array
   *   An array of all test failures, if any.
   */
  public static function nodes(array $nodes_or_nids, $language = NULL, $severity = NULL, $standards = NULL) {
    if (count($nodes_or_nids) == 0) {
      return [];
    }

    $results = [];
    $quail_standards = QuailApiSettings::get_standards();
    $quail_methods = QuailApiSettings::get_validation_methods();
    $type_settings = [];
    $node_type_standards = $standards;

    foreach ($nodes_or_nids as $node_or_nid) {
      if (is_numeric($node_or_nid)) {
        $node = Node::load($node_or_nid);
      }
      else {
        $node = $node_or_nid;
      }

      if (!($node instanceof Node)) {
        continue;
      }

      $id_node = $node->nid->value;
      $id_revision = $node->vid->value;

      if (!isset($results[$id_node][$id_revision])) {
        $results[$id_node][$id_revision] = [];
      }

      $node_type = $node->getType();
      if (!array_key_exists($node_type, $type_settings)) {
        $type_settings[$node_type] = TypeSettingsStorage::loadAsArray($node_type);
      }

      if (is_null($standards)) {
        $node_type_standards = $type_settings[$node_type]['standards'];
      }

      if (!empty($node_type_standards)) {
        $node_view = node_view($node, 'full', $language);
        $rendered_node = drupal_render($node_view);
        unset($node_view);

        foreach ($node_type_standards as $standard_name) {
          $results[$id_node][$id_revision] = array_merge($results[$id_node][$id_revision], QuailApiValidation::validate($rendered_node, $quail_standards[$standard_name], $severity));
        }
        unset($rendered_node);
      }

      if (isset($results[$id_node][$id_revision]['report'])) {
        $database = FALSE;
        if (!empty($type_settings[$node_type]['method']) && is_array($quail_methods) && array_key_exists('database', $quail_methods[$type_settings[$node_type]['method']])) {
          $database = $quail_methods[$type_settings[$node_type]['method']]['database'];
        }

        if ($database && !empty($results[$id_node][$id_revision]['report'])) {
          $no_failures = TRUE;
          foreach ($results[$id_node][$id_revision]['report'] as $severity => $severity_results) {
            if (isset($severity_results['total']) && $severity_results['total'] > 0) {
              $no_failures = FALSE;
              break;
            }
          }

          if ($no_failures) {
            ProblemsStorage::delete_node_problems($id_node, $id_revision);
          }
          else {
            ProblemsStorage::save_node_problems($id_node, $id_revision, $results[$id_node][$id_revision]['report']);
          }
        }
      }

      unset($node);
    }

    return $results;
  }

  /**
   * Performs validation on the given nodes and stores the results.
   *
   * @param array $revisions_or_vids
   *   An array of node (revision) objects or node revision ids.
   *   The array key must be the node revision id.
   *   The array value is either the node object or the node id.
   * @param string|null $language
   *   (optional) The language to use during validation
   * @param array|null $severity
   *   (optional) An array of booleans representing the qual test display levels
   * @param array|null $standards
   *   (optional) An array of standards to use when validating.
   *   This will override a node type specific standards.
   *
   * @return array
   *   An array of all test failures, if any.
   */
  public static function node_revisions(array $revisions_or_vids, $language = NULL, $severity = NULL, $standards = NULL) {
    if (count($revisions_or_vids) == 0) {
      return [];
    }

    $results = [];
    $quail_standards = QuailApiSettings::get_standards();
    $quail_methods = QuailApiSettings::get_validation_methods();
    $type_settings = [];
    $node_type_standards = $standards;

    foreach ($revisions_or_vids as $revision_id => $node_or_nid) {
      if (!is_numeric($revision_id)) {
        continue;
      }

      if (is_numeric($node_or_nid)) {
        $node = Node::load($node_or_nid);
      }
      else {
        $node = $node_or_nid;
      }

      if (!($node instanceof Node)) {
        continue;
      }

      if ($node->vid->value != $revision_id) {
        $entity_type = $node->getEntityTypeId();
        $node = \Drupal::entityManager()->getStorage($entity_type)->loadRevision($revision_id);
        unset($entity_type);
      }

      $id_node = $node->nid->value;
      $id_revision = $node->vid->value;

      if (!isset($results[$id_node][$id_revision])) {
        $results[$id_node][$id_revision] = [];
      }

      $node_type = $node->getType();
      if (!array_key_exists($node_type, $type_settings)) {
        $type_settings[$node_type] = TypeSettingsStorage::loadAsArray($node_type);
      }

      if (is_null($standards)) {
        $node_type_standards = $type_settings[$node_type]['standards'];
      }

      if (!empty($node_type_standards)) {
        $node_view = node_view($node, 'full', $language);
        $rendered_node = drupal_render($node_view);
        unset($node_view);

        foreach ($node_type_standards as $standard_name) {
          $results[$id_node][$id_revision] = array_merge($results[$id_node][$id_revision], QuailApiValidation::validate($rendered_node, $quail_standards[$standard_name], $severity));
        }
        unset($rendered_node);
      }

      if (isset($results[$id_node][$id_revision]['report'])) {
        $database = FALSE;
        if (!empty($type_settings[$node_type]['method']) && is_array($quail_methods) && array_key_exists('database', $quail_methods[$type_settings[$node_type]['method']])) {
          $database = $quail_methods[$type_settings[$node_type]['method']]['database'];
        }

        if ($database && !empty($results[$id_node][$id_revision]['report'])) {
          $no_failures = TRUE;
          foreach ($results[$id_node][$id_revision]['report'] as $severity => $severity_results) {
            if (isset($severity_results['total']) && $severity_results['total'] > 0) {
              $no_failures = FALSE;
              break;
            }
          }

          if ($no_failures) {
            ProblemsStorage::delete_node_problems($id_node, $id_revision);
          }
          else {
            ProblemsStorage::save_node_problems($id_node, $id_revision, $results[$id_node][$id_revision]['report']);
          }
        }
      }

      unset($node);
    }

    return $results;
  }
}
