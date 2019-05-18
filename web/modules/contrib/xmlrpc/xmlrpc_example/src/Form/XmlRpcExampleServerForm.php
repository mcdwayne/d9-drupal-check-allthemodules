<?php

namespace Drupal\xmlrpc_example\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configuration form of the XML-RPC service = UI for the XML-RPC Server part.
 *
 * A server does not require an interface at all. In this implementation we use
 * a server configuration form to set the limits available for the addition and
 * subtraction operations.
 *
 * In this case the maximum and minimum values for any of the operations (add
 * or subtraction).
 */
class XmlRpcExampleServerForm extends ConfigFormBase {

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
    return 'xmlrpc_example_server';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('xmlrpc_example.server');

    $form['explanation'] = [
      '#markup' => '<div>' . $this->t('This is the XML-RPC server configuration page.<br />Here you may define the maximum and minimum values for the addition or subtraction exposed services.<br />') . '</div>',
    ];
    $form['min'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the minimum value returned by the subtraction or addition methods'),
      '#description' => $this->t('If the result of the operation is lower than this value, a custom XML-RPC error will be returned: 10002.'),
      '#default_value' => $config->get('min'),
      '#size' => 5,
      '#required' => TRUE,
    ];
    $form['max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the maximum value returned by sub or add methods'),
      '#description' => $this->t('if the result of the operation is bigger than this value, a custom XML-RPC error will be returned: 10001.'),
      '#default_value' => $config->get('max'),
      '#size' => 5,
      '#required' => TRUE,
    ];
    $form['info'] = [
      '#type' => 'markup',
      '#markup' => '<div>' . $this->t('Use the <a href=":url">XML-RPC Client example form</a> to experiment.', [
        ':url' => Url::fromRoute('xmlrpc_example.client')->toString(),
      ]),
    ];

    if ($config->get('alter_enabled')) {
      $form['overridden'] = [
        '#type' => 'markup',
        '#markup' => '<div><strong>' . $this->t('Just a note of warning: The <a href=":url">alter form</a> has been used to disable the limits, so you may want to turn that off if you do not want it.', [
          ':url' => Url::fromRoute('xmlrpc_example.alter')->toString(),
        ]) . '</strong></div>',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('xmlrpc_example.server')
      ->set('min', $form_state->getValue('min'))
      ->set('max', $form_state->getValue('max'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
