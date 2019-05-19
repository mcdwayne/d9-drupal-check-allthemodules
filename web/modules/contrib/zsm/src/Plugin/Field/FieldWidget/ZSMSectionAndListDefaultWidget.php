<?php

namespace Drupal\zsm\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
/**
 * Field widget "zsm_section_and_list_default".
 *
 * @FieldWidget(
 *   id = "zsm_section_and_list_default",
 *   label = @Translation("ZSM Section and List default"),
 *   field_types = {
 *     "zsm_section_and_list",
 *   }
 * )
 */
class ZSMSectionAndListDefaultWidget extends WidgetBase implements WidgetInterface {
    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
        $item =& $items[$delta];
        $element += array(
            '#type' => 'fieldset',
        );
        $element['section'] = array(
            '#title' => t('Section Name'),
            '#type' => 'textfield',
            '#default_value' => isset($item->section) ? $item->section : '',
        );
        $element['list'] = array(
            '#title' => t('List'),
            '#type' => 'textarea',
            '#rows' => '3',
            '#default_value' => isset($item->list) ? $item->list : '',
        );
        return $element;
    }
}