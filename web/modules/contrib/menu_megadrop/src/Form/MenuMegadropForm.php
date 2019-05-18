<?php

namespace Drupal\menu_megadrop\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Menu megadrop edit forms.
 *
 * @ingroup menu_megadrop
 */
class MenuMegadropForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\menu_megadrop\Entity\MenuMegadrop */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Menu megadrop.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Menu megadrop.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.menu_megadrop.canonical', ['menu_megadrop' => $entity->id()]);
  }

}
