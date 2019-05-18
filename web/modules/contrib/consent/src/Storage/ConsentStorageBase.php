<?php

namespace Drupal\consent\Storage;
use Drupal\consent\ConsentInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * The base class for consent storage implementations.
 */
abstract class ConsentStorageBase implements ConsentStorageInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ConsentStorageBase constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function save(ConsentInterface $consent) {
    $this->beforeSave($consent);
    if ($consent->missingKeys()) {
      throw new ConsentStorageException(sprintf('Cannot save consent information with missing keys %s.', implode(', ', $consent->missingKeys())));
    }
    $this->doSave($consent);
  }

  /**
   * Perform actions right before saving the consent.
   *
   * @param \Drupal\consent\ConsentInterface $consent
   *   The consent information to save.
   */
  protected function beforeSave(ConsentInterface $consent) {
    $this->moduleHandler->invokeAll('before_consent_save', [$consent]);
  }

  /**
   * Performs save operation on the given consent information.
   *
   * @param \Drupal\consent\ConsentInterface $consent
   *   The consent information to save.
   *
   * @throws \Drupal\consent\Storage\ConsentStorageException
   *   When something went wrong.
   */
  abstract protected function doSave(ConsentInterface $consent);

}
