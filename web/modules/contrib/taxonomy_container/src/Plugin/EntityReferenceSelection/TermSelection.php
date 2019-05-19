<?php

namespace Drupal\taxonomy_container\Plugin\EntityReferenceSelection;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection as BaseTermSelection;

/**
 * Taxonomy container implementation of the entity reference selection plugin.
 *
 * @EntityReferenceSelection(
 *   id = "taxonomy_container",
 *   label = @Translation("Taxonomy term selection (with groups)"),
 *   entity_types = {"taxonomy_term"},
 *   group = "taxonomy_container"
 * )
 */
class TermSelection extends BaseTermSelection {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['prefix' => '-'] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['auto_create']['#access'] = FALSE;
    $form['auto_create_bundle']['#access'] = FALSE;

    $form['prefix'] = [
      '#title' => t('List item prefix'),
      '#type' => 'textfield',
      '#size' => 5,
      '#maxlength' => 5,
      '#description' => $this->t('The character before each child term.'),
      '#default_value' => $this->configuration['prefix'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {

    if ($match || $limit) {
      return parent::getReferenceableEntities($match, $match_operator, $limit);
    }

    $handler_settings = $this->configuration['handler_settings'];
    $bundle_names = isset($handler_settings['target_bundles'])
      ? $handler_settings['target_bundles']
      : array_keys($this->entityManager->getBundleInfo('taxonomy_term'));

    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityManager->getStorage('taxonomy_term');

    $prefix = $this->configuration['prefix'];

    $options = [];
    foreach ($bundle_names as $bundle) {

      // Use first bundle as key. This prevents turning bundle labels into
      // optgroups when more than one bundle were provided.
      // See \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::getSettableOptions().
      if (!isset($key)) {
        $key = $bundle;
      }

      /** @var \Drupal\taxonomy\TermInterface[] $terms */
      $terms = $term_storage->loadTree($bundle, 0, NULL, TRUE);
      foreach ($terms as $term) {
        // Check if this is a parent item. We do a loose comparison on the
        // string value of zero ('0') so that the result is correct both for
        // numeric and string IDs. If we would compare to the numeric value of
        // zero (0) PHP would cast both arguments to numbers. In the case of
        // string IDs the ID would always be casted to a 0 causing the
        // condition to always be TRUE.
        if ($term->parents[0] == '0') {
          $parent = $term;
          $options[$key][$term->id()] = $parent->label();
        }
        else {
          $options[$key][$parent->label()][$term->id()] = str_repeat($prefix, $term->depth) . $term->label();
          // If at least on child has been found, remove the top level term.
          unset($options[$key][$parent->id()]);
        }
      }
    }

    return $options;
  }

}
