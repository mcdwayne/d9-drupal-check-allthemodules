<?php

namespace Drupal\table_bs_filter\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

define('TABLE_BS_FILTER_REGEX', '/\<table.*?\>/s');
define('TABLE_BS_FILTER_CELLS_REGEX', '/\<[tr|td|th].*?\>/s');
define('TABLE_BS_FILTER_END_REGEX', '/\<\/table.*?\>/s');

/**
 * Add Bootstrap Class to any tables.
 *
 * @Filter(
 *   id = "table_bs_filter",
 *   title = @Translation("Add Bootstrap Class to any tables"),
 *   description = @Translation("This filter will add table table-stripped table-hover table-responsive to any tables."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class TableBSFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['remove_width_height'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove Width & Height From Cells'),
      '#default_value' => $this->settings['remove_width_height'],
      '#description' => $this->t('This option will cleared width and height from cells.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $text = preg_replace_callback(TABLE_BS_FILTER_REGEX, [&$this, 'tableBsFilterReplace'], $text);
    if ($this->settings['remove_width_height']) {
      $text = preg_replace_callback(TABLE_BS_FILTER_CELLS_REGEX, [&$this, 'tableBsFilterRemoveWidthHeight'], $text);
    }
    $text = preg_replace_callback(TABLE_BS_FILTER_END_REGEX, [&$this, 'tableBsFilterEndReplace'], $text);

    $result = new FilterProcessResult($text);
    return $result;
  }

  /**
   * Callback to convert a table to boostrap table.
   *
   * @param string $match
   *   Takes a match of tag code.
   *
   * @return string
   *   The HTML markup representation of the tag, or an empty string on failure.
   */
  private function tableBsFilterReplace($match) {
    $table_id = '';
    $table_classes = '';
    $table_styles = '';

    if (preg_match('/id="(.+)"/', $match[0], $id)) {
      $table_id = ' id="' . $id[1] . '"';
    }
    if (preg_match('/class="(.+)"/', $match[0], $classes)) {
      $table_classes = ' ' . $classes[1];
    }
    if (preg_match('/style="(.+)"/', $match[0], $styles)) {
      $table_styles = 'style="' . $styles[1] . '"';
      $table_styles = preg_replace('/width.+;/', '', $table_styles);
    }

    return '<div class="table-responsive"><table class="table table-bordered table-striped table-hover' . $table_classes . '"' . $table_id . $table_styles . '>';
  }

  /**
   * Callback to convert a table to boostrap table & remove cells width/height.
   *
   * @param string $match
   *   Takes a match of tag code.
   *
   * @return string
   *   The HTML markup representation of the tag, or an empty string on failure.
   */
  private function tableBsFilterRemoveWidthHeight($match) {
    $tbody = $match[0];
    $tbody = preg_replace('/width.+;/', '', $tbody);
    $tbody = preg_replace('/height.+;/', '', $tbody);
    $tbody = preg_replace('/width="(.+)"/', '', $tbody);
    $tbody = preg_replace('/height="(.+)"/', '', $tbody);
    return $tbody;
  }

  /**
   * Callback to convert a table to boostrap table.
   *
   * @param string $match
   *   Takes a match of tag code.
   *
   * @return string
   *   The HTML markup representation of the tag, or an empty string on failure.
   */
  private function tableBsFilterEndReplace($match) {
    return '</table></div>';
  }

}
