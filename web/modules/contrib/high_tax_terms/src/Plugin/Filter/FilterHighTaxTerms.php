<?php

namespace Drupal\high_tax_terms\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Provide textfilter to highlight taxonomy terms.
 *
 * @Filter(
 *   id = "filter_high_tax_terms",
 *   title = @Translation("Highlight taxonomy terms"),
 *   description = @Translation("Provide textfilter to highlight taxonomy terms"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterHighTaxTerms extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($this->settings['high_tax_terms_taxonomy']);
    foreach ($terms as $term) {
      switch (TRUE) {
        case (isset($term->description__value)):
          $text = str_replace($term->name, sprintf('<span class="ttf_replace">%s<span class="ttf_description">%s</span></span>', $term->name, strip_tags($term->description__value)), $text);
          break;

        case (!isset($term->description__value)):
          $text = str_replace($term->name, sprintf('<span class="ttf_replace_none">%s</span>', $term->name), $text);
          break;
      }
    }
    $result = new FilterProcessResult($text);
    $result->setAttachments([
      'library' => [
        'high_tax_terms/high_tax_terms_popup',
      ],
    ]);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach (Vocabulary::loadMultiple() as $vocs) {
      $options[$vocs->id()] = $vocs->label();
    }

    $form['high_tax_terms_taxonomy'] = [
      '#type' => 'select',
      '#title' => $this->t('Taxonomy'),
      '#options' => $options,
      '#default_value' => $this->settings['high_tax_terms_taxonomy'],
      '#description' => $this->t('Select taxonomy for highlighting'),
    ];

    return $form;
  }

}
