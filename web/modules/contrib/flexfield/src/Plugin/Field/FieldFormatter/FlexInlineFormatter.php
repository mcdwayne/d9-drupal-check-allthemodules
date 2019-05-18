<?php

namespace Drupal\flexfield\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flexfield\Plugin\FlexFieldTypeManager;
use Drupal\flexfield\Plugin\FlexFieldTypeManagerInterface;
use Drupal\flexfield\Plugin\Field\FieldFormatter\FlexFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'flex_inline' formatter.
 *
 * Renders the flexfield items inline using a simple separator and no additional
 * wrapper markup.
 *
 * @FieldFormatter(
 *   id = "flex_inline",
 *   label = @Translation("Inline"),
 *   weight = 1,
 *   field_types = {
 *     "flex"
 *   }
 * )
 */
class FlexInlineFormatter extends FlexFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_labels' => FALSE,
      'label_separator' => ': ',
      'item_separator' => ', ',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $form = parent::settingsForm($form, $form_state);
    $id = 'flexfield-show-labels';

    $form['show_labels'] = [
      '#type' => 'checkbox',
      '#title' => t('Show Labels?'),
      '#default_value' => $this->getSetting('show_labels'),
      '#attributes' => [
        'data-id' => $id,
      ],
    ];

    $form['label_separator'] = [
      '#type' => 'textfield',
      '#title' => t('Label Separator'),
      '#default_value' => $this->getSetting('label_separator'),
      '#states' => [
        'visible' => [
          ':input[data-id="' . $id . '"]' => ['checked' => TRUE],
        ]
      ],
    ];

    $form['item_separator'] = [
      '#type' => 'textfield',
      '#title' => t('Label Separator'),
      '#default_value' => $this->getSetting('item_separator'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Show labels: @show_labels', ['@show_labels' => $this->getSetting('label_display') ? 'Yes' : 'No']);
    if ($this->getSetting('label_display')) {
      $summary[] = t('Label Separator: @sep', ['@sep' => $this->getSetting('label_separator')]);
    }
    $summary[] = t('Item Separator: @sep', ['@sep' => $this->getSetting('item_separator')]);

    return $summary;
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

    $output = [];

    foreach ($this->getFlexFieldItems() as $name => $flexitem) {
      if ($this->getSetting('show_labels')) {
        $output[] = implode($this->getSetting('label_separator'), [
          $flexitem->getLabel(),
          $flexitem->value($item),
        ]);
      }
      else {
        $output[] = $flexitem->value($item);
      }
    }

    return ['#markup' => implode($this->getSetting('item_separator'), $output)];
  }

}
