<?php

namespace Drupal\html_head_meta_and_link\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class HtmlHeadMetaAndLinkEntityForm.
 */
class HtmlHeadMetaAndLinkEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $html_head_meta_and_link_entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $html_head_meta_and_link_entity->label(),
      '#description' => $this->t("Label for the Html head meta and link entity."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $html_head_meta_and_link_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\html_head_meta_and_link\Entity\HtmlHeadMetaAndLinkEntity::load',
      ],
      '#disabled' => !$html_head_meta_and_link_entity->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $html_head_meta_and_link_entity = $this->entity;
    $status = $html_head_meta_and_link_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Html head meta and link entity.', [
          '%label' => $html_head_meta_and_link_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Html head meta and link entity.', [
          '%label' => $html_head_meta_and_link_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($html_head_meta_and_link_entity->toUrl('collection'));
  }

}
