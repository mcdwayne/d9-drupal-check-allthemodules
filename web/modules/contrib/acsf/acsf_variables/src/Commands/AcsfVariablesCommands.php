<?php

namespace Drupal\acsf_variables\Commands;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Drupal\acsf\AcsfException;
use Drush\Commands\DrushCommands;

/**
 * Provides drush commands for the acsf_variables module.
 */
class AcsfVariablesCommands extends DrushCommands {

  /**
   * Retrieves a named ACSF variable.
   *
   * @command acsf-vget
   *
   * @option exact Only retrieve the exact variable name specified.
   *
   * @param string $name
   *   The name of the variable to retrieve.
   * @param array $options
   *   The command options supplied to the executed command.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
   *   ProperyList of the variable.
   *
   * @throws \Drupal\acsf\AcsfException
   *   If the variable does not exist with the provided name or the
   *   acsf_variables module isn't enabled.
   * @throws \InvalidArgumentException
   *   If one or more arguments are missing or invalid.
   */
  public function vget($name, array $options = ['exact' => FALSE]) {
    if (!\Drupal::moduleHandler()->moduleExists('acsf_variables')) {
      throw new AcsfException(dt('The acsf_variables module must be enabled.'));
    }

    if (empty($name)) {
      throw new \InvalidArgumentException(dt('You must provide the name of the variable to retrieve as the first argument.'));
    }

    $exact = $options['exact'];

    if ($exact) {
      if (($value = \Drupal::service('acsf.variable_storage')->get($name)) && !is_null($value)) {
        $variables[$name] = $value;
      }
    }
    else {
      $variables = \Drupal::service('acsf.variable_storage')->getMatch($name);
    }

    if (!empty($variables)) {
      return new PropertyList($variables);
    }
    else {
      throw new AcsfException(dt('@name not found.', ['@name' => $name]));
    }
  }

  /**
   * Sets a named ACSF variable with an optional group.
   *
   * @command acsf-vset
   *
   * @option group An optional group name for the variable.
   *
   * @param string $name
   *   The name of the variable to set.
   * @param mixed $value
   *   The value of the variable to set.
   * @param array $options
   *   The command options supplied to the executed command.
   *
   * @throws \Drupal\acsf\AcsfException
   *   If the variable does not exist with the provided name or the
   *   acsf_variables module isn't enabled.
   * @throws \InvalidArgumentException
   *   If one or more arguments are missing or invalid.
   */
  public function vset($name, $value, array $options = ['group' => NULL]) {
    if (!\Drupal::moduleHandler()->moduleExists('acsf_variables')) {
      throw new AcsfException(dt('The acsf_variables module must be enabled.'));
    }

    if (empty($name)) {
      throw new \InvalidArgumentException(dt('You must provide the name of the variable to set as the first argument.'));
    }
    if (empty($value)) {
      throw new \InvalidArgumentException(dt('You must provide the value of the variable to set as the second argument.'));
    }

    if (\Drupal::service('acsf.variable_storage')->set($name, $value, $options['group'])) {
      $this->output()->writeln(dt('@name was set to !value', ['@name' => $name, '!value' => $value]));
    }
    else {
      throw new AcsfException(dt('The @name variable could not be set.'));
    }
  }

  /**
   * Retrieves a group of variables.
   *
   * @command acsf-vget-group
   *
   * @param string $group
   *   The group name of the variable to retrieve.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
   *   PropertyList of the variables.
   *
   * @throws \Drupal\acsf\AcsfException
   *   If the variable does not exist with the provided name or the
   *   acsf_variables module isn't enabled.
   * @throws \InvalidArgumentException
   *   If the argument is missing or invalid.
   */
  public function vgetGroup($group) {
    if (!\Drupal::moduleHandler()->moduleExists('acsf_variables')) {
      throw new AcsfException(dt('The acsf_variables module must be enabled.'));
    }

    if (empty($group)) {
      throw new \InvalidArgumentException(dt('You must provide the group name of the variables to retrieve as the first argument.'));
    }

    if ($data = \Drupal::service('acsf.variable_storage')->getGroup($group)) {
      return new PropertyList($data);
    }
    else {
      throw new AcsfException(dt('@group group not found.', ['@group' => $group]));
    }
  }

  /**
   * Deletes a named variable.
   *
   * @command acsf-vdel
   *
   * @param string $name
   *   The name of the variable to delete.
   *
   * @throws \Drupal\acsf\AcsfException
   *   If the variable does not exist with the provided name or the
   *   acsf_variables module isn't enabled.
   * @throws \InvalidArgumentException
   *   If the argument is missing or invalid.
   */
  public function vdel($name) {
    if (!\Drupal::moduleHandler()->moduleExists('acsf_variables')) {
      throw new AcsfException(dt('The acsf_variables module must be enabled.'));
    }

    if (empty($name)) {
      throw new \InvalidArgumentException(dt('You must provide the name of the variable to delete as the first argument.'));
    }

    $storage = \Drupal::service('acsf.variable_storage');
    if ($variable = $storage->get($name)) {
      if ($storage->delete($name)) {
        $this->output()->writeln(dt('@name was deleted.', ['@name' => $name]));
      }
      else {
        throw new AcsfException(dt('Unable to delete the @name variable.', ['@name' => $name]));
      }
    }
    else {
      throw new AcsfException(dt('@name not found.', ['@name' => $name]));
    }
  }

  /**
   * Retrieves info about a site.
   *
   * @command acsf-info
   *
   * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
   *   The site info list in var_export format.
   */
  public function info() {
    $data = \Drupal::service('acsf.variable_storage')->get('acsf_site_info', []);
    return new PropertyList($data);
  }

}
