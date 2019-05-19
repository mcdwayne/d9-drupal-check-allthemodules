<?php

namespace Drupal\wbm2cm\Commands;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\wbm2cm\MigrationController;
use Drush\Commands\DrushCommands;

class Wbm2cmCommands extends DrushCommands {

  /**
   * The migration controller service.
   *
   * @var \Drupal\wbm2cm\MigrationController
   */
  protected $controller;

  /**
   * The module installer service.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * Wbm2cmCommands constructor.
   *
   * @param \Drupal\wbm2cm\MigrationController $controller
   *   The migration controller service.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer service.
   */
  public function __construct(MigrationController $controller, ModuleInstallerInterface $module_installer) {
    $this->controller = $controller;
    $this->moduleInstaller = $module_installer;
  }

  /**
   * Migrates from Workbench Moderation to Content Moderation.
   *
   * @command wbm2cm:migrate
   * @aliases wbm2cm-migrate
   */
  public function migrate() {
    $out = $this->output();

    $this->save();
    $this->clear(FALSE);

    $fields = $this->controller->getOverriddenFields();
    if ($fields) {
      $out->writeln('It looks like you have overridden the moderation_state base field. These overrides will be reverted because they are incompatible with Content Moderation. You will also need to delete these from your exported config.');

      /** @var \Drupal\Core\Field\Entity\BaseFieldOverride $field */
      foreach ($fields as $field) {
        $field->delete();
        $message = sprintf('Reverted %s. Delete %s.yml from your exported config.', $field->id(), $field->getConfigDependencyName());
        $out->writeln($message);
      }
    }

    $out->writeln('Installing Content Moderation...');
    // Module installation or uninstallation modifies the container, and
    // potentially every service in it. We do not want to be holding on to
    // outdated services and their implict dependency trees.
    $this->moduleInstaller->uninstall(['workbench_moderation']);
    if (\Drupal::hasContainer()) {
      $this->controller = $this->reloadService($this->controller);
      $this->moduleInstaller = $this->reloadService($this->moduleInstaller);
    }
    $this->moduleInstaller->install(['content_moderation']);

    $this->restore();

    $out->writeln('Yay! You have been migrated to Content Moderation.');
  }

  /**
   * Saves moderation state data to temporary migration tables.
   *
   * @command wbm2cm:save
   * @aliases wbm2cm-save
   */
  public function save() {
    $out = $this->output();

    $out->writeln('Saving existing moderation states to temporary tables...');
    $messages = $this->controller->executeStepWithMessages('save');
    array_walk($messages, [$out, 'writeln']);

  }

  /**
   * Deletes moderation state data.
   *
   * @param bool $standalone
   *   Internal use only. TRUE if the command is being run directly.
   *
   * @command wbm2cm:clear
   * @aliases wbm2cm-clear
   */
  public function clear($standalone = TRUE) {
    $out = $this->output();

    $out->writeln('Removing Workbench Moderation data...');
    $messages = $this->controller->executeStepWithMessages('clear');
    array_walk($messages, [$out, 'writeln']);

    if ($standalone) {
      $out->writeln('You should now be able to uninstall Workbench Moderation and install Content Moderation.');
    }
  }

  /**
   * Restores moderation state data from temporary migration tables.
   *
   * @command wbm2cm:restore
   * @aliases wbm2cm-restore
   */
  public function restore() {
    $out = $this->output();

    $out->writeln('Restoring moderation states from temporary tables...');
    $messages = $this->controller->executeStepWithMessages('restore');
    array_walk($messages, [$out, 'writeln']);
  }

  protected function reloadService($service) {
    return \Drupal::service($service->_serviceId);
  }

}
