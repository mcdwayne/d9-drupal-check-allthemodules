<?php

namespace Drupal\headline_group\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'headline_default' formatter.
 *
 * @FieldFormatter(
 *   id = "headline_default",
 *   module = "headline_group",
 *   label = @Translation("Headline Group (Complete)"),
 *   field_types = {
 *     "headline_group"
 *   }
 * );
 */
class HeadlineDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();
    $summary[] = $this->t('Displays the headline group in a @tag@class@bem.',
      [
        '@tag' => $settings['headline_group_tag'],
        '@class' => $settings['headline_group_class'] ? '.' . $settings['headline_group_class'] : '',
        '@bem' => $settings['headline_group_bem'] ? ' with BEM-style inner classes' : '',
      ]
    );
    if ($settings['headline_group_anchor']) {
      $summary[] = $this->t('Include an ID anchor.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'headline_group_tag' => 'div',
      'headline_group_class' => 'headline-group',
      'headline_group_bem' => TRUE,
      'headline_group_anchor' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['headline_group_tag'] = [
      '#title' => $this->t('Outer tag'),
      '#type' => 'select',
      '#options' => [
        'div' => 'Div',
        'h1' => 'H1',
        'h2' => 'H2',
        'h3' => 'H3',
        'h4' => 'H4',
        'h5' => 'H5',
        'h6' => 'H6',
      ],
      '#default_value' => $this->getSetting('headline_group_tag'),
    ];

    $element['headline_group_class'] = [
      '#title' => $this->t('Class'),
      '#type' => 'textfield',
      '#maxlength' => '64',
      '#default_value' => $this->getSetting('headline_group_class'),
    ];

    $element['headline_group_bem'] = [
      '#title' => $this->t('Use BEM'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('headline_group_bem'),
    ];

    $element['headline_group_anchor'] = [
      '#title' => $this->t('Include ID anchor'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('headline_group_anchor'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();

    foreach ($items as $delta => $item) {

      $head = $item->headline;
      $superhead = $item->superhead;
      $subhead = $item->subhead;

      if (empty($head) && empty($superhead) && empty($subhead)) {
        continue;
      }

      $class = $settings['headline_group_class']? $settings['headline_group_class'] : 'headline-group';

      $attributes = [
        'class' => $class,
      ];

      if (isset($settings['headline_group_anchor']) && $head) {
        $attributes['id'] = Html::cleanCssIdentifier($head);
      }

      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => $settings['headline_group_tag'],
        '#attributes' => $attributes,
      ];

      if (!empty($superhead)) {
        $elements[$delta]['superhead'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'class' => ($settings['headline_group_bem'] ? $class . '__' : '') . 'super',
          ],
          '#value' => $superhead,
        ];
      }

      if (!empty($head)) {
        $elements[$delta]['head'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'class' => ($settings['headline_group_bem'] ? $class . '__' : '') . 'head',
          ],
          '#value' => $head,
        ];
      }

      if (!empty($subhead)) {
        $elements[$delta]['subhead'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'class' => ($settings['headline_group_bem'] ? $class . '__' : '') . 'sub',
          ],
          '#value' => $subhead,
        ];
      }
    }

    return $elements;
  }

}
