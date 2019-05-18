<?php

/**
 * @file
 * Contains \Drupal\packaging\Form\PackagingDebugForm.
 */

namespace Drupal\packaging\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\packaging\Context;
use Drupal\packaging\Package;
use Drupal\packaging\Product;
use Drupal\packaging\Strategy;


/**
 * Functions needed by shipping quotes modules to administer packaging strategy.
 *
 * @author Tim Rohaly.    <http://drupal.org/user/202830>
 */


/**
 * Configure packaging settings for this site.
 */
class PackagingDebugForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'packaging_debug';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'packaging.settings',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Menu callback for /packaging path. This is used only for testing during
   * development, and will be removed before a release.
   *
   * @return
   *   Forms for store administrator to set configuration options.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $packaging_config = $this->config('packaging.settings');

    $operations = packaging_get_strategies();
    $options = array();
    foreach ($operations as $id => $operation) {
      $options[$id] = $operation['admin_label'];
    }
    $default_value = $packaging_config->get('strategy');
    $default_value = isset($default_value) ?  $default_value : reset($options);

    $form['packaging_strategy'] = array(
      '#type' => 'select',
      '#title' => t('Please choose Strategy'),
      '#options' => $options,
      '#default_value' => $default_value,
    );

    //$form['hooks'] = array(
    //  '#markup' => '<pre>' . ctools_plugin_api_get_hook('views', 'views') . '</pre>',
    //);

    $form['operations'] = array(
      '#markup' => '<pre>' . var_export($operations, TRUE) . '</pre>',
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Select strategy'),
    );

//    // Register additional submit handler.
//    $form['#submit'][] = 'print_info_submit';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * Submit handler for /packaging path. This is used only for testing during
   * development, and will be removed before a release.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $packaging_config = $this->config('packaging.settings');

    $packaging_config
      ->set('strategy', $values['packaging_strategy'])
      ->save();

    $operation = $values['packaging_strategy'];
    if ($instance = packaging_get_instance($operation)) {
      $context = new Context();
      $context->setStrategy($instance);
      drupal_set_message("<pre>Invoked packageProducts()<br>" . var_export($context->packageProducts(array(new Product(), new Product())), TRUE) . "</pre>");
    }
    parent::submitForm($form, $form_state);
  }
}
