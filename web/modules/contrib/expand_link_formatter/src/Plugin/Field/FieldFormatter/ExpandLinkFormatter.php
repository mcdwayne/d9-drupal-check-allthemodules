<?php

namespace Drupal\expand_link_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Plugin implementation of the 'expand_link_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "expand_link_formatter",
 *   label = @Translation("Expand link formatter"),
 *   field_types = {
 *     "text_long",
 *     "text_with_summary"
 *   }
 * )
 */
class ExpandLinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      "separator" => "<hr>",
      "expand_link_label" => t("Read more"),
      "collapse_link_label" => t("Read Less"),
      "maxlength" => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      "separator" => [
        '#type' => 'textfield',
        '#title' => $this->t('Separator'),
        '#default_value' => $this->getSetting('separator'),
        '#size' => 60,
        '#maxlength' => 128,
      ],
      "expand_link_label" => [
        '#type' => 'textfield',
        '#title' => $this->t('Expand link label'),
        '#default_value' => $this->getSetting('expand_link_label'),
        '#size' => 60,
        '#maxlength' => 128,
      ],
      "collapse_link_label" => [
        '#type' => 'textfield',
        '#title' => $this->t('Collapse link label'),
        '#default_value' => $this->getSetting('collapse_link_label'),
        '#size' => 60,
        '#maxlength' => 128,
      ],
      "maxlength" => [
        '#type' => 'number',
        '#title' => $this->t('Maximum characters before trimming'),
        '#description' => $this->t('The text can be automatically trimmed and the expand link introduced when the separator is missing. Set to 0 to deactivate. '),
        '#default_value' => $this->getSetting('maxlength'),
      ],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Expand link formatter.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $field_name = $items->getName();

    foreach ($items as $delta => $item) {
      $value = Html::decodeEntities($item->getValue()['value']);
      $separator = $this->getSetting('separator');
      $maxlength = $this->getSetting('maxlength');
      $collapsed = $item->value;
      $expanded = NULL;
      if (strpos($value, $separator) !== FALSE) {
        list($collapsed, $expanded) = explode($separator, $value, 2);
      }
      elseif ($maxlength > 0 && strlen($value) >= $maxlength) {
        $options = [
          'max_length' => $maxlength,
          'word_boundary' => TRUE,
        ];
        // Use the trimming function from the views module.
        $collapsed = FieldPluginBase::trimText($options, $value);
        $expanded = substr($value, strlen($collapsed));
      }

      if ($expanded) {
        $markup = [
          '#theme' => 'expand_link_formatter',
          '#attached' => [
            'library' => ['expand_link_formatter/expand'],
            'drupalSettings' => [
              'expandLinkFormatter' => [
                'expandLinkLabel' => $this->getSetting('expand_link_label'),
                'collapseLinkLabel' => $this->getSetting('collapse_link_label'),
              ]
            ]
          ],
          '#expanded' => [
            '#type' => 'processed_text',
            '#text' => $expanded,
            '#format' => $item->format,
            '#langcode' => $item->getLangcode(),
          ],
          '#collapsed' => [
            '#type' => 'processed_text',
            '#text' => $collapsed,
            '#format' => $item->format,
            '#langcode' => $item->getLangcode(),
          ],
          '#expand_link_label' => t($this->getSetting('expand_link_label')),
          '#collapse_link_label' => t($this->getSetting('collapse_link_label')),
          '#field_name' => $field_name . "_" . $delta,
        ];

        $elements[$delta] = $markup;
      }
      else {
        $elements[$delta] = [
          '#type' => 'processed_text',
          '#text' => $collapsed,
          '#format' => $item->format,
          '#langcode' => $item->getLangcode(),
        ];
      }
    }

    return $elements;
  }

}
