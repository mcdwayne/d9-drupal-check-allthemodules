<?php

namespace Drupal\cards\Plugin\Field\FieldWidget;





use Drupal\entityreference_view_mode\Plugin\Field\FieldWidget\EntityReferenceViewModeFieldWidgetTrait;

trait CardWidgetTrait {

  use EntityReferenceViewModeFieldWidgetTrait;

  public function cardOptions($items,
                              $delta,
                              $element,
                              &$form,
                              $form_state) {


    // Figure out what has been selected in the widget so far.
    $selections = $this->getSelections($items, $delta, $element, $form, $form_state);

    // If no view mode is selected then dont add the card options.
    if (empty($selections['view_mode'])) {
      return $element;
    }

    // Build some context to pass to the alter functions
    $context = [
      'items' => $items,
      'delta' => $delta,
      'element' => $element,
      'form' => $form,
      'form_state' => $form_state,
      'target_type' => $selections['target_type'],
      'view_mode' => $selections['view_mode'],
      'content' => $selections['content'],
      'bundle' => $selections['target_type'],
    ];

    $available_colors = $this->getFieldSetting('settings')[$selections['target_type']]['group_1'];
    \Drupal::moduleHandler()->alter('card_group_1', $available_colors, $context);

    $available_widths = $this->getFieldSetting('settings')[$selections['target_type']]['group_2'];
    \Drupal::moduleHandler()->alter('card_group_2', $available_widths, $context);

    $available_heights = $this->getFieldSetting('settings')[$selections['target_type']]['group_3'];
    \Drupal::moduleHandler()->alter('card_group_3', $available_heights, $context);

    $available_icons = $this->getFieldSetting('settings')[$selections['target_type']]['group_4'];
    \Drupal::moduleHandler()->alter('card_group_4', $available_icons, $context);

    $available_classes = $this->getFieldSetting('settings')[$selections['target_type']]['adhoc'];
    \Drupal::moduleHandler()->alter('card_adhoc', $available_classes, $context);


    $color = isset($items[$delta]->group_1) ? $items[$delta]->group_1 : '';
    if (count($available_colors)) {
      $element['group_1'] = [
        '#type' => 'select',
        '#title' => t('group_1'),
        '#title_display' => 'hidden',
        '#default_value' => $color,
        '#options' => $available_colors,
      ];

    }
    else {
      // Hidden color field.
      $element['group_1'] = [
        '#type' => 'hidden',
        '#default_value' => '',
      ];
    }

    $width = isset($items[$delta]->group_2) ? $items[$delta]->group_2 : '';

    if (count($available_widths)) {

      // Width.
      $element['group_2'] = [
        '#type' => 'select',
        '#title' => t('group_2'),
        '#title_display' => 'hidden',
        '#default_value' => $width,
        '#options' => $available_widths,
      ];

    }
    else {
      // Hidden width field.
      $element['group_2'] = [
        '#type' => 'hidden',
        '#default_value' => '',
      ];

    }

    $height = isset($items[$delta]->group_3) ? $items[$delta]->group_3 : '';

    if (count($available_heights)) {

      // Height.
      $element['group_3'] = [
        '#type' => 'select',
        '#title' => t('group_3'),
        '#title_display' => 'hidden',
        '#default_value' => $height,
        '#options' => $available_heights,
      ];
    }
    else {

      // Hidden height field.
      $element['group_3'] = [
        '#type' => 'hidden',
        '#default_value' => '',
      ];
    }

    $icon = isset($items[$delta]->group_4) ? $items[$delta]->group_4 : '';

    if (count($available_icons)) {

      // Height.
      $element['group_4'] = [
        '#type' => 'select',
        '#title' => t('group_4'),
        '#title_display' => 'hidden',
        '#default_value' => $icon,
        '#options' => $available_icons,
      ];
    }
    else {

      // Hidden icon field.
      $element['group_4'] = [
        '#type' => 'hidden',
        '#default_value' => '',
      ];
    }

    $classes = isset($items[$delta]->classes) ? $items[$delta]->classes : [];
    $element['adhoc'] = [
      '#type' => 'checkboxes',
      '#title_display' => 'attribute',
      '#default_value' => $classes,
      '#options' => $available_classes,
    ];
    return $element;
  }
}

