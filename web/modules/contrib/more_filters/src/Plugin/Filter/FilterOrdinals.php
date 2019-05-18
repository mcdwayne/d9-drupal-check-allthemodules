<?php

namespace Drupal\more_filters\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to format ordinals (such as: 1st, 2nd, 4th).
 *
 * This filter wraps any ordinals that it finds in HTML text nodes with
 * <span class="ordinal"></span>.
 *
 * @Filter(
 *   id = "filter_ordinals",
 *   title = @Translation("Wrap ordinals (nd/st/th/rd) in <code>&lt;span&gt;</code> tags."),
 *   description = @Translation("Allows ordinals to be superscripted separately with css. Examples: 1st, 2nd, 3rd, 4th, etc."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class FilterOrdinals extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult(_filter_ordinals($text, $this));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('Add &lt;span class="ordinal"&gt; tags around any ordinals (nd/st/th/rd), such as in 1st, 2nd, 3rd, etc.');
    }
    else {
      return $this->t('Wrap ordinals (nd/st/th/rd) in &lt;span class="ordinal"&gt; tags.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['more_filters_ordinals_tag_default'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Wrap ordinals in &lt;sup&gt; tags instead of &lt;span class="ordinal"&gt; tags.'),
      '#default_value' => $this->settings['more_filters_ordinals_tag_default'],
    );
    return $form;
  }

}
