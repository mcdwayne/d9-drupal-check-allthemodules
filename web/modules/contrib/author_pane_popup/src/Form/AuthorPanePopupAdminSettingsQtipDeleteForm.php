<?php

namespace Drupal\author_pane_popup\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Author Pane Popup qTip instance delete confirm form.
 */
class AuthorPanePopupAdminSettingsQtipDeleteForm extends ConfirmFormBase {
  /**
   * The banned IP address.
   *
   * @var string
   */
  protected $name;
  protected $machineName;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'author_pane_popup_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete qTip instance %ip?', array('%ip' => $this->name));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('author_pane_popup.qtip_list');
  }

  /**
   * {@inheritdoc}
   *
   * @param string $machine_name
   *   The machine name of qTip instance.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $machine_name = '') {
    $this->machineName = $machine_name;
    $query = \Drupal::database()->select('author_pane_popup_qtip', 'qtip');
    $query->addField('qtip', 'name');
    $query->condition('qtip.machine_name', $machine_name);
    $query->range(0, 1);
    $name = $query->execute()->fetchField();
    $this->name = $name;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = \Drupal::database()->delete('author_pane_popup_qtip');
    $query->condition('machine_name', $this->machineName);
    $query->execute();
    drupal_set_message($this->t('The qTip instance %ip has been deleted.', array('%ip' => $this->name)));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
