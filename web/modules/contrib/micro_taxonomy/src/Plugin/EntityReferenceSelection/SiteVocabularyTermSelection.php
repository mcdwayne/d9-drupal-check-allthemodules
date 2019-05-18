<?php

namespace Drupal\micro_taxonomy\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection;

/**
 * Provides specific access control for the taxonomy_term entity type.
 *
 * @EntityReferenceSelection(
 *   id = "site_vocabulary:taxonomy_term",
 *   label = @Translation("Site Vocabulary Term selection"),
 *   entity_types = {"taxonomy_term"},
 *   group = "site_vocabulary",
 *   base_plugin_label = @Translation("Site Vocabulary Term selection"),
 *   weight = 1
 * )
 */
class SiteVocabularyTermSelection extends TermSelection {

  /**
   * The site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['warning'] = [
      '#markup' => '<div class="messages messages--warning">' . $this->t('This Plugin selection select dynamically the vocabulary of a micro site as the target bundle.') . '</div>',
      '#weight' => -1,
    ];

    // Unset the target bundle form. We dynamically provide the target bundle
    // on a active site.
    $form['target_bundles'] = [
      '#type' => 'value',
      '#value' => [],
    ];
    // Sorting is not possible for taxonomy terms because we use
    // \Drupal\taxonomy\TermStorageInterface::loadTree() to retrieve matches.
    $form['sort']['#access'] = FALSE;

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    if ($match || $limit) {
      return parent::getReferenceableEntities($match, $match_operator, $limit);
    }

    $options = [];
    $bundle_name = $this->getSiteVocabulary();

    $has_admin_access = $this->currentUser->hasPermission('administer taxonomy');
    $unpublished_terms = [];
    if ($vocabulary = Vocabulary::load($bundle_name)) {
      /** @var \Drupal\taxonomy\TermInterface[] $terms */
      if ($terms = $this->entityManager->getStorage('taxonomy_term')->loadTree($vocabulary->id(), 0, NULL, TRUE)) {
        foreach ($terms as $term) {
          if (!$has_admin_access && (!$term->isPublished() || in_array($term->parent->target_id, $unpublished_terms))) {
            $unpublished_terms[] = $term->id();
            continue;
          }
          $options[$vocabulary->id()][$term->id()] = str_repeat('-', $term->depth) . Html::escape($this->entityManager->getTranslationFromContext($term)->label());
        }
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {
    if ($match) {
      return parent::countReferenceableEntities($match, $match_operator);
    }

    $total = 0;
    $referenceable_entities = $this->getReferenceableEntities($match, $match_operator, 0);
    foreach ($referenceable_entities as $bundle => $entities) {
      $total += count($entities);
    }
    return $total;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $configuration = $this->getConfiguration();
    $target_type = $configuration['target_type'];
    $entity_type = $this->entityManager->getDefinition($target_type);

    $query = $this->entityManager->getStorage($target_type)->getQuery();

    // Override the target bundle by the site's vocabulary.
    $target_bundle = $this->getSiteVocabulary();
    if ($target_bundle) {
      $configuration['target_bundles'] = [$target_bundle];
    }

    // If 'target_bundles' is NULL, all bundles are referenceable, no further
    // conditions are needed.
    if (is_array($configuration['target_bundles'])) {
      // If 'target_bundles' is an empty array, no bundle is referenceable,
      // force the query to never return anything and bail out early.
      if ($configuration['target_bundles'] === []) {
        $query->condition($entity_type->getKey('id'), NULL, '=');
        return $query;
      }
      else {
        $query->condition($entity_type->getKey('bundle'), $configuration['target_bundles'], 'IN');
      }
    }

    if (isset($match) && $label_key = $entity_type->getKey('label')) {
      $query->condition($label_key, $match, $match_operator);
    }

    // Add entity-access tag.
    $query->addTag($target_type . '_access');

    // Add the Selection handler for system_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addMetaData('entity_reference_selection_handler', $this);

    // Add the sort option.
    if ($configuration['sort']['field'] !== '_none') {
      $query->sort($configuration['sort']['field'], $configuration['sort']['direction']);
    }

    // Adding the 'taxonomy_term_access' tag is sadly insufficient for terms:
    // core requires us to also know about the concept of 'published' and
    // 'unpublished'.
    if (!$this->currentUser->hasPermission('administer taxonomy')) {
      $query->condition('status', 1);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $term = parent::createNewEntity($entity_type_id, $bundle, $label, $uid);

    // In order to create a referenceable term, it needs to published.
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term->setPublished();
    if ($site = $this->negotiator()->getActiveSite()) {
      $term->set('site_id', $site);
    }

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    if (!$this->currentUser->hasPermission('administer taxonomy')) {
      $entities = array_filter($entities, function ($term) {
        /** @var \Drupal\taxonomy\TermInterface $term */
        return $term->isPublished();
      });
    }
    return $entities;
  }

  /**
   * Get the vocabulary name attached to the active site.
   *
   * @return null|string
   */
  protected function getSiteVocabulary() {
    $bundle_name = NULL;
    $active_site = $this->negotiator()->getActiveSite();
    if ($active_site->hasVocabulary()) {
      $bundle_name = $active_site->getSiteVocabulary();
    }
    return $bundle_name;
  }

  /**
   * Gets the site negotiator.
   *
   * @return \Drupal\micro_site\SiteNegotiatorInterface
   *   The site negotiator.
   */
  protected function negotiator() {
    if (!$this->negotiator) {
      $this->negotiator = \Drupal::service('micro_site.negotiator');
    }
    return $this->negotiator;
  }

  /**
   * Sets the site negotiator for this handler.
   *
   * @param \Drupal\micro_site\SiteNegotiatorInterface
   *   The site negotiator.
   *
   * @return $this
   */
  public function setNegotiator(SiteNegotiatorInterface $negotiator) {
    $this->negotiator = $negotiator;
    return $this;
  }

}
