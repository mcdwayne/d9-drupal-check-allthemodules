<?php

/**
 * @file
 * Contains \Drupal\colossal_menu\Form\LinkForm.
 */

namespace Drupal\colossal_menu\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\Form\MenuLinkFormInterface;
use Drupal\Core\Menu\MenuLinkInterface;

/**
 * Form controller for Link edit forms.
 *
 * @ingroup colossal_menu
 */
class LinkForm extends ContentEntityForm implements MenuLinkFormInterface {

  /**
   * {@inheritdoc}
   */
  public function setMenuLinkInstance(MenuLinkInterface $menu_link) {
    $this->entity = $menu_link;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(array &$form, FormStateInterface $form_state) {
    return $form_state->getUserInput();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $this->buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    return $this->validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    return $this->submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $link = $this->entity;

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit %label', ['%label' => $link->label()]);
    }

    return parent::form($form, $form_state, $link);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $link = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Link.', [
          '%label' => $link->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Link.', [
          '%label' => $link->label(),
        ]));
    }

    $form_state->setRedirect('entity.colossal_menu.edit_form', [
      'colossal_menu' => $link->getMenuName(),
    ]);
  }

}
