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
 * Plugin implementation of the 'flex_list formatter.
 *
 * Renders the flexfield items as an item list
 *
 * @FieldFormatter(
 *   id = "flex_list",
 *   label = @Translation("HTML List"),
 *   weight = 3,
 *   field_types = {
 *     "flex"
 *   }
 * )
 */
class FlexListFormatter extends FlexFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'list_type' => 'ul'
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $form = parent::settingsForm($form, $form_state);

    $form['list_type'] = [
      '#type' => 'select',
      '#title' => t('List Type'),
      '#options' => [
        'ul' => 'Unordered List',
        'ol' => 'Numbered List',
      ],
      '#default_value' => $this->getSetting('list_type'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $options = [
      'ul' => 'Un-ordered List',
      'ol' => 'Numbered List',
    ];
    $summary[] = t('List type: @type', ['@type' => $options[$this->getSetting('list_type')]]);

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

    $class = Html::cleanCssIdentifier($this->fieldDefinition->get('field_name'));
    $output = [
      '#theme' => [
        'item_list',
        'item_list__flexfield',
        'item_list__' . $this->fieldDefinition->get('field_name'),
      ],
      '#list_type' => $this->getSetting('list_type'),
      '#attributes' => [
        'class' => [$class, $class . '--list']
      ],
    ];

    foreach ($this->getFlexFieldItems() as $name => $flexitem) {
      $output['#items'][] = [
        '#markup' => $flexitem->getLabel() . ': ' . $flexitem->value($item),
        '#wrapper_attributes' => [
          'class' => [$class . '__' . Html::cleanCssIdentifier($name)],
        ]
      ];
    }

    return $output;
  }

}
