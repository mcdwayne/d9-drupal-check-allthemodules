<?php

/**
 * @file
 * Contains \Drupal\block_page\Form\PageVariantEditBlockForm.
 */

namespace Drupal\block_page\Form;

/**
 * Provides a form for editing a block plugin of a page variant.
 */
class PageVariantEditBlockForm extends PageVariantConfigureBlockFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_page_page_variant_edit_block_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareBlock($block_id) {
    return $this->pageVariant->getBlock($block_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function submitText() {
    return $this->t('Update block');
  }

}
