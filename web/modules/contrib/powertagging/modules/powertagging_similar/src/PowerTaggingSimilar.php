<?php

/**
 * @file
 * The main class of the PowerTagging SeeAlso Engine module.
 */

namespace Drupal\powertagging_similar;
use Drupal\Core\Link;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\powertagging\PowerTagging;
use Drupal\powertagging_similar\Entity\PowerTaggingSimilarConfig;

/**
 * A collection of static functions offered by the PowerTagging module.
 */
class PowerTaggingSimilar {

  protected $config;

  /**
   * PowerTagging constructor.
   *
   * @param PowerTaggingSimilarConfig $config
   *   The configuration of the PowerTagging SeeAlso widget.
   */
  public function __construct($config) {
    $this->config = $config;
  }

  /**
   * Display a PowerTagging Simliar Content widget.
   *
   * @param string $entity_type
   *   The entity type of the entity to show similar content for.
   * @param int $entity_id
   *   The ID of the entity to show similar content for.
   *
   * @return string
   *   The HTML of the widget.
   */
  public function displayWidget($entity_type, $entity_id) {
    $content = '';
    /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
    $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
    $powertagging = new PowerTagging(PowerTaggingConfig::load($this->config->getPowerTaggingId()));

    $field_instances = $powertagging->getTaggingFieldInstances();

    // Get all the tags of the current entity.
    $entity_tids = array();
    /** @var \Drupal\field\Entity\FieldConfig $field_instance */
    foreach ($field_instances as $field_instance) {
      if ($field_instance->getTargetEntityTypeId() == $entity_type && $entity->bundle() == $field_instance->getTargetBundle() && $entity->hasField($field_instance->getName())) {
        $field_values = $entity->get($field_instance->getName())->getValue();
        if (!empty($field_values)) {
          foreach ($field_values as $field_value) {
            $entity_tids[] = $field_value['target_id'];
          }
        }
      }
    }

    // Return if the original entity was not tagged yet.
    if (empty($entity_tids)) {
      return $content;
    }

    $settings = $this->config->getConfig();
    $content_types = $settings['content_types'][$this->config->getPowerTaggingId()];
    // Content gets loaded and displayed by content type.
    if (!$settings['merge_content']) {
      $tab_content = '';
      $content_types_to_add = array();
      foreach ($content_types as $content_type) {
        if (!$content_type['show']) {
          continue;
        }
        $exploded_field_key = explode(' ', $content_type['entity_key']);
        $similar_entity_scores = $this->getSimilarContent(
          $entity_tids,
          array(
            array(
              'entity_type' => $exploded_field_key[0],
              'bundle' => $exploded_field_key[1],
              'field_id' => $exploded_field_key[2]
            )
          ),
          $content_type['count'],
          array(
            array(
              'entity_type' => $entity_type,
              'entity_id' => $entity_id,
            )
          )
        );

        if (!empty($similar_entity_scores)) {
          $content_types_to_add[] = $content_type['entity_key'];

          // Load all the entities.
          $entities_to_load = array();
          foreach (array_keys($similar_entity_scores) as $entity_info) {
            $exploded_entity_info = explode('|', $entity_info);
            $entities_to_load[$exploded_entity_info[0]][] = $exploded_entity_info[1];
          }
          $loaded_entities = array();
          foreach ($entities_to_load as $entity_type => $entity_ids) {
            $entities = \Drupal::entityTypeManager()->getStorage($entity_type)->loadMultiple($entity_ids);
            foreach ($entities as $entity_id => $entity) {
              $loaded_entities[$entity_type . '|' . $entity_id] = $entity;
            }
          }

          // Display the content.
          //$tab_content .= '<div id="powertagging-similar-widget-' . $widget->id . '-tabs-' . str_replace(' ', '-', $content_type['entity_key']) . '"><ul>';
          $tab_content .= '<h3>' . $content_type['title'] . '</h3><div><ul>';
          foreach ($similar_entity_scores as $similar_entity_info => $score) {
            // If the scored entity could be loaded.
            if (isset($loaded_entities[$similar_entity_info])) {
              $similar_entity = $loaded_entities[$similar_entity_info];
              $exploded_entity_info = explode('|', $similar_entity_info);
              $tab_content .= '<li>' . $this->themeItem($similar_entity, $exploded_entity_info[0]) . '</li>';
            }
          }
          $tab_content .= '</ul></div>';
        }
      }

      if (!empty($tab_content)) {
        // Add the tabs menu.
        /*$content .= '<div class="powertagging-similar-widget-tabs" id="powertagging-similar-widget-' . $widget->id . '-tabs"><ul>';
        foreach ($content_types as $content_type) {
          if (in_array($content_type['entity_key'], $content_types_to_add)) {
            $content .= '<li><a href="#powertagging-similar-widget-' . $widget->id . '-tabs-' . str_replace(' ', '-', $content_type['entity_key']) . '">' . $content_type['title'] . '</a></li>';
          }
        }

        // Add the tab contents.
        $content .= '</ul>' . $tab_content . '</div>';*/

        $content .= '<div class="powertagging-similar-widget-accordion" id="powertagging-similar-widget-' . $this->config->id() . '-accordion">' . $tab_content . '</div>';
      }
    }
    // Content gets loaded and displayed all together.
    else {
      $similar_content_types = array();
      foreach ($content_types as $content_type) {
        if (!$content_type['show']) {
          continue;
        }
        $exploded_field_key = explode(' ', $content_type['entity_key']);
        $similar_content_types[] = array(
          'entity_type' => $exploded_field_key[0],
          'bundle' => $exploded_field_key[1],
          'field_id' => $exploded_field_key[2]
        );
      }

      $similar_entity_scores = $this->getSimilarContent(
        $entity_tids,
        $similar_content_types,
        $settings['merge_content_count'],
        array(
          array(
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
          )
        )
      );

      if (!empty($similar_entity_scores)) {
        // Load all the entities.
        $entities_to_load = array();
        foreach (array_keys($similar_entity_scores) as $entity_info) {
          $exploded_entity_info = explode('|', $entity_info);
          $entities_to_load[$exploded_entity_info[0]][] = $exploded_entity_info[1];
        }
        $loaded_entities = array();
        foreach ($entities_to_load as $entity_type => $entity_ids) {
          $entities = \Drupal::entityTypeManager()->getStorage($entity_type)->loadMultiple($entity_ids);
          foreach ($entities as $entity_id => $entity) {
            $loaded_entities[$entity_type . '|' . $entity_id] = $entity;
          }
        }

        // Display the content.
        $content .= '<ul>';
        foreach ($similar_entity_scores as $similar_entity_info => $score) {
          // If the scored entity could be loaded.
          if (isset($loaded_entities[$similar_entity_info])) {
            $similar_entity = $loaded_entities[$similar_entity_info];
            $exploded_entity_info = explode('|', $similar_entity_info);
            $content .= '<li>' . $this->themeItem($similar_entity, $exploded_entity_info[0]) . '</li>';
          }
        }
        $content .= '</ul>';
      }
    }
    return $content;
  }

