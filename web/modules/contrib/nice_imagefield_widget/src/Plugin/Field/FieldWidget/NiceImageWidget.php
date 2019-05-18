<?php

/**
 * @file
 * Contains
 *     \Drupal\nice_imagefield_widget\Plugin\Field\FieldWidget\NiceImageWidget.
 */

namespace Drupal\nice_imagefield_widget\Plugin\Field\FieldWidget;

use Drupal\image\Plugin\Field\FieldWidget\ImageWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'nice_image_widget' widget.
 *
 * @FieldWidget(
 *   id = "nice_image_widget",
 *   label = @Translation("Nice Image Widget"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class NiceImageWidget extends ImageWidget
{
    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state)
    {
        $element = parent::settingsForm($form, $form_state);

        $element['preview_image_style'] = array(
            '#title' => t('Grid image style'),
            '#type' => 'select',
            '#options' => image_style_options(FALSE),
            '#required' => TRUE,
            '#default_value' => $this->getSetting('preview_image_style'),
            '#description' => t('The preview image will be shown while editing the content. Recommended use preview image style with dimension more than 220x220.'),
            '#weight' => 15,
        );

        return $element;
    }

    /**
     * Overrides
     * \Drupal\file\Plugin\Field\FieldWidget\FileWidget::formMultipleElements().
     *
     * Special handling for draggable multiple widgets and 'add more' button.
     *
     * @param FieldItemListInterface $items
     * @param array $form
     * @param FormStateInterface $form_state
     * @return array
     */
    protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state)
    {
        $elements = parent::formMultipleElements($items, $form, $form_state);
        $elements['#theme'] = 'nice_imagefield_widget_multiple';
        $elements['#attached']['library'][] = 'nice_imagefield_widget/sortable';

        return $elements;
    }
}
