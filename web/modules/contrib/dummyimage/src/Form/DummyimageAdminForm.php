<?php
/**
 * @file
 * Contains \Drupal\dummyimage\Form\DummyimageAdminForm.
 */

namespace Drupal\dummyimage\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

class DummyimageAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dummyimage_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('dummyimage.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $service_links = array();
    $service_names = array();
    $service_forms = array();

    $services = \Drupal::moduleHandler()->invokeAll('dummyimage_service', []);

    foreach ($services as $key => $service) {
      $service_links[] = \Drupal::l($service['title'], Url::fromUri($service['url']));
      $service_names[$key] = $service['title'];
      $service_forms[$key] = $service['form builder'];
    }

    $options = array(
      'none' => t('For no images'),
      'missing' => t('For missing images'),
      'all' => t('For all images'),
    );

    $form['dummyimages_generate'] = array(
      '#type' => 'radios',
      '#title' => t('Use dummy images'),
      '#options' => $options,
      '#default_value' => \Drupal::config('dummyimage.settings')
        ->get('dummyimages_generate'),
    );

    $manager = \Drupal::service('plugin.manager.dummyimage');
    $plugins = $manager->getDefinitions();
    $service_names = array();
    foreach ($plugins as $plugin) {
      $service_names[$plugin['id']] = $this->t($plugin['name']);
    }

    $form['dummyimages_service'] = array(
      '#type' => 'select',
      '#title' => t('Service'),
      '#description' => t('Select a image service to use'),
      '#default_value' => \Drupal::config('dummyimage.settings')
        ->get('dummyimages_service'),
      '#options' => $service_names,
    );

    return parent::buildForm($form, $form_state);
  }
}
