<?php

/**
 * @file
 * Contains \Drupal\simpleplayer\Plugin\field\formatter\SimplePlayerFormatter.
 */

namespace Drupal\simpleplayer\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'simpleplayer' formatter.
 *
 * @FieldFormatter(
 *   id = "simpleplayer",
 *   label = @Translation("HTML5 SimplePlayer Formatter"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class SimplePlayerFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'enable_counter' => '',
      'enable_progressbar' => '',
      'enable_download' => '',
      'enable_back30' => '',
      'enable_combospeed' => '',
      ) + parent::defaultSettings();
  }
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    $simpleplayer_counter = $this->getSetting('enable_counter');
    $simpleplayer_progressbar = $this->getSetting('enable_progressbar');
    $simpleplayer_download_button = $this->getSetting('enable_download');
    $simpleplayer_back30_button = $this->getSetting('enable_back30');
    $simpleplayer_combospeed_button = $this->getSetting('enable_combospeed');

    foreach ($items as $delta => $item) {
      $mediapath = $item->getUrl();
      $mediatype = strtolower(strstr(basename(parse_url($mediapath, PHP_URL_PATH)), '.'));

      //$fontawesome = $this->getSetting('fontawesome');
      $fontawesome = TRUE;

      $elements[$delta] = array(
        '#theme' => 'simpleplayer',
        '#simpleplayer_fa' => $fontawesome,
        '#simpleplayer_counter' => $simpleplayer_counter,
        '#simpleplayer_progressbar' => $simpleplayer_progressbar,
        '#simpleplayer_mediapath' => $mediapath,
        '#simpleplayer_mediatype' => $mediatype,
        '#simpleplayer_download_button' => $simpleplayer_download_button,
        '#simpleplayer_back30_button' => $simpleplayer_back30_button,
        '#simpleplayer_combospeed_button' => $simpleplayer_combospeed_button,
        '#attached' => array(
          'library' => array(
            'css/simpleplayer-ideas',
          ),
        ),
      );
    }

    return $elements;
  }
  /**
   * {@inheritdoc}
   */

   public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['enable_counter'] = array(
      '#type'           => 'checkbox',
      '#title'          => t('Enable Counter'),
      '#description'    => t('Enable the visual time counter'),
      '#default_value'  => $this->getSetting('enable_counter'),
    );
    // Progress Checkbox.
    $form['enable_progressbar'] = array(
      '#type'           => 'checkbox',
      '#title'          => t('Enable Progress Bar'),
      '#description'    => t('Enable the visual progress bar'),
      '#default_value'  => $this->getSetting('enable_progressbar'),
    );
    // Download Checkbox.
    $form['enable_download'] = array(
      '#type'           => 'checkbox',
      '#title'          => t('Enable Download'),
      '#description'    => t('Enable the download button'),
      '#default_value'  => $this->getSetting('enable_download'),
    );
    // Back 30 Checkbox.
    $form['enable_back30'] = array(
      '#type'           => 'checkbox',
      '#title'          => t('Enable Back 30'),
      '#description'    => t('Enable the go back 30 seconds button'),
      '#default_value'  => $this->getSetting('enable_back30'),
    );
    // Combo Speed Checkbox.
    $form['enable_combospeed'] = array(
      '#type'           => 'checkbox',
      '#title'          => t('Enable Combo Speed'),
      '#description'    => t('Enable the combo speed button'),
      '#default_value'  => $this->getSetting('enable_combospeed'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $summary[] = t('Options for HTML5 SimplePlayer', array(
      '@enable_counter'      => $this->getSetting('enable_counter'),
      '@enable_progressbar'  => $this->getSetting('enable_progressbar'),
      '@enable_download'     => $this->getSetting('enable_download'),
      '@enable_back30'       => $this->getSetting('enable_back30'),
      '@enable_combospeed'   => $this->getSetting('enable_combospeed'),
    ));
    return $summary;
  }
}
