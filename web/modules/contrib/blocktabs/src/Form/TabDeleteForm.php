<?php

namespace Drupal\blocktabs\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blocktabs\BlocktabsInterface;

/**
 * Form for deleting a tab.
 */
class TabDeleteForm extends ConfirmFormBase {

  /**
   * The blocktabs containing the tab to be deleted.
   *
   * @var \Drupal\blocktabs\BlocktabsInterface
   */
  protected $blockTabs;

  /**
   * The tab to be deleted.
   *
   * @var \Drupal\blocktabs\TabInterface
   */
  protected $tab;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @tab tab from the %blocktabs blocktabs?', ['%blocktabs' => $this->blockTabs->label(), '@tab' => $this->tab->label()]);
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
    return $this->blockTabs->urlInfo('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tab_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, BlocktabsInterface $blocktabs = NULL, $tab = NULL) {
    $this->blockTabs = $blocktabs;
    $this->tab = $this->blockTabs->getTab($tab);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->blockTabs->deleteTab($this->tab);
    drupal_set_message($this->t('The tab %name has been deleted.', ['%name' => $this->tab->label()]));
    $form_state->setRedirectUrl($this->blockTabs->urlInfo('edit-form'));
  }

}
