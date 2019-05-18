<?php

namespace Drupal\blockgroup\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BlockGroupContentForm.
 *
 * @package Drupal\blockgroup\Form
 */
class BlockGroupContentForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $block_group_content = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $block_group_content->label(),
      '#description' => $this->t("Label for the Block group content."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $block_group_content->id(),
      '#machine_name' => [
        'exists' => '\Drupal\blockgroup\Entity\BlockGroupContent::load',
      ],
      '#disabled' => !$block_group_content->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $block_group_content = $this->entity;
    $status = $block_group_content->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Block group content.', [
          '%label' => $block_group_content->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Block group content.', [
          '%label' => $block_group_content->label(),
        ]));
    }
    $form_state->setRedirectUrl($block_group_content->toUrl('collection'));
  }

}
