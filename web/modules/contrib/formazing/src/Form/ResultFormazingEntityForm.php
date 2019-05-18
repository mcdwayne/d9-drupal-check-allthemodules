<?php

namespace Drupal\formazing\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Result formazing entity edit forms.
 *
 * @ingroup formazing
 */
class ResultFormazingEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $datas = $this->entity->get('data')->value;

    if (!$datas) {
      return;
    }

    $datas = json_decode($datas);

    foreach ($datas as $key => $data) {
      $form[$key]['#title'] = $data->label;
      $form[$key]['#type'] = $data->type;
      $form[$key]['#value'] = $data->value;
    }

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
        \Drupal::messenger()
          ->addStatus($this->t('Created the %label Result formazing entity.', [
            '%label' => $entity->label(),
          ]));
        break;
      default:
        \Drupal::messenger()
          ->addStatus($this->t('Saved the %label Result formazing entity.', [
            '%label' => $entity->label(),
          ]));
    }

    $form_state->setRedirect('entity.result_formazing_entity.canonical', ['result_formazing_entity' => $entity->id()]);
  }

}
