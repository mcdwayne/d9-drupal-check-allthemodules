<?php

namespace Drupal\widget_engine\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\inline_entity_form\Form\EntityInlineForm;

/**
 * Node inline form handler.
 */
class WidgetInlineForm extends EntityInlineForm {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels() {
    $labels = [
      'singular' => $this->t('widget'),
      'plural' => $this->t('widgets'),
    ];
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableFields($bundles) {
    $fields = parent::getTableFields($bundles);

    $fields['wid'] = [
      'type' => 'field',
      'label' => $this->t('ID'),
      'weight' => -1,
    ];
    $fields['widget_preview'] = [
      'type' => 'field',
      'label' => $this->t('Preview'),
      'weight' => 0,
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    $entity_form = parent::entityForm($entity_form, $form_state);
    // Remove the "Revision log" textarea,  it can't be disabled in the
    // form display and doesn't make sense in the inline form context.
    $entity_form['revision_log']['#access'] = FALSE;

    return $entity_form;
  }

}
