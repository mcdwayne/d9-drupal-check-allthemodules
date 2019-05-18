<?php

namespace Drupal\esm\Plugin\field_group\FieldGroupFormatter;

use Drupal\Component\Utility\Html;
use Drupal\field_group\FieldGroupFormatterBase;

/**
 * Area element.
 *
 * @FieldGroupFormatter(
 *   id = "area",
 *   label = @Translation("Area"),
 *   description = @Translation("Add an area element"),
 *   supported_contexts = {
 *     "form"
 *   }
 * )
 */
class Area extends FieldGroupFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    parent::preRender($element, $rendering_object);

    $element += [
      '#type' => 'html_tag',
      '#title' => Html::escape($this->getLabel()),
      '#tag' => 'div',
      '#attributes' => [
        'id' => $this->getSetting('id'),
        'class' => ['esm-area'],
      ],
    ];

    $classes = $this->getClasses();
    if (!empty($classes)) {
      $element += [
        '#attributes' => ['class' => $classes],
      ];
    }

    if ($this->getSetting('description')) {
      $element += [
        '#description' => $this->getSetting('description'),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = parent::settingsForm();

    $form['toc_entry'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create table of content entry.'),
      '#default_value' => $this->getSetting('toc_entry'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];
    if ($this->getSetting('toc_entry')) {
      $summary[] = $this->t('TOC entry: true');
    }
    else {
      $summary[] = $this->t('TOC entry: false');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    $defaults = [
      'toc_entry' => TRUE,
    ] + parent::defaultSettings($context);

    return $defaults;
  }

}
