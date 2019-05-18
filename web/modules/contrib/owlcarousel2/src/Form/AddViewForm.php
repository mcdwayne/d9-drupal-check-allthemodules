<?php

namespace Drupal\owlcarousel2\Form;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\owlcarousel2\Entity\OwlCarousel2;
use Drupal\owlcarousel2\OwlCarousel2Item;
use Drupal\owlcarousel2\Util;

/**
 * Class addViewForm.
 *
 * @package Drupal\owlcarousel2\Form
 */
class AddViewForm extends AddItemForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'owlcarousel2_add_view_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $owlcarousel2 = NULL, $item_id = NULL) {
    $form['#title'] = $this->t('Carousel | Add View');

    $form_state->set('owlcarousel2', $owlcarousel2);

    // Check if it is an edition.
    if ($item_id) {
      $carousel = OwlCarousel2::load($owlcarousel2);
      $item     = $carousel->getItem($item_id);

      $form['item_id'] = [
        '#type'  => 'value',
        '#value' => $item_id,
      ];

      $form['weight'] = [
        '#type'  => 'value',
        '#value' => $item['weight'],
      ];
    }

    $form['view_id'] = [
      '#type'        => 'select',
      '#title'       => $this->t('View'),
      '#description' => $this->t('Select the view. Note that it will not consider the fields you have chosen in the view, but it will use the view mode instead.'),
      '#required'    => TRUE,
      '#options'     => Util::getViewsOptions(),
      '#default_value' => (isset($item['view_id']) && $item['view_id']) ? $item['view_id'] : '',
    ];

    $view_modes_ids = \Drupal::entityQuery('entity_view_mode')
      ->condition('targetEntityType', 'node')
      ->execute();

    $view_modes = [];
    foreach ($view_modes_ids as $value) {
      $key       = substr($value, strlen('node.'), strlen($value));
      $view_mode = EntityViewMode::load($value);
      if ($view_mode->status() === TRUE) {
        $view_modes[$key] = $view_mode->label();
      }
    }

    $form['view_mode'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Node view mode'),
      '#description'   => $this->t('The node view mode to display each view result.'),
      '#options'       => $view_modes,
      '#required'      => FALSE,
      '#empty_option'  => $this->t('Select'),
      '#default_value' => (isset($item['view_mode']) && $item['view_mode']) ? $item['view_mode'] : '',
    ];

    $form += parent::buildForm($form, $form_state, $owlcarousel2, $item_id);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, OwlCarousel2 $carousel = NULL) {
    $operation       = $form_state->getValue('operation');
    $owlcarousel2_id = $form_state->getStorage()['owlcarousel2'];
    $carousel        = OwlCarousel2::load($owlcarousel2_id);

    $item_array = [
      'type'      => 'view',
      'view_id'   => $form_state->getValue('view_id'),
      'view_mode' => $form_state->getValue('view_mode'),
    ];

    if ($operation == 'add') {
      $item = new OwlCarousel2Item($item_array);
      $carousel->addItem($item);
    }
    else {
      $item_array['id']     = $form_state->getValue('item_id');
      $item_array['weight'] = $form_state->getValue('weight');
      $item                 = new OwlCarousel2Item($item_array);
      $carousel->updateItem($item);
    }

    parent::submitForm($form, $form_state, $carousel);
  }

}
