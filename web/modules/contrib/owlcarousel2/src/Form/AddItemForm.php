<?php

namespace Drupal\owlcarousel2\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\owlcarousel2\Entity\OwlCarousel2;

/**
 * Class AddItemForm.
 *
 * @package Drupal\owlcarousel2\Form
 */
class AddItemForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'owlcarousel2_add_item_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $owlcarousel2 = NULL, $item_id = NULL) {
    $form['operation'] = [
      '#type'  => 'value',
      '#value' => $item_id ? 'update' : 'add',
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['submit'] = [
      '#type'        => 'submit',
      '#value'       => $item_id ? $this->t('Update') : $this->t('Add'),
      '#button_type' => 'primary',
    ];

    $form['actions']['back'] = [
      '#type'       => 'link',
      '#title'      => $this->t('Back'),
      '#url'        => Url::fromRoute('entity.owlcarousel2.edit_form', [
        'owlcarousel2' => $owlcarousel2,
      ]),
      '#attributes' => ['class' => 'button'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, OwlCarousel2 $carousel = NULL) {
    $operation = $form_state->getValue('operation');

    $carousel->setNewRevision(TRUE);
    $carousel->setRevisionLogMessage($this->t('Item @op', [
      '@op' => $operation == 'add' ? $this->t('added') : $this->t('Updated'),
    ]));
    $carousel->save();

    $form_state->setRedirect('entity.owlcarousel2.edit_form', ['owlcarousel2' => $carousel->id()]);
  }

}
