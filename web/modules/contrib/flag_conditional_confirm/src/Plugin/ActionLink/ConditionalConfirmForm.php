<?php

namespace Drupal\flag_conditional_confirm\Plugin\ActionLink;

use Drupal\flag\Plugin\ActionLink\ConfirmForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flag\ActionLink\ActionLinkTypeBase;
use Drupal\flag\FlagInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Provides the Conditional Confirm Form link type.
 *
 * @ActionLinkType(
 *  id = "confirm_on_condition",
 * label = @Translation("Conditional Confirm Form"),
 * description = "Redirects user to a confirmation form if condition is met."
 * )
 */
class ConditionalConfirmForm extends ConfirmForm {

  /**
   * Flag service.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flagService;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Build a new link type instance and sets the configuration.
   *
   * @param array $configuration
   *   The configuration array with which to initialize this plugin.
   * @param string $plugin_id
   *   The ID with which to initialize this plugin.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\flag\FlagService $flag_service
   *   The Flag service.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The Module handler service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, AccountInterface $current_user, FlagService $flag_service, ModuleHandler $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $current_user);
    $this->flagService = $flag_service;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('flag'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $options = parent::defaultConfiguration();
    $options += [
      'conditional_type' => 'flag',
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $plugin_id = $this->getPluginId();

    $form['display']['settings']['link_options_' . $plugin_id]['conditional_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Confirm form shown only...'),
      '#options' => [
        'flag' => $this->t('On flagging'),
        'unflag' => $this->t('On unflagging'),
        'custom' => $this->t('Custom condition (requires code)'),
      ],
      '#description' => $this->t('On what condition the confirmation should form be shown.'),
      '#default_value' => $this->configuration['conditional_type'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getAsFlagLink(FlagInterface $flag, EntityInterface $entity) {

    $build = ActionLinkTypeBase::getAsFlagLink($flag, $entity);

    $action = $build['#action'] ?? 'flag';
    $confirmation_required = $this->isConfirmationRequired($action, $flag, $entity);

    if ($confirmation_required) {
      if ($this->configuration['form_behavior'] !== 'default') {
        $build['#attached']['library'][] = 'core/drupal.ajax';
        $build['#attributes']['class'][] = 'use-ajax';
        $build['#attributes']['data-dialog-type'] = $this->configuration['form_behavior'];
        $build['#attributes']['data-dialog-options'] = Json::encode([
          'width' => 'auto',
        ]);
      }
    }
    else {
      $build['#attached']['library'][] = 'flag/flag.link_ajax';
      $build['#attributes']['class'][] = 'use-ajax';
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl($action, FlagInterface $flag, EntityInterface $entity) {

    $confirmation_required = $this->isConfirmationRequired($action, $flag, $entity);

    switch ($action) {

      case 'flag':
        $route = ($confirmation_required) ? 'flag.confirm_flag' : 'flag.action_link_flag';
        return Url::fromRoute($route, [
          'flag' => $flag->id(),
          'entity_id' => $entity->id(),
        ]);

      default:
        $route = ($confirmation_required) ? 'flag.confirm_unflag' : 'flag.action_link_unflag';
        return Url::fromRoute($route, [
          'flag' => $flag->id(),
          'entity_id' => $entity->id(),
        ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isConfirmationRequired($action, FlagInterface $flag, EntityInterface $entity) {

    switch ($this->configuration['conditional_type']) {
      case 'flag':
      case 'unflag':
        return ($action == $this->configuration['conditional_type']);
    }

    // Custom conditional required.
    /** @var \Drupal\flag\FlaggingInterface $flagging */
    $flagging = $this->flagService->getFlagging($flag, $entity);

    foreach ($this->moduleHandler->getImplementations('flag_conditional_confirm_confirmation_required') as $module) {
      $function = $module . '_flag_conditional_confirm_confirmation_required';
      $result = $function($action,
        $flag,
        $entity,
        $flagging,
        $this->currentUser);
      // If an implementation requires confirmation, that's enough.
      if ($result === TRUE) {
        return TRUE;
      }
    } // Loop thru implementations.

    return FALSE;

  }

}
