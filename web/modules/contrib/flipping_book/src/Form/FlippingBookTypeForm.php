<?php

namespace Drupal\flipping_book\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\flipping_book\FlippingBookInterface;

/**
 * Class FlippingBookTypeForm.
 *
 * @package Drupal\flipping_book\Form
 */
class FlippingBookTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $flipping_book_type = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $flipping_book_type->label(),
      '#description' => $this->t("Label for the Flipping Book type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $flipping_book_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\flipping_book\Entity\FlippingBookType::load',
      ],
      '#disabled' => !$flipping_book_type->isNew(),
    ];

    $location = $flipping_book_type->get('location');
    $form['location'] = [
      '#type' => 'radios',
      '#title' => $this->t('Import location'),
      '#options' => $this->getImportLocations(),
      '#description' => $this->t('Choose where you want to import your flipping books.'),
      '#required' => TRUE,
      '#default_value' => $location,
      '#disabled' => !empty($location),
    ];

    return $form;
  }

  /**
   * Get import locations.
   *
   * @return array
   *   An array of available import locations.
   */
  public function getImportLocations() {
    $locations = [
      FlippingBookInterface::FLIPPING_BOOK_PUBLIC => $this->t('Public folder'),
    ];

    if (!empty(PrivateStream::basePath())) {
      $locations[FlippingBookInterface::FLIPPING_BOOK_PRIVATE] = $this->t('Private folder');
    }

    return $locations;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $flipping_book_type = $this->entity;
    $status = $flipping_book_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Flipping Book type.', [
          '%label' => $flipping_book_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Flipping Book type.', [
          '%label' => $flipping_book_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($flipping_book_type->urlInfo('collection'));
  }

}
