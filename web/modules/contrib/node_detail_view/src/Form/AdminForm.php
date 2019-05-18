<?php
/**
 * @file
 * Contains \Drupal\node_detail_view\Form\AdminForm.
 */

namespace Drupal\node_detail_view\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin form.
 */
class AdminForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('node_detail_view.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_detail_view_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $view_options = array('node_info_wrapper', 'node_info_wrapper_fixed');
    $options = array_combine($view_options, $view_options);

    $form['node_detail_view_default'] = array(
      '#type' => 'select',
      '#default_value' => \Drupal::config('node_detail_view.settings')->get('default_view'),
      '#title' => t('Default Viewing style for admin content page'),
      '#options' => array(
        t('List View'),
        t('Detailed View'),
      ),
    );
    $form['node_detail_view_info_block'] = array(
      '#type' => 'select',
      '#default_value' => \Drupal::config('node_detail_view.settings')->get('info_block_class'),
      '#description' => t('<i>node_info_wrapper</i> will keep this div relative to other divs, <br /><i>node_info_wrapper_fixed</i> will keep this div fixed on screen.'),
      '#title' => t('Default class for positioning Node Info'),
      '#options' => $options,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::getContainer()->get('config.factory')->getEditable('node_detail_view.settings');
    $config->set('default_view', $form_state->getValue('node_detail_view_default'))->save();
    $config->set('info_block_class', $form_state->getValue('node_detail_view_info_block'))->save();
    return parent::submitForm($form, $form_state);
  }
}
