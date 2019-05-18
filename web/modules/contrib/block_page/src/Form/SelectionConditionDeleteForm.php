<?php

/**
 * @file
 * Contains \Drupal\block_page\Form\SelectionConditionDeleteForm.
 */

namespace Drupal\block_page\Form;

use Drupal\block_page\BlockPageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * @todo.
 */
class SelectionConditionDeleteForm extends ConfirmFormBase {

  /**
   * The block page this selection condition belongs to.
   *
   * @var \Drupal\block_page\BlockPageInterface
   */
  protected $blockPage;

  /**
   * The page variant.
   *
   * @var \Drupal\block_page\Plugin\PageVariantInterface
   */
  protected $pageVariant;

  /**
   * The selection condition used by this form.
   *
   * @var \Drupal\Core\Condition\ConditionInterface
   */
  protected $selectionCondition;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_page_selection_condition_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the selection condition %name?', array('%name' => $this->selectionCondition->getPluginDefinition()['label']));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return new Url('block_page.page_variant_edit', array(
      'block_page' => $this->blockPage->id(),
      'page_variant_id' => $this->pageVariant->id(),
    ));
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
  public function buildForm(array $form, array &$form_state, BlockPageInterface $block_page = NULL, $page_variant_id = NULL, $condition_id = NULL) {
    $this->blockPage = $block_page;
    $this->pageVariant = $this->blockPage->getPageVariant($page_variant_id);
    $this->selectionCondition = $this->pageVariant->getSelectionCondition($condition_id);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->pageVariant->removeSelectionCondition($this->selectionCondition->getConfiguration()['uuid']);
    $this->blockPage->save();
    drupal_set_message($this->t('The selection condition %name has been removed.', array('%name' => $this->selectionCondition->getPluginDefinition()['label'])));
    $form_state['redirect_route'] = $this->getCancelRoute();
  }

}
