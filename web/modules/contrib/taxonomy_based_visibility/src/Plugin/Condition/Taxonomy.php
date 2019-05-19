<?php

namespace Drupal\taxonomy_based_visibility\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

error_reporting(0);
/**
 * Provides a 'taxonomy_based_visibility' condition.
 *
 * @Condition(
 *   id = "taxonomy_based_visibility",
 *   label = @Translation("Taxonomy Based Visibility"),
 *   description = @Translation("Taxonomy Based Visibility"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", label = @Translation("User"))
 *   }
 * )
 */
class Taxonomy extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $vids = Vocabulary::loadMultiple();
    $form['#attached']['library'][] = 'taxonomy_based_visibility/taxonomy-based-visibility';
    foreach ($vids as $vid) {
      $title = ucfirst($vid->id());
      $grid_open = FALSE;
      foreach ($this->configuration[$vid->id()][$vid->id()] as $key => $value) {
        if ($this->configuration[$vid->id()][$vid->id()][$key] !== 0 && $key !== "all") {
          $grid_open = TRUE;
          break;
        }
      }
      $form[$vid->id()] = [
        '#type' => 'details',
        '#title' => $title,
        '#open' => $grid_open,
      ];
      $form[$vid->id()][$vid->id()] = [
        '#type' => 'checkboxes',
        '#title' => $title,
        '#description' => $this->t('Check the box for any taxonomy that should have this block visible') ,
        '#options' => $this->getTaxonomy($vid->id()) ,
        '#default_value' => isset($this->configuration[$vid->id()][$vid->id()]) ? $this->configuration[$vid->id()][$vid->id()] : '',
      ];
    }
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'custom_taxonomy' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $vids = Vocabulary::loadMultiple();
    foreach ($vids as $vid) {
      $this->configuration[$vid->id()] = array_filter($form_state->getValue($vid->id()));
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // Use the custom_taxonomy labels. They will be sanitized below.
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // Check for Negate the condition checkbox checked.
    $vids = Vocabulary::loadMultiple();
    $block_taxonomy_checked = TRUE;
    foreach ($vids as $vid) {
      foreach ($this->configuration[$vid->id()][$vid->id()] as $key => $value) {
        if ($this->configuration[$vid->id()][$vid->id()][$key] !== 0) {
          $block_taxonomy_checked = FALSE;
        }
      }
    }
    if ($block_taxonomy_checked && !$this->isNegated()) {
      return TRUE;
    }
    // Loads all taxonomy checked in block.
    $vids = Vocabulary::loadMultiple();
    foreach ($vids as $vid) {
      foreach (array_values(array_filter($this->configuration[$vid->id()][$vid->id()])) as $key => $value) {
        $block_taxonomy_choosed[] = $value;
      }
    }

    // Get current URL and load Node.
    $route_match = \Drupal::routeMatch();
    $current_path = \Drupal::service('path.current')->getPath();
    $nid = explode('/', $current_path);
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid[2]);
    if ($route_match->getRouteName() == 'entity.node.canonical') {
      $entity_type_id = 'node';
      $bundle = $node->bundle();
      foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
        if (!empty($field_definition->getTargetBundle()) && $field_definition->getType() == "entity_reference") {
          $node_taxonomys_field_values[] = $node->get($field_name)->getValue();
        }
      }
    }
    foreach ($node_taxonomys_field_values as $value1) {
      foreach ($value1 as $value2) {
        $node_taxonomys_field_value[] = $value2['target_id'];
      }
    }
    return (bool) array_intersect($node_taxonomys_field_value, $block_taxonomy_choosed);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Optimize cache context, if a user cache context is provided, only use
    // user.roles, since that's the only part this condition cares about.
    $contexts = [];
    foreach (parent::getCacheContexts() as $context) {
      $contexts[] = $context == 'user' ? 'user' : $context;
    }
    return $contexts;
  }

  /**
   * Function To Get List of Taxonomys based on vocabulary ID Params number.
   */
  public function getTaxonomy($vid) {
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', $vid);
    $tids = $query->execute();
    $terms = Term::loadMultiple($tids);
    if (!empty($terms)) {
      $options["all"] = "Select All";
      foreach ($terms as $term) {
        $options[$term->tid->value] = $term->name->value;
      }
    }
    return $options;
  }

}
