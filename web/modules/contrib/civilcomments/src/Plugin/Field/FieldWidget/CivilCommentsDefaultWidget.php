<?php

namespace Drupal\civilcomments\Plugin\Field\FieldWidget;

use Drupal\civilcomments\Plugin\Field\FieldType\CivilCommentItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'civilcomments_default' widget.
 *
 * @FieldWidget(
 *   id = "civilcomments_default",
 *   label = @Translation("Civil Comments default"),
 *   field_types = {
 *     "civil_comments"
 *   }
 * )
 *
 * @todo
 *   - Implement validation.
 *   - Consider implementing an interface as e.g. Comments does with the options
 *     array.
 */
class CivilCommentsDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['status'] = [
      '#title' => $this->t('Civil Comments status'),
      '#type' => 'radios',
      '#default_value' => isset($items[$delta]->status) ? $items[$delta]->status : NULL,
      '#options' => [
        CivilCommentItemInterface::OPEN => $this->t('Open'),
        CivilCommentItemInterface::DISABLED => $this->t('Disabled'),
      ],
      CivilCommentItemInterface::OPEN => [
        '#description' => $this->t('Users with the "View Civil Comments" permission will be able to view and post comments.'),
      ],
      CivilCommentItemInterface::DISABLED => [
        '#description' => $this->t('Civil Comments will not be displayed.'),
      ],
    ];

    return $element;
  }

}
