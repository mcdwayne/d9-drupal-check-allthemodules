<?php

namespace Drupal\searchcloud_block\Entity\Form;


use Drupal\Core\Entity\ContentEntityFormController;

class SearchCloudFormController extends ContentEntityFormController {

  protected $keyword;
  protected $count;
  protected $hide;

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    /* @var $entity \Drupal\searchcloud_block\Entity\SearchCloud */
    $form = parent::form($form, $form_state);

    $this->setValues();

    $form['keyword'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Keyword'),
      '#default_value' => $this->keyword,
      '#size'          => 60,
      '#maxlength'     => 128,
      '#required'      => TRUE,
    );

    $form['count'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Count'),
      '#default_value' => $this->count,
      '#size'          => 10,
      '#maxlength'     => 10,
      '#required'      => TRUE,
    );

    $form['hide'] = array(
      '#type'          => 'checkbox',
      '#title'         => t('Hidden'),
      '#default_value' => $this->hide,
    );

    return $form;
  }

  /**
   * Save the values of the enity in protected variables.
   */
  protected function setValues() {
    $entity = $this->entity;
    if (!empty($entity->keyword)) {
      $this->keyword = $entity->keyword->value;
    }
    if (!empty($entity->count)) {
      $this->count = $entity->count->value;
    }
    if (!empty($entity->hide)) {
      $this->hide = $entity->hide->value;
    }
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, array &$form_state) {
    // Build the entity object from the submitted values.
    $entity                                     = parent::submit($form, $form_state);
    $form_state['redirect_route']['route_name'] = 'searchcloud_block.entity.list';

    return $entity;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    /* @var $entity \Drupal\searchcloud_block\Entity\SearchCloud */
    $entity = $this->entity;
    $entity->save();
  }

}
