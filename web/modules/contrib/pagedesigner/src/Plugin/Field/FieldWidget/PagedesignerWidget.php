<?php
namespace Drupal\pagedesigner\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the Pagedesigner content widget.
 *
 * @FieldWidget(
 *   id = "pagedesigner_widget",
 *   module = "pagedesigner",
 *   label = @Translation("Pagedesigner content"),
 *   field_types = {
 *     "pagedesigner_item"
 *   }
 * )
 */
class PagedesignerWidget extends WidgetBase
{

    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
    {
        return [];
        $entity = \Drupal::routeMatch()->getParameter('node');
        return [
            '#type' => 'inline_template',
            '#template' => '<section class="pd-content">{{markup}}</section>',
            '#context' => ['markup' => ['#markup' => \Drupal::service('pagedesigner.service.renderer')->render($entity)]],
        ];
    }

    /**
     * Validate the color text field.
     */
    public static function validate($element, FormStateInterface $form_state)
    {

    }

}
