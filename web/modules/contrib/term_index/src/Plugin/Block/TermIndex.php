<?php

namespace Drupal\term_index\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Provides a categories list block.
 *
 * @Block(
 *   id = "term_index",
 *   admin_label = @Translation("Term index"),
 *   category = @Translation("Term index"),
 * )
 */
class TermIndex extends BlockBase {

  const SHOW_INDEX_DEFAULT = TRUE;

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $vocabularyNames = static::getVocabularyNames();

    $form['selected_taxonomy'] = [
      '#type' => 'select',
      '#title' => $this->t('Taxonomy'),
      '#options' => $vocabularyNames,
      '#default_value' => $config['selected_taxonomy'] ?? $this->t('No taxonomies available'),
      '#required' => TRUE,
    ];
    $form['show_index'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show index'),
      '#default_value' => $config['show_index'] ?? static::SHOW_INDEX_DEFAULT,
    ];

    return $form;
  }

  /**
   * Return the vocabulary names keyed by their ids.
   */
  public static function getVocabularyNames() {
    $vocabularies = Vocabulary::loadMultiple();
    $vocabularyNames = array_map(function ($vocabulary) {
      /** @var Vocabulary $vocabulary */
      return $vocabulary->label();
    }, $vocabularies);

    return $vocabularyNames;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue(
      'selected_taxonomy',
      $form_state->getValue('selected_taxonomy')
    );
    $this->setConfigurationValue(
      'show_index',
      $form_state->getValue('show_index')
    );
    $this->setConfigurationValue(
      'block_id',
      str_replace('_', '-', $form['id']['#default_value'])
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $termsToDisplay = $this->getTermsToDisplay();

    return [
      '#theme' => 'term_index',
      '#terms' => $termsToDisplay,
      '#id' => $this->getTermIndexId(),
      '#attached' => [
        'library' => [
          'term_index/term-index',
        ],
        'drupalSettings' => [
          'show-index' => $config['show_index'] ?? static::SHOW_INDEX_DEFAULT,
          'terms' => $termsToDisplay,
        ],
      ],
    ];
  }

  /**
   * Get the list to pass to JS and template files.
   */
  public function getTermsToDisplay() {
    $config = $this->getConfiguration();
    $vid = $config['selected_taxonomy'];
    $termIndexId = $this->getTermIndexId();
    /** @var \Drupal\taxonomy\Entity\Term[] $terms */
    $terms = static::getTerms($vid);
    $termsToDisplay[$termIndexId] = [];

    foreach ($terms as $term) {
      $termsToDisplay[$termIndexId][$term->label()] = $term->url();
    }
    ksort($termsToDisplay[$termIndexId]);

    return $termsToDisplay;
  }

  /**
   * Get a unique for this block's term index container.
   */
  public function getTermIndexId() {
    $config = $this->getConfiguration();
    return $config['block_id'] . '-' . $config['selected_taxonomy'];
  }

  /**
   * Get the terms of a taxonomy.
   */
  public static function getTerms($vid) {
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vid)
      ->execute();

    return Term::loadMultiple($tids);
  }

}
