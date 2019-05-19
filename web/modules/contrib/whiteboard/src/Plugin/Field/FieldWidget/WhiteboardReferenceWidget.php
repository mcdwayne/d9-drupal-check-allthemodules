<?php

/**
 * @file
 * Contains \Drupal\whiteboard\Plugin\Field\FieldWidget\WhiteboardReferenceWidget.
 */

namespace Drupal\whiteboard\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\whiteboard\Whiteboard;

/**
 * Plugin implementation of the 'whiteboard_reference' widget.
 *
 * @FieldWidget(
 *   id = "whiteboard_reference",
 *   label = @Translation("Whiteboard Reference"),
 *   field_types = {
 *     "whiteboard"
 *   },
 *   settings = {
 *     "placeholder" = ""
 *   }
 * )
 *
 * What about:
 * 'behaviors' => array(
 *   'multiple values' => FIELD_BEHAVIOR_DEFAULT,
 *   'default value' => FIELD_BEHAVIOR_DEFAULT,
 * ),
 */
class WhiteboardReferenceWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();

    $whiteboard = new Whiteboard($items[$delta]->get('wbid')->getValue());

    $element['whiteboard'] = array(
      '#type' => 'fieldset',
      '#title' => 'Whiteboard',
      '#collapsible' => TRUE,
      '#description' => $whiteboard->get('wbid') ? t('Edit whiteboard') : t('Add a whiteboard'),
      'whiteboard_title' => array(
        '#title' => t('Whiteboard title'),
        '#type' => 'textfield',
        '#default_value' => $whiteboard->get('title'),
      ),
      'whiteboard_wbid' => array(
        '#type' => 'value',
        '#value' => $whiteboard->get('wbid'),
      ),
      'whiteboard_marks' => array(
        '#title' => t('Whiteboard Marks'),
        '#type' => 'textfield',
        '#default_value' => $whiteboard->get('marks'),
      ),
    );
    if ($whiteboard->get('wbid')) {
      $element['whiteboard']['whiteboard_delete'] = array(
        '#title' => t('Remove this Whiteboard?'),
        '#type' => 'checkbox',
      );
    }
    if ($formats = filter_formats($user)) {
      $format_options = array();
      foreach ($formats as $format) {
        $format_options[$format->id()] = $format->label();
      }
      $element['whiteboard']['whiteboard_format'] = array(
        '#type' => 'select',
        '#title' => t('Canvas marks output format'),
        '#options' => $format_options,
        '#default_value' => $whiteboard->get('format'),
      );
    }
    return $element;
  }

}
