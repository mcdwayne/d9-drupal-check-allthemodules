<?php

namespace Drupal\jstree_menu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure order for this site.
 */
class JsTreeMenuForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jstree_menu_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'jstree_menu.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jstree_menu.config');

    $form['general'] = array(
      '#type' => 'fieldset',
      '#title' => t('General settings'),
      '#weight' => 5,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['general']['jstree_menu_theme'] = array(
      '#type' => 'select',
      '#title' => t('jsTree theme'),
      '#options' => array(
        'default' => t('Default'),
        'proton' => t('Proton'),
      ),
      '#default_value' => $config->get('jstree_menu_theme'),
      '#description' => t('You may have to clear the cache for changes to take effect'),
    );

    $form['general']['jstree_menu_height'] = array(
      '#type' => 'select',
      '#title' => t('Menu maximum height'),
      '#options' => array(
        'auto' => t('Automatic'),
        '300px' => t('300px'),
        '500px' => t('500px'),
      ),
      '#default_value' => $config->get('jstree_menu_height'),
      '#description' => t('If you select automatic there will be no vertical scroll and all contents will be visible'),
    );

    $form['general']['jstree_menu_remove_border'] = array(
      '#type' => 'checkbox',
      '#title' => t('Remove border of jsTree.'),
      '#default_value' => $config->get('jstree_menu_remove_border'),
    );

    $form['icons'] = array(
      '#type' => 'fieldset',
      '#title' => t('Icon settings'),
      '#weight' => 6,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['icons']['desc'] = array(
      '#type' => 'item',
      '#title' => t('Help'),
      '#markup' => t('You can use Bootstrap glyphicons and/or Font Awesome icons (see README file for more details).'),
    );

    $form['icons']['jstree_menu_icon'] = array(
      '#type' => 'textfield',
      '#title' => t('Normal icon'),
      '#required' => TRUE,
      '#default_value' => $config->get('jstree_menu_icon'),
      '#description' => t('Icon displayed on every node of tree except leaves.'),
    );

    $form['icons']['jstree_menu_icon_leaves'] = array(
      '#type' => 'textfield',
      '#title' => t('Leaves icon'),
      '#required' => TRUE,
      '#default_value' => $config->get('jstree_menu_icon_leaves'),
      '#description' => t('Icon displayed on tree leaves'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('jstree_menu.config')
      ->set('jstree_menu_theme', $values['jstree_menu_theme'])
      ->save();

    $this->config('jstree_menu.config')
      ->set('jstree_menu_height', $values['jstree_menu_height'])
      ->save();

    $this->config('jstree_menu.config')
      ->set('jstree_menu_remove_border', $values['jstree_menu_remove_border'])
      ->save();

    $this->config('jstree_menu.config')
      ->set('jstree_menu_icon', $values['jstree_menu_icon'])
      ->save();

    $this->config('jstree_menu.config')
      ->set('jstree_menu_icon_leaves', $values['jstree_menu_icon_leaves'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
