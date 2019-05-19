<?php

namespace Drupal\switches\TwigExtension;

use Drupal\switches\SwitchManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Switches Twig extension that adds a custom function to check a switch status.
 *
 * @code
 * {{ switch_is_active($my_switch_id) }}
 * @endcode
 */
class SwitchExtension extends \Twig_Extension {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Switch Manager service.
   *
   * @var \Drupal\switches\SwitchManagerInterface
   */
  protected $switchManager;

  /**
   * Constructs the SwitchExtension object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\switches\SwitchManagerInterface $switchManager
   *   The Switch Manager service.
   */
  public function __construct(LoggerInterface $logger, SwitchManagerInterface $switchManager) {
    $this->logger = $logger;
    $this->switchManager = $switchManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      'switch_is_active' => new \Twig_Function_Function([$this, 'isSwitchActive']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'twig_extension_switches.switch_access';
  }

  /**
   * Returns Activation status of switch if it exists.
   *
   * @param string $switchId
   *   The machine name for the switch.
   *
   * @return bool
   *   The switch activation status or the configured default for missing
   *   switches.
   *
   * @see SwitchManagerInterface::getActivationStatus()
   */
  public function switchIsActive($switchId) {
    return $this->switchManager->getActivationStatus($switchId);
  }

}
