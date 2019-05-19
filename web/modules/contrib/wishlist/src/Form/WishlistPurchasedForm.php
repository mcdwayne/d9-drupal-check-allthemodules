<?php

/**
 * @file
 * Contains \Drupal\wishlist\Form\WishlistPurchasedForm.
 */

namespace Drupal\wishlist\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WishlistPurchasedForm.
 *
 * @package Drupal\wishlist\Form
 */
class WishlistPurchasedForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $wishlist_purchased = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $wishlist_purchased->label(),
      '#description' => $this->t("Label for the Wishlist purchased."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $wishlist_purchased->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\wishlist\Entity\WishlistPurchased::load',
      ),
      '#disabled' => !$wishlist_purchased->isNew(),
    );

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $wishlist_purchased = $this->entity;
    $status = $wishlist_purchased->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Wishlist purchased.', [
          '%label' => $wishlist_purchased->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Wishlist purchased.', [
          '%label' => $wishlist_purchased->label(),
        ]));
    }
    $form_state->setRedirectUrl($wishlist_purchased->urlInfo('collection'));
  }

}
