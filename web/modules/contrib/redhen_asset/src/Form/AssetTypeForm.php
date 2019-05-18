<?php

/**
 * @file
 * Contains \Drupal\redhen_asset\Form\AssetTypeForm.
 */

namespace Drupal\redhen_asset\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AssetTypeForm.
 *
 * @package Drupal\redhen_asset\Form
 */
class AssetTypeForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $redhen_asset_type = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $redhen_asset_type->label(),
      '#description' => $this->t("Label for the asset type."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $redhen_asset_type->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\redhen_asset\Entity\AssetType::load',
      ),
      '#disabled' => !$redhen_asset_type->isNew(),
    );

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $redhen_asset_type = $this->entity;
    $status = $redhen_asset_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Asset type.', [
          '%label' => $redhen_asset_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Asset type.', [
          '%label' => $redhen_asset_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($redhen_asset_type->urlInfo('collection'));
  }

}
