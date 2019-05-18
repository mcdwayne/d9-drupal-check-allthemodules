<?php

namespace Drupal\client_connection\Plugin\ClientConnection;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Core\Plugin\PluginWithFormsTrait;

/**
 * Base class for Client Connection plugins.
 */
abstract class ClientConnectionBase extends ContextAwarePluginBase implements ClientConnectionInterface {

  use ContextAwarePluginAssignmentTrait;
  use PluginWithFormsTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    unset($configuration['context']);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getPluginDefinition()['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->getPluginDefinition()['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function categories() {
    return $this->getPluginDefinition()['categories'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * The plugin's Client Connection Config entity.
   *
   * @return \Drupal\client_connection\Entity\ClientConnectionConfigInterface
   *   The currently set Client Connection Config entity, or NULL if not set.
   */
  protected function getEntity() {
    return $this->getContext('client_connection_config')->getContextValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationValue($key, $default_return = NULL) {
    $keys = is_array($key) ? $key : [$key];
    return NestedArray::getValue($this->configuration, $keys, $default_return);
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigurationValue($key, $value) {
    $this->configuration[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * Gets the request object.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request object.
   */
  protected function getRequest() {
    if (!$this->requestStack) {
      $this->requestStack = \Drupal::service('request_stack');
    }
    return $this->requestStack->getCurrentRequest();
  }

  /**
   * Returns the configuration form elements specific to this plugin.
   *
   * Client Connections that need to add form elements to the configuration
   * form should implement this method.
   *
   * @param array $form
   *   The form definition array for the configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The renderable form array representing the entire configuration form.
   */
  abstract protected function clientForm(array $form, FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   *
   * Plugins should not override this method. To add form elements for a
   * specific client connection, override ClientConnectionBase::clientForm().
   *
   * @see \Drupal\client_connection\Plugin\ClientConnection\ClientConnectionBase::clientForm()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $definition = $this->getPluginDefinition();
    $form['provider'] = [
      '#type' => 'value',
      '#value' => $definition['provider'],
    ];

    // Add context mapping UI form elements.
    $contexts = $form_state->getTemporaryValue('gathered_contexts') ?: [];
    $form['context_mapping'] = $this->addContextAssignmentElement($this, $contexts);
    $form['#access'] = FALSE;

    // Add plugin-specific settings for this client.
    $form += $this->clientForm($form, $form_state);

    \Drupal::moduleHandler()->alter(['client_connection_form', 'client_connection_form_' . $this->pluginId], $form, $form_state, $this);

    return $form;
  }

  /**
   * Adds client-specific validation for the form.
   *
   * Note that this method takes the form structure and form state for the full
   * configuration form as arguments, not just the elements defined in
   * ClientConnectionBase::clientForm().
   *
   * @param array $form
   *   The form definition array for the full configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\client_connection\Plugin\ClientConnection\ClientConnectionBase::clientForm()
   * @see \Drupal\client_connection\Plugin\ClientConnection\ClientConnectionBase::clientSubmit()
   */
  abstract protected function clientValidate(array $form, FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   *
   * Plugins should not override this method. To add validation for a specific
   * client, override ClientConnectionBase::clientValidate().
   *
   * @see \Drupal\client_connection\Plugin\ClientConnection\ClientConnectionBase::clientValidate()
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->clientValidate($form, $form_state);
    \Drupal::moduleHandler()->invokeAll('client_connection_form_validate', [
      &$form,
      $form_state,
      $this,
    ]);
    \Drupal::moduleHandler()->invokeAll('client_connection_form_' . $this->pluginId . '_validate', [
      &$form,
      $form_state,
      $this,
    ]);
  }

  /**
   * Adds client-specific submission handling for the client form.
   *
   * Note that this method takes the form structure and form state for the full
   * configuration form as arguments, not just the elements defined in
   * ClientConnectionBase::clientForm().
   *
   * @param array &$form
   *   The form definition array for the full configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\client_connection\Plugin\ClientConnection\ClientConnectionBase::clientForm()
   * @see \Drupal\client_connection\Plugin\ClientConnection\ClientConnectionBase::clientValidate()
   */
  abstract protected function clientSubmit(array &$form, FormStateInterface $form_state);

  /**
   * {@inheritdoc}
   *
   * Plugins should not override this method. To add submission handling
   * for a specific client, override ClientConnectionBase::clientSubmit().
   *
   * @see \Drupal\client_connection\Plugin\ClientConnection\ClientConnectionBase::clientSubmit()
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Process the client's submission handling if no errors occurred only.
    if (!$form_state->getErrors()) {
      $this->configuration['provider'] = $form_state->getValue('provider');
      $this->clientSubmit($form, $form_state);

      \Drupal::moduleHandler()->invokeAll('client_connection_form_submit', [
        &$form,
        $form_state,
        $this,
      ]);
      \Drupal::moduleHandler()->invokeAll('client_connection_form_' . $this->pluginId . '_submit', [
        &$form,
        $form_state,
        $this,
      ]);
    }
  }

}
