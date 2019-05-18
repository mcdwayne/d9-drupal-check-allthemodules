<?php

/**
 * @file
 * Contains \Drupal\block_page\Form\AccessConditionDeleteForm.
 */

namespace Drupal\block_page\Form;

use Drupal\block_page\BlockPageInterface;
use Drupal\Core\Form\ConfirmFormBase;

/**
 * Provides a form for deleting an access condition.
 */
class AccessConditionDeleteForm extends ConfirmFormBase {

  /**
   * The block page this selection condition belongs to.
   *
   * @var \Drupal\block_page\BlockPageInterface
   */
  protected $blockPage;

  /**
   * The access condition used by this form.
   *
   * @var \Drupal\Core\Condition\ConditionInterface
   */
  protected $accessCondition;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_page_access_condition_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the access condition %name?', array('%name' => $this->accessCondition->getPluginDefinition()['label']));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return $this->blockPage->urlInfo('edit-form');
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
  public function buildForm(array $form, array &$form_state, BlockPageInterface $block_page = NULL, $condition_id = NULL) {
    $this->blockPage = $block_page;
    $this->accessCondition = $block_page->getAccessCondition($condition_id);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->blockPage->removeAccessCondition($this->accessCondition->getConfiguration()['uuid']);
    $this->blockPage->save();
    drupal_set_message($this->t('The access condition %name has been removed.', array('%name' => $this->accessCondition->getPluginDefinition()['label'])));
    $form_state['redirect_route'] = $this->getCancelRoute();
  }

}
