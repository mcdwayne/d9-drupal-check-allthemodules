<?php

namespace Drupal\field_pager\Plugin\Field\FieldFormatter;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_pager\PagerHelper;
use Drupal\text\Plugin\Field\FieldFormatter\TextDefaultFormatter;

/**
 * Class FieldBasePager, Base class for field pager.
 *
 * @package Drupal\field_pager\Plugin\Field\FieldFormatter
 */
abstract class FieldBasePager extends TextDefaultFormatter {

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
    return PagerHelper::mergeView(count($items), $langcode, $this, $fields);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $index_name = $this->getSetting('index_name');
    $index_current = (int) (isset($_GET[$index_name]) ? $_GET[$index_name] : 0);

    if (empty($items[$index_current])) {
      throw new NotFoundHttpException();
    }

    return $elements;
  }

}
