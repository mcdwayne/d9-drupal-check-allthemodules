<?php

namespace Drupal\acquiadam_asset_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class DamImport extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dam_folders';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'acquiadam_asset_import.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('acquiadam_asset_import.config');
    $form['folders'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Folders'),
      '#description' => $this->t('Add a list of folder ids separated by new lines.'),
      '#default_value' => $config->get('folders'),
      '#rows' => 10,
    ];

    $bundles = $this->damBundles();
    $form['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Media bundle'),
      '#description' => $this->t('Select the media bundle to import to'),
      '#options' => $bundles,
      '#default_value' => $config->get('bundle'),
    ];

    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable DAM import.'),
      '#default_value' => $config->get('enable'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('acquiadam_asset_import.config')
      ->set('folders', $values['folders'])
      ->set('bundle', $values['bundle'])
      ->set('enable', $values['enable'])
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Retrieve a list of bundles that can be used with Acquia Dam.
   */
  public function damBundles() {
    $options = [];
    try {
      $media_bundles = \Drupal::entityTypeManager()
        ->getStorage('media_type')
        ->loadByProperties(['source' => 'acquiadam_asset']);
      /** @var \Drupal\media\Entity\MediaType $bundle */
      foreach ($media_bundles as $name => $bundle) {
        $options[$name] = $bundle->label();
      }
    } catch (Exception $x) {
      watchdog_exception('acquiadam_asset_import', $x);
    }
    return $options;
  }

}
