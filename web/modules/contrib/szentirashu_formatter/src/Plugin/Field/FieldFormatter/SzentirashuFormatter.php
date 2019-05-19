<?php

namespace Drupal\szentirashu_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'szentirashu_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "szentirashu_formatter",
 *   label = @Translation("Szentiras.hu formatter"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class SzentirashuFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    $settings['translation'] = 'KNB';
    $settings['behavior']    = 'link';

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    // Form for selecting preferred translations.
    $form['translation'] = array(
      '#type' => 'select',
      '#title' => t('Translation'),
      '#default_value' => $this->getSetting('translation'),
      '#empty_option' => t('None'),
      '#options' => [
        'KNB'  => $this->t("Káldi Nova Vulgata"),
        'SZIT' => $this->t("Bible of St. Stephen's Society"),
        'STL'  => $this->t("New Testament Translation of Simon Tamás László"),
        'BD'   => $this->t("Békés-Dalos New Testament"),
        'RUF'  => $this->t("Hungarian Bible Society's new Bible Translation (2014)"),
       // 'UF'   => $this->t("Hungarian Bible Society's new Bible Translation (1990)"),
        'KG'   => $this->t("Károli Gáspár's Revised Translation"),
      ],
    );

    // Form for selecting preferred behaviour.
    $form['behavior'] = array(
      '#type' => 'radios',
      '#title' => t('Behavior'),
      '#default_value' => $this->getSetting('behavior'),
      '#options' => [
        'auto'  => $this->t('Auto load text'),
        'click' => $this->t('Load text on click'),
        'link'  => $this->t('Standard link'),
      ],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    // Summarize settings.
    $summary[] = t('Translation: @translation', array('@translation' => $this->getSetting('translation')));
    $summary[] = t('Behavior: @behavior', array('@behavior' => $this->getSetting('behavior')));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $this->viewValue($item),
        '#translation' => $this->getSetting('translation'),
        '#behavior' => $this->getSetting('behavior'),
        '#reference' => $item->value,
        '#protocol' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ? 'https:' : 'http:',
        '#theme' => 'szentirashu_formatter',
        '#attached' => array(
          'library' => array(
            'szentirashu_formatter/szentirashu-loader',
          ),
        ),
      ];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
