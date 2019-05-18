<?php

namespace Drupal\monster_menus\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

class RestoreContentConfirmForm extends ConfirmFormBase {

  private $x, $cancel_url;

  /**
   * @inheritDoc
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to restore this @thing?', $this->x);
  }

  /**
   * @inheritDoc
   */
  public function getCancelUrl() {
    return $this->cancel_url;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Restore');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you want to restore this @thing as a @subthing of %name?', $this->x);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mm_ui_content_restore_confirm';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    list ($item, $parent, $this->x) = $form_state->getBuildInfo()['args'];
    $form['mmtid']     = array('#type' => 'value', '#value' => $item->mmtid);
    $form['mode']      = array('#type' => 'value', '#value' => 'move');
    $form['move_mode'] = array('#type' => 'value', '#value' => 'page');
    $form['dest']      = array('#type' => 'value', '#value' => array($parent => ''));
    $form['name']      = array('#type' => 'value', '#value' => $item->name);
    $form['alias']     = array('#type' => 'value', '#value' => $item->alias);
    $this->cancel_url = mm_content_get_mmtid_url($item->mmtid);

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    CopyMoveContentForm::validate($form, $form_state, TRUE);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $error = mm_content_move_from_bin($src_mmtid = $form_state->getValue('mmtid'));

    if (is_string($error)) {
      \Drupal::messenger()->addError($this->t($error));
    }
    else {
      \Drupal::messenger()->addStatus($this->t('The @thing has been restored.', mm_ui_strings(FALSE)));
      mm_set_form_redirect_to_mmtid($form_state, $src_mmtid);
    }
  }

}
