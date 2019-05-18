<?php

/**
 * @file
 * Contains \Drupal\extlink_preview\Plugin\Field\FieldFormatter\ExternalLinkPreview.
 */

namespace Drupal\extlink_preview\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\extlink_preview\Preview\GetPreview;

/**
 * Plugin implementation of the 'extlink_preview' formatter.
 *
 * @FieldFormatter(
 *   id = "extlink_preview",
 *   label = @Translation("Attach Link Preview"),
 *   field_types = {
 *     "text_long",
 *     "text_with_summary"
 *   },
 *   settings = {
 *     "show_title" = "Y",
 *     "show_image" = "Y",
 *     "trim_link_description" = "265"
 *   }
 * )
 */

class ExternalLinkPreview extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'show_title' => 'Y',
      'show_image' => 'Y',
      'trim_link_description' => '265'
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['show_title'] = array(
      '#title' => t('Show Link Title'),
      '#type' => 'select',
      '#options' => array(
        'Y' => t('Yes'),
        'N' => t('No'),
      ),
      '#default_value' => $this->getSetting('show_title'),
    );
    $element['show_image'] = array(
      '#title' => t('Show Link Image'),
      '#type' => 'select',
      '#options' => array(
        'Y' => t('Yes'),
        'N' => t('No'),
      ),
      '#default_value' => $this->getSetting('show_image'),
    );
    $element['trim_link_description'] = array(
      '#title' => t('Trim Link Description'),
      '#description' => t('Enter 0 to hide Description'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $this->getSetting('trim_link_description'),
      '#element_validate' => array('_element_validate_integer_positive'),
      '#required' => TRUE,
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $help_string = t('Choose settings for external link preview');
    $summary[] = $help_string;
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {

    $element = array();
    $settings = array();
    $settings['show_title'] = $this->getSetting('show_title');
    $settings['show_image'] = $this->getSetting('show_image');
    $settings['trim_link_description'] = $this->getSetting('trim_link_description');

    foreach ($items as $delta => $item) {
      $output = $item->processed;
      $preview = new GetPreview();
      $extlink_preview = $preview->extlink_preview_get_preview($output, $settings);
        if (isset($extlink_preview)) {
          $output1 = array(
            '#theme' => 'extlink_preview',
            '#title' => $extlink_preview['title'],
            '#url' => $extlink_preview['url'],
            '#description' => $extlink_preview['description'],
            '#image' => $extlink_preview['image'],
            '#attached' => array(
             'library' =>  array(
               'extlink_preview/extlink_preview'
              ),
            ),
          );
        }
      $out = drupal_render($output1);
      $element[$delta] = array('#markup' => $output.$out);
    }
    return $element;
  }
}
