<?php

namespace Drupal\link_partners\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\text\Plugin\Field\FieldFormatter\TextTrimmedFormatter;
use Drupal\link_partners\vendor\Sape\SAPE_context;

/**
 * Plugin implementation of the 'context_links' formatter.
 *
 * @FieldFormatter(
 *   id = "context_links",
 *   label = @Translation("Context link"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class ContextFormater extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary[] = $this->t('You use a @partner context', [
      '@partner' => $this->getSetting('partner'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $context = [];
    $config = \Drupal::config('link_partners.settings');
    $partner = $this->getSetting('partner');

    switch ($partner) {
      case 'sape':
        if (!defined('_SAPE_USER')) {
          define('_SAPE_USER', $config->get('sape.id'));
        }

        $context = SAPE_context::getInstance([
          'charset' => 'UTF-8',
          'multi_site' => TRUE,
          'show_counter_separately' => TRUE,
          'force_show_code' => $config->get('sape.debug'),
        ]);
        break;
    }

    // Trim functions.
    $render_as_summary = function (&$element) {
      // Make sure any default #pre_render callbacks are set on the element,
      // because text_pre_render_summary() must run last.
      $element += \Drupal::service('element_info')
        ->getInfo($element['#type']);
      // Add the #pre_render callback that renders the text into a summary.
      $element['#pre_render'][] = [
        TextTrimmedFormatter::class,
        'preRenderSummary',
      ];
      // Pass on the trim length to the #pre_render callback via a property.
      $element['#text_summary_trim_length'] = $this->getSetting('trim_length');
    };

    foreach ($items as $delta => $item) {
      // The ProcessedText element already handles cache context & tag bubbling.
      // @see \Drupal\filter\Element\ProcessedText::preRenderText()
      $elements[$delta] = [
        '#type' => 'processed_text',
        '#text' => $item->value,
        '#format' => $item->format,
        '#langcode' => $item->getLangcode(),
      ];

      if ($this->getSetting('summary') === TRUE) {

        if ($this->getPluginId() == 'context_links' && !empty($item->summary)) {
          $elements[$delta]['#text'] = $item->summary;
        }
        else {
          $elements[$delta]['#text'] = $item->value;
          $render_as_summary($elements[$delta]);
        }

      }

      $context = $context->replace_in_text_segment($elements[$delta]['#text']);
      $elements[$delta]['#text'] = empty($context) ? $elements[$delta]['#text'] : $context;

    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'partner' => 'sape',
        'summary' => FALSE,
        'trim_length' => 600,
      ] + parent::defaultSettings();
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element = [];

    $element['partners'] = [
      '#type' => 'select',
      '#title' => $this->t('Partner'),
      '#description' => $this->t('Select partner of context links'),
      '#default_value' => $this->getSetting('partner'),
      '#options' => [
        'sape' => 'Sape',
      ],
    ];
    $element['summary'] = [
      '#title' => $this->t('Summary or trimmed'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('summary'),
    ];
    $element['trim_length'] = [
      '#title' => $this->t('Length'),
      '#type' => 'number',
      '#description' => $this->t('To crop text, specify the number of valid characters.'),
      '#default_value' => $this->getSetting('trim_length'),
      '#min' => 1,
      '#states' => [
        'visible' => [
          ':input[name="fields[body][settings_edit_form][settings][summary]"]' => [
            'checked' => TRUE,
          ],
        ],
        'invisible' => [
          ':input[name="fields[body][settings_edit_form][settings][summary]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    return $element;
  }

}
