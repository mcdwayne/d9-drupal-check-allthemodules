<?php

namespace Drupal\product_choice\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Class ProductChoiceListForm.
 *
 * @package Drupal\product_choice\Form
 */
class ProductChoiceListForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $product_choice_list = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $product_choice_list->label(),
      '#description' => $this->t("Label for the Product choice list."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $product_choice_list->id(),
      '#machine_name' => [
        'exists' => '\Drupal\product_choice\Entity\ProductChoiceList::load',
      ],
      '#disabled' => !$product_choice_list->isNew(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#description' => $this->t('This text will be displayed on the <em>Product choice list</em> listing page.'),
      '#default_value' => $product_choice_list->getDescription(),
    ];

    $form['help_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Help Text'),
      '#description' => $this->t('This text will be displayed on the <em>Product choice term</em> pages for this list.'),
      '#default_value' => $product_choice_list->getHelpText(),
    ];

    // Is there a better way to get filter_formats other than filter.module?
    $options = [];
    foreach (filter_formats() as $format) {
      $options[$format->id()] = $format->label();
    }

    $form['allowed_formats'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed formats'),
      '#options' => $options,
      '#default_value' => $product_choice_list->getAllowedFormats(),
      '#description' => $this->t('Restrict which text formats are allowed, given the user has the required permissions. If no text formats are selected, then all the ones the user has access to will be available.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $product_choice_list = $this->entity;
    $status = $product_choice_list->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label product choice list.', [
          '%label' => $product_choice_list->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label product choice list.', [
          '%label' => $product_choice_list->label(),
        ]));
    }
    $form_state->setRedirectUrl($product_choice_list->toUrl('collection'));
  }

}
