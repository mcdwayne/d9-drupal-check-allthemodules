<?php

namespace Drupal\soundcloudfield\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'soundcloud_link' formatter.
 *
 * @FieldFormatter(
 *   id = "soundcloud_link",
 *   module = "soundcloudfield",
 *   label = @Translation("Link to SoundCloud URI"),
 *   field_types = {
 *     "soundcloud"
 *   }
 * )
 */
class SoundCloudLinkFormatter extends FormatterBase {
  // investigate: extends UriLinkFormatter

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'trim_length' => '80',
      'rel' => '',
      'target' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['trim_length'] = array(
      '#type' => 'number',
      '#title' => $this->t('Trim link text length'),
      '#field_suffix' => $this->t('characters'),
      '#default_value' => $this->getSetting('trim_length'),
      '#min' => 1,
      '#description' => $this->t('Leave blank to allow unlimited link text lengths.'),
    );

    $elements['rel'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Add rel="nofollow" to links'),
      '#return_value' => 'nofollow',
      '#default_value' => $this->getSetting('rel'),
    );

    $elements['target'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Open link in new window'),
      '#return_value' => '_blank',
      '#default_value' => $this->getSetting('target'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $settings = $this->getSettings();

    $summary[] = $this->t('Displays the SoundCloud link.');

    if (!empty($settings['trim_length'])) {
      $summary[] = $this->t('Link text trimmed to @limit characters', array('@limit' => $settings['trim_length']));
    }
    else {
      $summary[] = $this->t('Link text not trimmed');
    }

    if (!empty($settings['rel'])) {
      $summary[] = $this->t('Add rel="@rel"', array('@rel' => $settings['rel']));
    }
    if (!empty($settings['target'])) {
      $summary[] = $this->t('Open link in new window');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {
      if (!$item->isEmpty()) {
        $link_title = $item->url;

        // Trim the link text to the desired length.
        if (!empty($settings['trim_length'])) {
          $link_title = Unicode::truncate($link_title, $settings['trim_length'], FALSE, TRUE);
        }

        $elements[$delta] = array(
          '#type' => 'link',
          '#url' => Url::fromUri($item->url),
          '#title' => $link_title,
          '#options' => array(),
        );

        if (!empty($item->_attributes)) {
          $elements[$delta]['#options'] += array('attributes' => array());
          $elements[$delta]['#options']['attributes'] += $item->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }
      }
    }

    return $elements;
  }

}
