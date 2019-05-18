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
 * Plugin implementation of the 'flex_table' formatter.
 *
 * Formats the flexfield items as an html table.
 *
 * @FieldFormatter(
 *   id = "flex_table",
 *   label = @Translation("Table"),
 *   weight = 2,
 *   field_types = {
 *     "flex"
 *   }
 * )
 */
class FlexTableFormatter extends FlexFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'label_display' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Flexfield items will be rendered as a table.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];
    $component = Html::cleanCssIdentifier($this->fieldDefinition->get('field_name'));
    $flexitems = $this->getFlexFieldItems();
    $header = [];
    foreach ($flexitems as $flexitem) {
      $header[] = $flexitem->getLabel();
    }

    $wrapper_id = 'flexfield-settings-wrapper';

    // Jam the whole table in the first row since we're rendering the main field
    // items as table rows.
    $elements[0] = [
      '#theme' => 'table',
      '#header' => $header,
      '#attributes' => [
        'class' => [$component]
      ],
      '#rows' => [],
    ];

    // Build the table rows and columns.
    foreach ($items as $delta => $item) {
      $elements[0]['#rows'][$delta]['class'][] = $component . '__item';
      foreach ($flexitems as $name => $flexitem) {
        $elements[0]['#rows'][$delta]['data'][$name] = [
          'data' => $flexitem->value($item),
          'class' => [$component . '__' . Html::cleanCssIdentifier($name)],
        ];
      }
    }

    return $elements;
  }

}
