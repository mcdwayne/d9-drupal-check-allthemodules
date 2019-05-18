<?php

namespace Drupal\consent\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConsentLayerBlockBase.
 */
abstract class ConsentLayerBlockBase extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * ConsentLayerBlockBase constructor.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Session\AccountProxyInterface
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $libraries = ['consent/layer'];
    foreach ($this->configuration['trigger'] as $trigger => $enabled) {
      if ($enabled) {
        $libraries[] = 'consent/trigger.' . $trigger;
      }
    }
    return [
      '#attached' => [
        'library' => $libraries,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $no_access = !$this->currentUser->hasPermission('configure consent');
    if ($no_access) {
      $this->messenger()->addWarning($this->t("You do not have permission to change the consent configuration."));
    }
    return parent::blockForm($form, $form_state) + [
      '#disabled' => $no_access,
      'trigger' => [
        '#type' => 'fieldset',
        '#title' => $this->t('Trigger for user consents'),
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
      ] + $this->buildTriggerElements($form, $form_state),
    ];
  }

  /**
   * Build the trigger form elements.
   *
   * @param array $form
   *   The main form build array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The trigger form elements.
   */
  protected function buildTriggerElements(array $form, FormStateInterface $form_state) {
    $elements = [];
    foreach ($this->getConsentTriggers() as $trigger => $label) {
      $elements[$trigger] = [
        '#type' => 'checkbox',
        '#title' => $this->t($label),
        '#default_value' => $this->configuration['trigger'][$trigger],
      ];
    }
    return $elements;
  }

  /**
   * Get all available consent triggers.
   *
   * @return array
   *   The consent triggers.
   */
  protected function getConsentTriggers() {
    return [
      'storage' => 'Submit user consents to the backend storage.',
      'click' => 'Enable automatic opt-in when clicking.',
      'scroll' => 'Enable automatic opt-in when scrolling.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    parent::blockValidate($form, $form_state);
    $no_access = !$this->currentUser->hasPermission('configure consent');
    if ($no_access) {
      $form_state->setErrorByName('', $this->t("You do not have permission to change the consent configuration."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValue('trigger', []);
    $triggers = $this->getConsentTriggers();
    foreach (array_keys($triggers) as $trigger) {
      $triggers[$trigger] = !empty($values[$trigger]);
    }
    $this->configuration['trigger'] = $triggers;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default = parent::defaultConfiguration();
    foreach (array_keys($this->getConsentTriggers()) as $trigger) {
      $default['trigger'][$trigger] = FALSE;
    }
    return $default;
  }

}
