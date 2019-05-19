<?php
/**
 * @file
 * Contains \Drupal\textareatabs\Plugin\Filter\TextAreaTabs
 */

namespace Drupal\textareatabs\Plugin\Filter;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Filter plugin for textareatabs.
 *
 * @Filter(
 *   id = "textareatabs",
 *   title = @Translation("Replace tabs with non-breaking spaces"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "textareatabs_character" = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
 *   },
 *   weight = 0
 * )
 */
class TextAreaTabs extends FilterBase {
  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $text = Xss::filter(str_replace("\t", $this->settings['textareatabs_character'], $text), []);
    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['textareatabs_character'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Replacement string for each tab'),
      '#default_value' => $this->settings['textareatabs_character'],
      '#required' => TRUE,
    ];
    return $form;
  }
}