  /**
   * Theme a single item in the list of similar content.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object
   * @param string $entity_type
   *   The entity type
   *
   * @return string
   *   The rendered HTML content
   */
  private function themeItem($entity, $entity_type) {
    $content = '';
    $settings = $this->config->getConfig();

    if ($settings['display_type'] == 'default') {
      $content = Link::fromTextAndUrl($entity->label(), $entity->toUrl())->toString();
    }
    elseif ($settings['display_type'] == 'view_mode') {
      $render_controller = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId());
      $themeable_array = $render_controller->view($entity, 'powertagging_similar_widget');

      if (!empty($themeable_array)) {
        $content = \Drupal::service('renderer')->render($themeable_array);
      }
    }

    return $content;
  }

  /**
   * Get entities similar to a list of taxonomy term ids.
   *
   * @param array $tags
   *   Array of taxonomy terms to get similar content for.
   * @param array $entities_to_check
   *   An array of entities to ignore, each value is an array itself containing following keys:
   *     'entity_type' => The entity type of entities to get.
   *     'bundle' => The bundle of entities to get.
   *     'field_id' => The field to check for similar tags.
   * @param int $count
   *   The maximum number of items to return.
   * @param array $entities_to_ignore
   *   An array of entities to ignore, each value is an array itself containing following keys:
   *     'entity_type' => The entity type of the entity to ignore.
   *     'entity_id' => The ID of the entity to ignore.
   *
   * @return array
   *   Array of similar entity ids (=key) and their scores (=value).
   */
  private function getSimilarContent(array $tags, array $entities_to_check, $count, array $entities_to_ignore = array()) {
    $entity_scores = array();

    foreach ($entities_to_check as $entity_information) {
      $tags_query = \Drupal::database()->select($entity_information['entity_type'] . '__' . $entity_information['field_id'], 'f');
      $tags_query->fields('f', array('entity_id', $entity_information['field_id'] . '_target_id'));
      $tags_query->condition('bundle', $entity_information['bundle']);

      if ($entity_information['entity_type'] == 'node') {
        $tags_query->join('node_field_data', 'n', 'f.entity_id = n.nid');
        $tags_query->condition('n.status', 1);
      }
      elseif ($entity_information['entity_type'] == 'user') {
        $tags_query->join('users_field_data', 'u', 'f.entity_id = u.uid');
        $tags_query->condition('u.status', 1);
      }

      $all_tags = $tags_query->execute()->fetchAll();

      $sorted_tags = array();
      foreach ($all_tags as $tag) {
        $sorted_tags[$tag->entity_id][] = $tag->{$entity_information['field_id'] . '_target_id'};
      }

      foreach ($sorted_tags as $check_entity_id => $check_entity_tids) {
        $intersection = array_intersect($tags, $check_entity_tids);
        if (count($intersection) > 0) {
          $entity_scores[$entity_information['entity_type'] . '|' . $check_entity_id] = count($intersection) / count($check_entity_tids);
        }
      }
    }
    arsort($entity_scores);

    $scores_by_entity = array();
    foreach (array_keys($entity_scores) as $similar_entity_id) {
      // Check if this entity has to be ignored.
      $ignore_entity = FALSE;
      foreach ($entities_to_ignore as $entity_to_ignore) {
        if ($similar_entity_id == $entity_to_ignore['entity_type'] . '|' . $entity_to_ignore['entity_id']) {
          $ignore_entity = TRUE;
          break;
        }
      }
      if ($ignore_entity) {
        continue;
      }

      // Only add as many items as count says maximum.
      $scores_by_entity[$similar_entity_id] = $entity_scores[$similar_entity_id];
      if (count($scores_by_entity) >= $count) {
        break;
      }
    }

    return $scores_by_entity;
  }
}
