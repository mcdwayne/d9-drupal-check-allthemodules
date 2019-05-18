<?php

namespace Drupal\icons\Element;

use Drupal\Core\Form\OptGroup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Select;

/**
 * Provides a form element for a drop-down menu to select an icon.
 *
 * Usage example:
 * @code
 * $form['icon_select'] = [
 *   '#type' => 'icon_select',
 *   '#title' => $this->t('Select element'),
 *   '#options' => [
 *     '1' => $this->t('One'),
 *     '2' => [
 *       '2.1' => $this->t('Two point one'),
 *       '2.2' => $this->t('Two point two'),
 *     ],
 *     '3' => $this->t('Three'),
 *   ],
 * ];
 * @endcode
 *
 * @FormElement("icon_select")
 */
class IconSelect extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return array(
      '#input' => TRUE,
      '#theme' => 'icon_select',
      '#theme_wrappers' => array('form_element'),
      '#process' => array(
        array($class, 'processIconSelect'),
      ),
    );
  }

  /**
   * Processes a icon select list form element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processIconSelect(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $items = [];

    $element['#attached']['library'] = 'icons/icon_picker';

    $element['#icon_select'] = [
      '#type' => 'select',
      '#required' => $element['#required'],
      '#name' => $element['#name'],
      '#id' => $element['#id'],
      '#attributes' => [
        'name' => $element['#name'],
      ],
    ];

    // If the element is set to #required through #states, override the
    // element's #required setting.
    $required = isset($element['#states']['required']) ? TRUE : $element['#required'];
    // If the element is required and there is no #default_value, then add an
    // empty option that will fail validation, so that the user is required to
    // make a choice. Also, if there's a value for #empty_value or
    // #empty_option, then add an option that represents emptiness.
    if ($required) {
      $element['#required'] = $required;
    }

    $element += [
      '#empty_value' => '',
      '#empty_option' => $required ? t('- Select -') : t('- None -'),
    ];

    $element['#icon_select']['#empty_value'] = $element['#empty_value'];
    $element['#icon_select']['#empty_option'] = $element['#empty_option'];

    // The empty option is prepended to #options and purposively not merged
    // to prevent another option in #options mistakenly using the same value
    // as #empty_value.
    $empty_option = array($element['#icon_select']['#empty_value'] => $element['#icon_select']['#empty_option']);
    $element['#options'] = $empty_option + $element['#options'];
    $element['#icon_select']['#options'] = $element['#options'];

    $element['#icon_picker'] = [
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#attributes' => $element['#attributes'],
    ];

    foreach ($element['#options'] as $key => $data) {
      if (is_array($data)) {
        $items[$key] = [
          '#markup' => $key,
          '#wrapper_attributes' => [
            'class' => [
              'icon-set',
            ],
          ],
          'children' => [],
        ];

        foreach ($data as $icon_id => $icon_label) {
          $items[$key]['children'][] = self::buildListItem($element, $icon_id, $icon_label);
        }
      }
      else {
        $icon_id = $key;
        $icon_label = $data;
        $items[] = self::buildListItem($element, $icon_id, $icon_label);
      }
    }

    $element['#icon_picker']['#items'] = $items;
    $element['#options'] = OptGroup::flattenOptions($element['#options']);

    return $element;
  }

  /**
   * Build a list item for the custom dropdown to pick an icon.
   *
   * @param array $element
   *   The form element to process.
   * @param string $icon_id
   *   Id of the icon to build a list item for.
   * @param string $icon_label
   *   Label which represents the textual representation of the icon.
   *
   * @return array
   *   List item to add to the item list.
   */
  protected static function buildListItem(array $element, $icon_id, $icon_label) {
    $option = [
      'icon' => [
        '#type' => 'icon',
        '#icon_id' => $icon_id,
        '#title' => $icon_label,
      ],
      'label' => [
        '#prefix' => '<span class="icons-select__label">',
        '#suffix' => '</span>',
        '#type' => 'markup',
        '#markup' => $icon_label,
      ],
    ];

    $option = \Drupal::service('renderer')
      ->render($option);

    $item = [
      '#markup' => $option,
      '#wrapper_attributes' => [
        'data-icon-id' => $icon_id,
        'class' => [
          'icons-select__item',
        ],
      ],
    ];

    if ($element['#default_value'] == $icon_id) {
      $item['#wrapper_attributes']['class'][] = 'selected';
    }

    return $item;
  }

}
