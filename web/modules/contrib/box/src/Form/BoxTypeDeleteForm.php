<?php

namespace Drupal\box\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for box type deletion.
 */
class BoxTypeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $num_boxes = $this->entityTypeManager->getStorage('box')->getQuery()
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();

    if ($num_boxes) {
      $caption = '<p>' . $this->formatPlural($num_boxes,
          '%type is used by 1 box on your site. You can not remove this box type until you have removed all of the %type boxes.',
          '%type is used by @count boxes on your site. You may not remove %type until you have removed all of the %type boxes.',
          ['%type' => $this->entity->label()]) . '</p>';

      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
