<?php

namespace Drupal\headline_group\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\headline_group\HeadlineGroupItemInterface;

abstract class BaseHeadlineWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $parentTitle = $this->getParentTitle($form, $form_state);
    $currentTitle = isset($items[$delta]->headline)? $items[$delta]->headline : $parentTitle;
    $headline_default = isset($items[$delta]->headline) ? $items[$delta]->headline : NULL;

    switch ($this->titleBehavior()) {
      case HeadlineGroupItemInterface::HG_OVERRIDE:
        $headline_placeholder = (empty($currentTitle)) ? $this->t('By default, use the parent entity title') : $currentTitle;
        $headline_disabled = FALSE;
        $headline_description = "If you do not provide a headline, the parent entity title will be used instead.";
        break;

      case HeadlineGroupItemInterface::HG_PROHIBIT:
        $headline_default = (empty($parentTitle)) ? NULL : $parentTitle;
        $headline_placeholder = ($headline_default) ? $headline_default : $this->t('Use the parent entity title.');
        $headline_disabled = TRUE;
        $headline_description = "The headline is automatically provided by the parent entity title.";
        break;

      default:
        // Inc. HG_BLANK.
        $headline_placeholder = $this->t('The main headline');
        $headline_disabled = FALSE;
        $headline_description = NULL;
    }

    $element['headline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Headline'),
      '#placeholder' => $headline_placeholder,
      '#default_value' => $headline_default,
      '#maxlength' => 255,
      '#disabled' => $headline_disabled,
      '#description' => $headline_description,
    ];

    return $element;

  }

  /**
   * Modify headline based on configuration.
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $title_behavior = $this->titleBehavior();
    if ($title_behavior == HeadlineGroupItemInterface::HG_BLANK) {
      // no special title handling
      return $values;
    }
    $parent_title = $this->getParentTitle($form, $form_state);
    
    foreach ($values as $delta => $val) {
      switch ($title_behavior) {
        case HeadlineGroupItemInterface::HG_OVERRIDE:
          if (empty($values[$delta]['headline'])) {
            $values[$delta]['headline'] = $parent_title;
          }
          break;

        case HeadlineGroupItemInterface::HG_PROHIBIT:
          $values[$delta]['headline'] = $parent_title;
          break;

      }
    }
    return $values;
  }

  /**
   * Indicates enabled support for superheads.
   *
   * @return bool
   *   Returns TRUE if the HeadlineGroupItem field is configured to support
   *   superheads, otherwise FALSE.
   */
  protected function supportsSuperhead() {
    $support = $this->getFieldSetting('include_superhead');
    return (bool) (HeadlineGroupItemInterface::HG_SUPERHEAD === $support);
  }

  /**
   * Indicates enabled support for subheads.
   *
   * @return bool
   *   Returns TRUE if the HeadlineGroupItem field is configured to support
   *   subheads, otherwise FALSE.
   */
  protected function supportsSubhead() {
    $support = $this->getFieldSetting('include_subhead');
    return (bool) (HeadlineGroupItemInterface::HG_SUBHEAD === $support);
  }

  /**
   * Returns the preference for headline treatment.
   */
  protected function titleBehavior() {
    return $this->getFieldSetting('title_behavior');
  }

  /**
   * Figure out the title field of the parent entity and return it if available.
   */
  protected function getParentTitle(array $form, FormStateInterface $form_state) {
    $label_field = FALSE;
    $title = '';

    if (isset($form['#entity'])) {
      // This is an IEF subform -- look up the keys for the entity.
      $entity = $form['#entity'];
      if ($entity instanceof ContentEntityInterface) {
        if ($entity->getEntityType()->hasKey('label')) {
          $label_field = $entity->getEntityType()->getKey('label');
          if ($entity->hasField($label_field)) {
            $title_vals = $entity->get($label_field);
            if ($title_vals && $title_vals->first()) {
              $title = $title_vals->first()->value;
            }
          }
        }
      }
    }
    else {
      // Get the label field from the form object.
      $entity = $form_state->getFormObject()->getEntity();
      if ($entity instanceof ContentEntityInterface) {
        if ($entity->getEntityType()->hasKey('label')) {
          $label_field = $entity->getEntityType()->getKey('label');
          if ($form_state->isSubmitted()) {
            // Get the parent title from the form state.
            $title_vals = $form_state->getValue($label_field);
            if (isset($title_vals[0]['value'])) {
              $title = $title_vals[0]['value'];
            }
          }
          else {
            // Get the parent title from the entity. 
            if ($entity->hasField($label_field)) {
              $title_vals = $entity->get($label_field);
              if ($title_vals && $title_vals->first()) {
                $title = $title_vals->first()->value;
              }
            }
          }
        }
      }
    }
    return $title;
  }

}
