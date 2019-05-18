<?php

/**
 * @file
 * Contains \Drupal\feadmin_block\Plugin\FeAdminTool\BlockFeAdminTool.
 *
 * Sponsored by: www.freelance-drupal.com
 */

namespace Drupal\feadmin_block\Plugin\FeAdminTool;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\feadmin\FeAdminTool\FeAdminToolBase;

/**
 * Provides a front-end administration tool for blocks.
 *
 * @FeAdminTool(
 *   id = "feadmin_block",
 *   label = @Translation("Block administration"),
 *   description = @Translation("This tool let's you move block within regions by drag&drop.")
 * )
 */
class BlockFeAdminTool extends FeAdminToolBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('administer blocks');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $block_list = array(
      '#type' => 'container',
      '#attached' => array(
        'library' => array('feadmin_block/feadmin_block.tool'),
      ),
      '#attributes' => array(
        'title' => $this->t('Place blocks'),
        'class' => array('feadmin-block-list'),
      ),
    );
    /*$block_manager = \Drupal::service('plugin.manager.block');
    $theme = \Drupal::theme()->getActiveTheme()->getName();

    $block_list = array(
      '#type' => 'container',
      '#attached' => array(
        'library' => array('feadmin_block/feadmin_block.tool'),
      ),
      '#attributes' => array(
        'title' => $this->t('Place blocks'),
        'class' => array('feadmin-block-list'),
      ),
      'filter' => array(
        '#type' => 'search',
        '#title' => $this->t('Filter'),
        '#title_display' => 'invisible',
        '#size' => 30,
        '#placeholder' => $this->t('Filter by block name'),
        '#attributes' => array(
          'class' => array('feadmin_block-block-filter'),
          'data-element' => '.feadmin_block-block-list .feadmin_block-block',
          'title' => $this->t('Enter a part of the block name to filter by.'),
        ),
      ),
      'list' => array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('feadmin_block-block-list'),
        ),
      ),
    );

    // Only add blocks which work without any available context.
    $definitions = $block_manager->getDefinitionsForContexts();
    // Order by category, and then by admin label.
    $definitions = $block_manager->getSortedDefinitions($definitions);

    foreach ($definitions as $plugin_id => $plugin_definition) {
      $block_name = $plugin_definition['admin_label'];
      $category = $plugin_definition['category'];

      $block_list['list'][$plugin_id] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array(
            'feadmin_block-block',
            'js-feadmin_block__add-block',
          ),
          //'data-block' => $plugin_id,
          'id' => Html::getUniqueId('feadmin_block-add-block'),
        ),
        'link' => array(
          '#type' => 'link',
          '#title' => $category . ' : ' . $block_name,
          '#url' => Url::fromRoute('block.admin_add', ['plugin_id' => $plugin_id, 'theme' => $theme]),
          '#attributes' => array(
            'class' => array(
              'feadmin_block-block-addlink',
            ),
          ),
        ),
      );
    }*/
    return $block_list;
  }

  /**
   * Form constructor.
   *
   * Plugin forms are embedded in other forms. In order to know where the plugin
   * form is located in the parent form, #parents and #array_parents must be
   * known, but these are not available during the initial build phase. In order
   * to have these properties available when building the plugin form's
   * elements, let this method return a form element that has a #process
   * callback and build the rest of the form in the callback. By the time the
   * callback is executed, the element's #parents and #array_parents properties
   * will have been set by the form API. For more documentation on #parents and
   * #array_parents, see \Drupal\Core\Render\Element\FormElement.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The form structure.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }
}
