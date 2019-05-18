<?php

namespace Drupal\icons\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'icon_select_widget' widget.
 *
 * @FieldWidget(
 *   id = "icon_select_widget",
 *   label = @Translation("Icon select list"),
 *   field_types = {
 *     "list_icon"
 *   },
 *   multiple_values = TRUE
 * )
 */
class IconSelectWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#type'] = 'icon_select';

    $element += [
      '#attached' => [
        'library' => [
          'icons/icon_picker',
        ],
      ],
    ];

    return $element;
  }

}
