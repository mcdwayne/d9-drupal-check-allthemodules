<?php

namespace Drupal\pagedesigner_yoast\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\yoast_seo\Plugin\Field\FieldWidget\YoastSeoWidget;

/**
 * Advanced widget for yoast_seo field.
 *
 * @FieldWidget(
 *   id = "pagedesigner_yoast_seo_widget",
 *   label = @Translation("Real-time SEO form for pagedesigner"),
 *   field_types = {
 *     "yoast_seo"
 *   }
 * )
 */
class PagedesignerYoastSeoWidget extends YoastSeoWidget
{

    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        $form['body']['#prefix'] = '<div class="hidden">';
        $form['body']['#suffix'] = '</div>';
        return parent::formElement($items, $delta, $element, $form, $form_state);
    }
    // /**
    //  * {@inheritdoc}
    //  */
    // public function settingsForm(array $form, FormStateInterface $form_state)
    // {
    //     $element = [];
    //     /** @var EntityFormDisplayInterface $form_display */
    //     $form_display = $form_state->getFormObject()->getEntity();
    //     $entity_type = $form_display->getTargetEntityTypeId();
    //     $bundle = $form_display->getTargetBundle();
    //     $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    //     $text_field_types = ['pagedesigner_item'];
    //     $text_fields = [];

    //     if (empty($fields)) {
    //         return $elements;
    //     }

    //     foreach ($fields as $field_name => $field) {
    //         if (in_array($field->getType(), $text_field_types)) {
    //             $text_fields[$field_name] = $field->getLabel() . ' (' . $field_name . ')';
    //         }
    //     }

    //     $element['body'] = [
    //         '#type' => 'select',
    //         '#title' => $this->t('Body'),
    //         '#required' => true,
    //         '#description' => $this->t('Select a field which is used as the body field.'),
    //         '#options' => $text_fields,
    //         '#default_value' => $this->getSetting('body'),
    //     ];

    //     return $element;
    // }
}
