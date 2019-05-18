<?php

namespace Drupal\ga_webform\Plugin\WebformHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\ga\AnalyticsCommand\Event;
use Drupal\ga_webform\DelayedCommandRegistryInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Registers a Googalytics tracking event.
 *
 * Note, that this webform handler must bridge the gap form the request to the
 * response, as the page gets either redirected or reloaded. It must register
 * the event in the system. Upon delivering the response, the corresponding
 * Googalytics event handler will take care of firing the tracking event.
 *
 * @WebformHandler(
 *   id = "googalytics_event",
 *   label = @Translation("Googalytics event"),
 *   description = @Translation("Registers a Googalytics tracking event."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class GoogalyticsWebformHandler extends WebformHandlerBase {

  /**
   * The delayed command registry.
   *
   * @var \Drupal\ga_webform\DelayedCommandRegistryInterface
   */
  protected $delayedCommandRegistry;

  /**
   * Constructs a new GoogalyticsWebformHandler object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformSubmissionConditionsValidatorInterface $conditions_validator
   *   The webform submission conditions (#states) validator.
   * @param \Drupal\ga_webform\DelayedCommandRegistryInterface $delayed_command_registry
   *   The delayed command registry.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, DelayedCommandRegistryInterface $delayed_command_registry) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);

    $this->delayedCommandRegistry = $delayed_command_registry;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('ga_webform.delayed_command_registry')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $command = new Event('webform', 'submit', $webform_submission->getWebform()->id());
    $this->delayedCommandRegistry->addCommand($command);
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    // This handler does not need a summary.
    return [];
  }

}
