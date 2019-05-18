<?php

namespace Drupal\dfs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for dfs admin settings.
 *
 * @ingroup dfs
 */
class DFSForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'dfs_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dfs.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dfs.settings');

    $types = array('image', 'file');
    foreach ($types as $type) {

      $section_title = ucwords($type);
      $form[$type] = array(
        '#title' => $section_title,
        '#type' => 'fieldset',
      );

      if ($type == 'image') {
        $form[$type]['dfs_default_image'] = array(
          '#title' => $this->t('Default image'),
          '#type' => 'managed_file',
          '#default_value' => $config->get('dfs_default_image'),
          '#description' => $this->t('Default image file ID (ex: 5).'),
        );

        $min_resolution = $config->get('dfs_min_resolution');
        $form[$type]['dfs_min_resolution'] = array(
          '#title' => $this->t('Minimum resolution'),
          '#type' => 'fieldset',
        );
        $form[$type]['dfs_min_resolution']['dfs_min_resolution_x'] = array(
          '#title' => $this->t('Width'),
          '#type' => 'textfield',
          '#default_value' => isset($min_resolution['x']) ? $min_resolution['x'] : '',
          '#description' => $this->t('Default min width (ex: 1024).'),
        );
        $form[$type]['dfs_min_resolution']['dfs_min_resolution_y'] = array(
          '#title' => t('Height'),
          '#type' => 'textfield',
          '#default_value' => isset($min_resolution['y']) ? $min_resolution['y'] : '',
          '#description' => $this->t('Default min width (ex: 768).'),
        );

        $max_resolution = $config->get('dfs_max_resolution');
        $form[$type]['dfs_max_resolution'] = array(
          '#title' => $this->t('Maximum resolution'),
          '#type' => 'fieldset',
        );
        $form[$type]['dfs_max_resolution']['dfs_max_resolution_x'] = array(
          '#title' => $this->t('Width'),
          '#type' => 'textfield',
          '#default_value' => isset($max_resolution['x']) ? $max_resolution['x'] : '',
          '#description' => $this->t('Default min width (ex: 1024).'),
        );
        $form[$type]['dfs_max_resolution']['dfs_max_resolution_y'] = array(
          '#title' => $this->t('Height'),
          '#type' => 'textfield',
          '#default_value' => isset($max_resolution['y']) ? $max_resolution['y'] : '',
          '#description' => $this->t('Default min width (ex: 768).'),
        );
      }

      $form[$type]['dfs_' . $type . '_require_filesize'] = array(
        '#title' => $this->t('Require max filesize'),
        '#type' => 'checkbox',
        '#default_value' => $config->get('dfs_' . $type . '_require_filesize'),
      );

      $form[$type]['dfs_' . $type . '_max_filesize'] = array(
        '#title' => $this->t('Max filesize'),
        '#type' => 'textfield',
        '#default_value' => $config->get('dfs_' . $type . '_max_filesize'),
        '#description' => $this->t('Default file maximum size (ex: 50 MB).'),
      );

      $form[$type]['dfs_' . $type . '_file_extensions'] = array(
        '#title' => $this->t('File extensions'),
        '#type' => 'textfield',
        '#default_value' => $config->get('dfs_' . $type . '_file_extensions'),
        '#description' => $this->t('Default file extensions.'),
      );

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('dfs.settings');
    // Image and file fields.
    $types = array('image', 'file');
    foreach ($types as $type) {
      if ($type == 'image') {
        $min_resolution = array(
          'x' => $form_state->getValue('dfs_min_resolution_x'),
          'y' => $form_state->getValue('dfs_min_resolution_y')
        );
        $max_resolution = array(
          'x' => $form_state->getValue('dfs_max_resolution_x'),
          'y' => $form_state->getValue('dfs_max_resolution_y')
        );
        $config->set('dfs_default_image', $form_state->getValue('dfs_default_image'));
        $config->set('dfs_min_resolution', $min_resolution);
        $config->set('dfs_max_resolution', $max_resolution);
      }
      $config->set('dfs_' . $type . '_require_filesize', $form_state->getValue('dfs_' . $type . '_require_filesize'));
      $config->set('dfs_' . $type . '_max_filesize', $form_state->getValue('dfs_' . $type . '_max_filesize'));
      $config->set('dfs_' . $type . '_file_extensions', $form_state->getValue('dfs_' . $type . '_file_extensions'));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
