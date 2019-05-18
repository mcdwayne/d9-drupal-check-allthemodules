<?php

/**
 * @file
 * Contains \Drupal\block_page\Form\PageVariantConfigureBlockFormBase.
 */

namespace Drupal\block_page\Form;

use Drupal\block_page\BlockPageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;

/**
 * Provides a base form for configuring a block as part of a page variant.
 */
abstract class PageVariantConfigureBlockFormBase extends FormBase {

  /**
   * The block page.
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
   * The plugin being configured.
   *
   * @var \Drupal\block\BlockPluginInterface
   */
  protected $block;

  /**
   * Prepares the block plugin based on the block ID.
   *
   * @param string $block_id
   *   Either a block ID, or the plugin ID used to create a new block.
   *
   * @return \Drupal\block\BlockPluginInterface
   *   The block plugin.
   */
  abstract protected function prepareBlock($block_id);

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
  public function buildForm(array $form, array &$form_state, BlockPageInterface $block_page = NULL, $page_variant_id = NULL, $block_id = NULL) {
    $this->blockPage = $block_page;
    $this->pageVariant = $block_page->getPageVariant($page_variant_id);
    $this->block = $this->prepareBlock($block_id);

    $form['#tree'] = TRUE;
    $form['settings'] = $this->block->buildConfigurationForm(array(), $form_state);
    $form['settings']['id'] = array(
      '#type' => 'value',
      '#value' => $this->block->getPluginId(),
    );
    $form['region'] = array(
      '#title' => $this->t('Region'),
      '#type' => 'select',
      '#options' => $this->pageVariant->getRegionNames(),
      '#default_value' => $this->pageVariant->getRegionAssignment($this->block->getConfiguration()['uuid']),
      '#required' => TRUE,
    );

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
    $settings = array(
      'values' => &$form_state['values']['settings'],
    );
    // Call the plugin validate handler.
    $this->block->validateConfigurationForm($form, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $settings = array(
      'values' => &$form_state['values']['settings'],
      'errors' => $form_state['errors'],
    );

    // Call the plugin submit handler.
    $this->block->submitConfigurationForm($form, $settings);
    $this->pageVariant->updateBlock($this->block->getConfiguration()['uuid'], array('region' => $form_state['values']['region']));
    $this->blockPage->save();

    $form_state['redirect_route'] = new Url('block_page.page_variant_edit', array(
      'block_page' => $this->blockPage->id(),
      'page_variant_id' => $this->pageVariant->id(),
    ));
  }

}
