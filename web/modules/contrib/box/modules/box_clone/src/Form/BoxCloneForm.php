<?php

namespace Drupal\box_clone\Form;

use Drupal\box\Form\BoxForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for clone box edit form.
 */
class BoxCloneForm extends BoxForm {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);

    $element['submit']['#access'] = TRUE;
    $element['submit']['#value'] = $this->t('Duplicate');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\box\Entity\BoxInterface $box */
    $box = $this->entity;
    $status = $box->save();

    if ($status == SAVED_NEW) {
      $args = [
        '@type' => $box->bundleLabel(),
        '%title' => $box->label(),
      ];
      $this->logger('box_clone')->notice('@type: added %title.', $args);
      drupal_set_message($this->t('@type box %title has been created.', $args));
    }

    if ($box->id()) {
      $form_state->setValue('id', $box->id());
      $form_state->set('id', $box->id());
      $form_state->setRedirect('entity.box.collection', ['box' => $box->id()]);
    }
    else {
      // In the unlikely case something went wrong on save, the box will be
      // rebuilt and box form redisplayed the same way as in preview.
      drupal_set_message(t('The cloned box could not be saved.'), 'error');
      $form_state->setRebuild();
    }
  }

}
