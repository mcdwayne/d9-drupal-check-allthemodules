<?php

namespace Drupal\whitelabel\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\whitelabel\Entity\WhiteLabel;
use Drupal\whitelabel\WhiteLabelProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Set a white label.
 *
 * @Action(
 *   id = "set_white_label",
 *   label = @Translation("Set a white label"),
 *   type = "whitelabel"
 * )
 */
class SetWhiteLabel extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The current white label.
   *
   * @var \Drupal\whitelabel\WhiteLabelProviderInterface
   */
  protected $whiteLabelProvider;

  /**
   * Constructs the set white label action.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\whitelabel\WhiteLabelProviderInterface $white_label_provider
   *   The white label provider.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WhiteLabelProviderInterface $white_label_provider) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->whiteLabelProvider = $white_label_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('whitelabel.whitelabel_provider'));
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if ($this->configuration['whitelabel_id']) {
      $whitelabel = WhiteLabel::load($this->configuration['whitelabel_id']);
      $this->whiteLabelProvider->setWhiteLabel($whitelabel);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'whitelabel_id' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];

    $whitelabels = \Drupal::entityTypeManager()->getStorage('whitelabel')->loadMultiple();
    foreach ($whitelabels as $whitelabel) {
      $options[$whitelabel->id()] = $whitelabel->label();
    }

    $form['whitelabel_id'] = [
      '#type' => 'select',
      '#title' => t('White label'),
      '#default_value' => $this->configuration['whitelabel_id'],
      '#description' => t('The white label to set.'),
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['whitelabel_id'] = $form_state['values']['whitelabel_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = AccessResult::allowed();
    return $return_as_object ? $access : $access->isAllowed();
  }

}
