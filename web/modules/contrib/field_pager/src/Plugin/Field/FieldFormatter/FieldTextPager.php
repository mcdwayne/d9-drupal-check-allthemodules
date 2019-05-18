<?php

namespace Drupal\field_pager\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_pager\PagerHelper;
use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Plugin implementation of the 'text_default' formatter.
 *
 * @FieldFormatter(
 *   id = "text_field_pager",
 *   label = @Translation("Default text (Pager)"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class FieldTextPager extends TextDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    return PagerHelper::mergeDefaultSettings($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    return PagerHelper::mergeSettingsForm($form, $form_state, $this, $elements);

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    return PagerHelper::mergeSettingsSummary($summary, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $fields = parent::view($items, $langcode);
    return PagerHelper::mergeView(count($items), $this, $fields);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $index_name = $this->getSetting('index_name');
    $delta = (int) (isset($_GET[$index_name]) ? $_GET[$index_name] : 0);

    if (empty($items[$delta])) {
      throw new NotFoundHttpException();
    }
    $item = $items[$delta];

    // The ProcessedText element already handles cache context & tag bubbling.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    $elements[$delta] = [
      '#type' => 'processed_text',
      '#text' => $item->value,
      '#format' => $item->format,
      '#langcode' => $item->getLangcode(),
    ];

    return $elements;
  }

}
