<?php

namespace Drupal\scripturefilter\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

use Drupal\Core\Form\FormStateInterface;

include_once 'scripturefilter.inc';

/**
 * Extension of FilterBase to perform this module's substitutions.
 *
 * @Filter(
 *   id = "scripturefilter",
 *   title = @Translation("Scripture Filter"),
 *   description = @Translation("Turns any Scripture reference into a link to one of several online Bibles."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class ScriptureFilter extends FilterBase {

  /**
   * A settings form for a site admin to configure default translation.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $default_trans = isset($this->settings['scripturefilter_default_translation']) ?
      $this->settings['scripturefilter_default_translation'] : "NIV";
    $form['scripturefilter_default_translation'] = [
      '#type' => 'select',
      '#title' => $this->t('Bible Translation'),
      '#default_value' => $default_trans,
      '#description' => $this->t('Select which Bible version to use'),
      '#options' => [
        "KJ21" => t("21st Century King James Version"),
        "ASV" => t("American Standard Version"),
        "AMP" => t("Amplified Bible"),
        "CEV" => t("Contemporary English Version"),
        "DARBY" => t("Darby Translation"),
        "ESV" => t("English Standard Version"),
        "KJV" => t("King James Version"),
        "MSG" => t("The Message"),
        "NASB" => t("New American Standard Bible"),
        "NET" => t("New English Translation"),
        "NIRV" => t("New International Reader's Version"),
        "NIV" => t("New International Version"),
        "NIV1984" => t("New International Version 1984"),
        "NIV-UK" => t("New International Version - UK"),
        "NKJV" => t("New King James Version"),
        "NLT" => t("New Living Translation"),
        "TNIV" => t("Today's New International Version"),
        "WE" => t("Worldwide English New Testament"),
        "WYC" => t("Wycliffe New Testament"),
        "YLT" => t("Young's Literal Translation"),
      ],
    ];
    return $form;
  }

  /**
   * Extension of base class process method, to replace Bible refs with links.
   */
  public function process($text, $langcode) {
    $translation = $this->settings['scripturefilter_default_translation'];
    $processed_text = scripturefilter_scripturize($text, $translation);
    return new FilterProcessResult($processed_text);
  }

  /**
   * Callback function for input filter tips() method.
   */
  public function tips($long = FALSE) {
    return $this->t('Scripture references will be linked automatically to an online Bible.  E.g. John 3:16, Eph 2:8-9 (ESV).');
  }

}
