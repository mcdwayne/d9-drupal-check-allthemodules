<?php

namespace Drupal\field_group_ajaxified_multipage\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Plugin implementation of the 'multipage' formatter.
 *
 * @FieldGroupFormatter(
 *   id = "multipage_group",
 *   label = @Translation("Multipage group"),
 *   description = @Translation("Turns javascript multipage groups to ajaxified version."),
 *   supported_contexts = {
 *     "form",
 *     "view",
 *   }
 * )
 */
class MultipageGroup extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {

    $element += [
      '#type' => 'multipage_group',
      '#title' => Html::escape($this->t('@label', ['@label' => $this->getLabel()])),
      '#pre_render' => [],
      '#attributes' => [],
    ];

    if ($this->getSetting('description')) {
      $element += [
        '#description' => $this->getSetting('description'),
      ];

    }

    if ($this->getSetting('id')) {
      $element['#id'] = Html::getId($this->getSetting('id'));
    }

    $classes = $this->getClasses();
    if (!empty($classes)) {
      $element['#attributes'] += ['class' => $classes];
    }

    if ($this->getSetting('required_fields')) {
      $element['#attached']['library'][] = 'field_group/formatter.fieldset';
      $element['#attached']['library'][] = 'field_group/core';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $form = parent::settingsForm();

    $options = [
      t('None'),
      t('Label only'),
      t('Step 1 of 10'),
      t('Step 1 of 10 [Label]'),
    ];
    $form['page_header'] = [
      '#title' => t('Format page title'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('page_header'),
      '#options' => $options,
      '#weight' => 20,
    ];

    $options = [
      t('No'),
      t('Format 1 / 10'),
      t('The count number only'),
    ];
    $form['page_counter'] = [
      '#title' => t('Add a page counter at the bottom'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('page_counter'),
      '#options' => $options,
      '#weight' => 21,
    ];

    $form['move_button'] = [
      '#title' => t('Move submit button to last multipage'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('move_button'),
      '#weight' => 22,
      '#options' => [
        t('No'),
        t('Yes'),
      ],
    ];

    $form['ajaxify'] = [
      '#title' => t('Ajaxify'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('ajaxify'),
      '#weight' => 23,
      '#options' => [0 => t('No'), 1 => t('Yes')],
      '#description' => t('If enabled navigation to next/prev pages will be done using ajax instead of simple javascript'),
    ];
    $form['nonjs_multistep'] = [
      '#title' => t('Non Javascript Multistep'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('nonjs_multistep'),
      '#weight' => 24,
      '#options' => [0 => t('No'), 1 => t('Yes')],
      '#description' => t('If enabled and ajaxify option is disabled no javascript will be used for form submision or navigration between steps, the form will be refreshed. useful for debugging or very complex multistep forms'),
      '#states' => [
        'visible' => [
          ':input[name*="[settings][ajaxify]"]' => ['value' => 1],
        ],
      ],
    ];

    $form['scroll_top'] = [
      '#title' => t('Scroll to top'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('scroll_top'),
      '#weight' => 25,
      '#options' => [
        t('No'),
        t('Yes'),
      ],
      '#description' => t('Scroll to the top of the page on step change.'),
      '#states' => [
        'visible' => [
          ':input[name*="[settings][ajaxify]"]' => ['value' => 1],
        ],
      ],
    ];

    $form['button_label'] = [
      '#title' => t('Change button label?'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('button_label'),
      '#weight' => 26,
      '#states' => [
        'visible' => [
          ':input[name*="[settings][ajaxify]"]' => ['value' => 1],
        ],
      ],
    ];

    $form['button_label_next'] = [
      '#title' => t('Button label next'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('button_label_next'),
      '#weight' => 27,
      '#states' => [
        'visible' => [
          ':input[name*="[settings][ajaxify]"]' => ['value' => 1],
          ':input[name*="[settings][button_label]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['button_label_prev'] = [
      '#title' => t('Button label prev'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('button_label_prev'),
      '#weight' => 28,
      '#states' => [
        'visible' => [
          ':input[name*="[settings][ajaxify]"]' => ['value' => 1],
          ':input[name*="[settings][button_label]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = parent::settingsSummary();

    if ($this->getSetting('required_fields')) {
      $summary[] = $this->t('Mark as required');
    }

    if ($this->getSetting('description')) {
      $summary[] = $this->t(
        'Description : @description',
        ['@description' => $this->getSetting('description')]
      );
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = [
      'description' => '',
      'move_button' => 1,
      'ajaxify' => 1,
      'nonjs_multistep' => 0,
      'scroll_top' => 0,
      'button_label' => 0,
    ] + parent::defaultSettings($context);

    if ($context == 'form') {
      $defaults['required_fields'] = 1;
    }

    return $defaults;
  }

}
