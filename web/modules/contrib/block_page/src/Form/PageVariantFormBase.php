<?php

/**
 * @file
 * Contains \Drupal\block_page\Form\PageVariantFormBase.
 */

namespace Drupal\block_page\Form;

use Drupal\block_page\BlockPageInterface;
use Drupal\Core\Form\FormBase;

/**
 * Provides a base form for editing and adding a page variant.
 */
abstract class PageVariantFormBase extends FormBase {

  /**
   * The block page this page variant belongs to.
   *
   * @var \Drupal\block_page\BlockPageInterface
   */
  protected $blockPage;

  /**
   * The page variant used by this form.
   *
   * @var \Drupal\block_page\Plugin\PageVariantInterface
   */
  protected $pageVariant;

  /**
   * Prepares the page variant used by this form.
   *
   * @param string $page_variant_id
   *   Either a page variant ID, or the plugin ID used to create a new variant.
   *
   * @return \Drupal\block_page\Plugin\PageVariantInterface
   *   The page variant object.
   */
  abstract protected function preparePageVariant($page_variant_id);

  /**
   * Returns the text to use for the submit button.
   *
   * @return string
   *   The submit button text.
   */
  abstract protected function submitText();

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, BlockPageInterface $block_page = NULL, $page_variant_id = NULL) {
    $this->blockPage = $block_page;
    $this->pageVariant = $this->preparePageVariant($page_variant_id);

    // Allow the page variant to add to the form.
    $form['page_variant'] = $this->pageVariant->buildConfigurationForm(array(), $form_state);
    $form['page_variant']['#tree'] = TRUE;

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->submitText(),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // Allow the page variant to validate the form.
    $page_variant_values = array(
      'values' => &$form_state['values']['page_variant'],
    );
    $this->pageVariant->validateConfigurationForm($form, $page_variant_values);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Allow the page variant to submit the form.
    $page_variant_values = array(
      'values' => &$form_state['values']['page_variant'],
    );
    $this->pageVariant->submitConfigurationForm($form, $page_variant_values);
  }

}
