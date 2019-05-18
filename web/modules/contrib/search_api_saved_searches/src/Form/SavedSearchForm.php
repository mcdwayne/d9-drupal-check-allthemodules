<?php

namespace Drupal\search_api_saved_searches\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a standard form for editing saved searches.
 */
class SavedSearchForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\search_api_saved_searches\SavedSearchInterface $search */
    $search = $this->getEntity();

    $args['%search_label'] = $search->label();
    $form['#title'] = $this->t('Edit saved search %search_label', $args);

    if ($search->getType()->getOption('allow_keys_change', FALSE)
        && !is_array($search->getQuery()->getOriginalKeys())) {
      $keywords = $search->getQuery()->getOriginalKeys();
      $form['search_keywords'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Fulltext keywords'),
        '#description' => $this->t('The fulltext keywords set on this search.'),
        '#default_value' => $keywords,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\search_api_saved_searches\SavedSearchInterface $search */
    $search = parent::buildEntity($form, $form_state);

    if ($form_state->hasValue('search_keywords')) {
      $keywords = trim($form_state->getValue('search_keywords'));
      if ($keywords === '') {
        $keywords = NULL;
      }
      $query = $search->getQuery();
      $query->keys($keywords);
      $search->setQuery($query);
    }

    return $search;
  }

}
