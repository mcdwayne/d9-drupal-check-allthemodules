<?php

/**
 * @file
 * Contains \Drupal\block_page\Form\PageVariantDeleteForm.
 */

namespace Drupal\block_page\Form;

use Drupal\block_page\BlockPageInterface;
use Drupal\Core\Form\ConfirmFormBase;

/**
 * Provides a form for deleting a page variant.
 */
class PageVariantDeleteForm extends ConfirmFormBase {

  /**
   * The block page this page variant belongs to.
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_page_page_variant_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the page variant %name?', array('%name' => $this->pageVariant->label()));
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
  public function buildForm(array $form, array &$form_state, BlockPageInterface $block_page = NULL, $page_variant_id = NULL) {
    $this->blockPage = $block_page;
    $this->pageVariant = $block_page->getPageVariant($page_variant_id);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->blockPage->removePageVariant($this->pageVariant->id());
    $this->blockPage->save();
    drupal_set_message($this->t('The page variant %name has been removed.', array('%name' => $this->pageVariant->label())));
    $form_state['redirect_route'] = $this->getCancelRoute();
  }

}
