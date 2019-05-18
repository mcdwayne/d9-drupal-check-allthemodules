<?php

namespace Drupal\xmlrpc_example\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Presents a form to enable/disable the code implemented in hook_xmlrpc_alter.
 *
 * Our implementation of hook_xmlrpc_alter will work only if a config variable
 * is set to TRUE, and we need a configuration form to enable/disable this
 * 'feature'. This is the user interface to enable or disable the
 * hook_xmlrpc_alter operations.
 */
class XmlRpcExampleAlterForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'xmlrpc_example.server',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlrpc_example_alter';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('xmlrpc_example.server');

    $form['explanation'] = [
      '#markup' => '<div>' . $this->t('This is a configuration form to enable the alteration of XML-RPC methods using hook_xmlrpc_alter.<br />hook_xmlrpc_alter() can be used to alter the current defined methods by other modules. In this case as demonstration, we will overide current add and subtraction methods with others not being limited. Remember that this hook is optional and is not required to create XMLRPC services.<br />') . '</div>',
    ];
    $form['alter_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override current xmlrpc_example.add and xmlrpc_example.subtraction methods'),
      '#description' => $this->t('If this checkbox is enabled, the default methods will be replaced with custom methods that ignore the XML-RPC server maximum and minimum restrictions.'),
      '#default_value' => $config->get('alter_enabled'),
    ];
    $form['info'] = [
      '#markup' => '<div>' . $this->t('Use the <a href=":url">client submission form</a> to see the results of checking this checkbox', [
        ':url' => Url::fromRoute('xmlrpc_example.client')->toString(),
      ]) . '</div>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('xmlrpc_example.server')
      ->set('alter_enabled', $form_state->getValue('alter_enabled'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
