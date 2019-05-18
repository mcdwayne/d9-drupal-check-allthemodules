<?php
/**
 * @file
 * Contains \Drupal\resume\Form\ResumeForm.
 */
namespace Drupal\posse\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class PosseConfiguration extends FormBase {

  /**
   * The payment gateway plugin manager.
   *
   * @var \Drupal\posse\PosseManager
   */
  protected $pluginManager;

  /**
   * Constructs a new PosseConfiguration object.
   */
  public function __construct() {
    $this->pluginManager = \Drupal::service('plugin.manager.posse');
    $this->config = \Drupal::service('config.factory')->getEditable('posse.configuration');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'posse_settings';
  }

  /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {


    if (empty($this->pluginManager->getDefinitions())) {
      $form['warning'] = [
        '#markup' => $this->t('No Posse plugins found. Please install a module which provides one.'),
      ];
    }
    else {
      foreach($this->pluginManager->getDefinitions() as $id => $plugin_definition) {
        $plugin = $this->pluginManager->createInstance($id, []);
        $plugin->configurationForm($form, $form_state);
      }
    }

    $form['actions'] = [
      '#weight' => 100,
      '#type' => 'fieldset'
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save Configuration')
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (!empty($this->pluginManager->getDefinitions())) {
      foreach($this->pluginManager->getDefinitions() as $id => $plugin_definition) {
        $plugin = $this->pluginManager->createInstance($id, []);
        $plugin->validateConfiguration($form, $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if (!empty($this->pluginManager->getDefinitions())) {
      foreach($this->pluginManager->getDefinitions() as $id => $plugin_definition) {
        $plugin = $this->pluginManager->createInstance($id, []);
        $plugin->submitConfiguration($form, $form_state);
      }
    }
   }

}
