<?php

namespace Drupal\netlify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class NetlifyForm.
 */
class NetlifyForm extends ConfigFormBase {

  protected $nodeTypes;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager to get the id of all the node.
   *
   * @throws \Exception
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    try {
      $this->nodeTypes = $entityTypeManager->getStorage('node_type')
        ->loadMultiple();
    }
    catch (\Exception $e) {
      throw new \Exception('Failed to load node types: %error', ['%error', $e->getMessage()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'netlify_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'netlify.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('netlify.settings');

    $form['netlify_build_hook_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Netlify Build Hook URL'),
      '#default_value' => $config->get('netlify_build_hook_url'),
      '#description' => $this->t('Learn about build hooks <a href="https://www.netlify.com/docs/webhooks/#incoming-webhooks">here</a>.'),
    ];

    $form['netlify_node_types'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Trigger build hook for:'),
    ];

    foreach ($this->nodeTypes as $node_type) {
      $id = $node_type->id();
      $label = $node_type->label();

      $form['netlify_node_types']['netlify_node_type_' . $id] = [
        '#type' => 'checkbox',
        '#title' => $this->t('@label', ['@label' => $label]),
        '#default_value' => $config->get('netlify_node_type_' . $id),
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('netlify.settings');
    $config->set('netlify_build_hook_url', $form_state->getValue('netlify_build_hook_url'));

    foreach ($this->nodeTypes as $node_type) {
      $id = $node_type->id();
      $config->set('netlify_node_type_' . $id, $form_state->getValue('netlify_node_type_' . $id));
    }
    $config->save();
  }

}
