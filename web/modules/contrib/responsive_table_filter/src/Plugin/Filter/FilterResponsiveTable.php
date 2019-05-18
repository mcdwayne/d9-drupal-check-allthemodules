<?php

/**
 * @file
 * Contains \Drupal\responsive_table_filter\Plugin\Filter\FilterResponsiveTable.
 */

namespace Drupal\responsive_table_filter\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a filter that wraps <table> tags with a <figure> tag.
 *
 * @Filter(
 *   id = "filter_responsive_table",
 *   title = @Translation("Responsive Table filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "wrapper_element" = "figure",
 *     "wrapper_classes" = "responsive-figure-table"
 *   }
 * )
 */
class FilterResponsiveTable extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['wrapper_element'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Wrapper element'),
      '#default_value' => $this->settings['wrapper_element'],
      '#description' => $this->t('The element to wrap the responsive table (e.g. figure)'),
    ];
    $form['wrapper_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper class(es)'),
      '#default_value' => $this->settings['wrapper_classes'],
      '#description' => $this->t("Any wrapper class(es) separated by spaces (e.g. responsive-figure-table)"),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    $text = preg_replace_callback('@<table([^>]*)>(.+?)</table>@s', [$this, 'processTableCallback'], $text);

    $result->setProcessedText($text);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE, $context = []) {
    return $this->t('Wraps a %table tags with a %figure tag.', [
      '%table' => '<table>',
      '%figure' => '<' . $this->getWrapperElement() . '>',
    ]);
  }

  /**
   * Callback to replace content of the <table> elements.
   *
   * @param array $matches
   *   An array of matches passed by preg_replace_callback().
   *
   * @return string
   *   A formatted string.
   */
  private function processTableCallback(array $matches) {
    $attributes = $matches[1];
    $text = $matches[2];
    $text = '<' . $this->getWrapperElement() . $this->getWrapperAttributes() . '><table' . $attributes . '>' . $text . '</table></' . $this->getWrapperElement() . '>';

    return $text;
  }

  /**
   * Get the wrapper element.
   *
   * @return string
   *   The wrapper element tag.
   */
  private function getWrapperElement() {
    return Xss::filter($this->settings['wrapper_element']);
  }

  /**
   * Get the wrapper class(es).
   *
   * @return string
   *   The wrapper element classes.
   */
  private function getWrapperAttributes() {
    return new Attribute([
      'class' => [$this->settings['wrapper_classes']],
    ]);
  }

}
