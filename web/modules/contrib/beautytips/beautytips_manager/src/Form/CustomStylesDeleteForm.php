<?php

namespace Drupal\beautytips_manager\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Url;
use Drupal\Core\Link;

class CustomStylesDeleteForm extends ConfirmFormBase {

  protected $style;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'beautytips_manager_delete_style_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to the beautytip stlye %style?', ['%style' => $this->style->name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('beautytips_manager.customStyles');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $this->style = beautytips_manager_get_custom_style($id);
    if (empty($this->style)) {
      throw new NotFoundHttpException();
    }

    if ($this->style->name == \Drupal::config('beautytips.basic')
        ->get('beautytips_default_style')) {
      return [
        'warning' => [
          '#type' => 'markup',
          '#markup' => t('You cannot delete this style because is set as the default style. You may change this !link.', ['!link' => Link::createFromRoute(t('here'), 'beautytips.config')->toString()]),
        ],
      ];
    }
    else {
      return parent::buildForm($form, $form_state);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    beautytips_manager_delete_custom_style($this->style->id);
    $form_state->setRedirect('beautytips_manager.customStyles');
  }
}
