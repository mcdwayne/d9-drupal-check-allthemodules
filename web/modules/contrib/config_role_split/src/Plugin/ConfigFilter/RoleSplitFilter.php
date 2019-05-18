<?php

namespace Drupal\config_role_split\Plugin\ConfigFilter;

use Drupal\config_filter\Plugin\ConfigFilterBase;

/**
 * Provides a RoleSplitFilter.
 *
 * @ConfigFilter(
 *   id = "role_split",
 *   label = @Translation("Role Split"),
 *   storages = {"config.storage.sync"},
 *   deriver = "\Drupal\config_role_split\Plugin\ConfigFilter\RoleSplitDeriver"
 * )
 */
class RoleSplitFilter extends ConfigFilterBase {

  /**
   * {@inheritdoc}
   */
  public function filterRead($name, $data) {
    if (!$this->isManagedRole($name)) {
      return parent::filterRead($name, $data);
    }

    $id = $data['id'];
    switch ($this->getPluginProperty('mode', 'split')) {
      case 'split':
      case 'fork':
        // Merge the permissions.
        $data['permissions'] = $this->mergePermissions($data['permissions'], $this->getPermissions($id));
        break;

      case 'exclude':
        // Remove the permissions when reading.
        $data['permissions'] = array_diff($data['permissions'], $this->getPermissions($id));
        sort($data['permissions']);
        break;
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterWrite($name, array $data) {
    if (!$this->isManagedRole($name)) {
      return parent::filterWrite($name, $data);
    }

    $id = $data['id'];
    switch ($this->getPluginProperty('mode', 'split')) {
      case 'split':
        // Remove the permissions when exporting.
        $data['permissions'] = array_diff($data['permissions'], $this->getPermissions($data['id']));
        sort($data['permissions']);
        break;

      case 'fork':
        // Remove the permissions from exporting that are not already exported.
        $permissions = array_diff($this->getPermissions($id), $this->getSourcePermissions($id));
        $data['permissions'] = array_diff($data['permissions'], $permissions);
        sort($data['permissions']);
        break;

      case 'exclude':
        // Add the permissions from the config if they are already exported.
        $permissions = array_intersect($this->getPermissions($id), $this->getSourcePermissions($id));
        $data['permissions'] = $this->mergePermissions($data['permissions'], $permissions);
        break;
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterReadMultiple(array $names, array $data) {
    foreach ($names as $name) {
      if ($this->isManagedRole($name)) {
        // Filter managed roles individually.
        $data[$name] = $this->filterRead($name, $data[$name]);
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterDelete($name, $delete) {
    if (!$this->isManagedRole($name)) {
      return parent::filterDelete($name, $delete);
    }

    if (in_array($this->getPluginProperty('mode', 'split'), ['fork', 'exclude'])) {
      // We do not remove the role, this may lead to roles not being removed
      // when they should be because there is still a filter that manages it
      // even though the role was deleted from the active storage.
      // To solve this we would have to inject the active storage and perform an
      // exists($name) call on it to see if it should be kept.
      return FALSE;
    }

    return $delete;
  }

  /**
   * {@inheritdoc}
   */
  public function filterDeleteAll($prefix, $delete) {
    if (in_array($this->getPluginProperty('mode', 'split'), ['fork', 'exclude'])) {
      // Do not delete all for fork and exclude filters.
      return FALSE;
    }
    return parent::filterDeleteAll($prefix, $delete);
  }

  /**
   * Return whether the configuration is a managed role that needs filtering.
   *
   * @param string $name
   *   The name of the config to check.
   *
   * @return bool
   *   Whether the config is a role that is managed by this filter.
   */
  protected function isManagedRole($name) {
    if (strpos($name, 'user.role.') === 0) {
      if ($this->getPermissions(str_replace('user.role.', '', $name))) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get the permissions for a given role id.
   *
   * @param string $id
   *   The role id.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getPermissions($id) {
    $roles = $this->getPluginProperty('roles', []);
    // Here we could do more fancy things like wildcards.
    if (array_key_exists($id, $roles) && !empty($roles[$id])) {
      return $roles[$id];
    }
    return [];
  }

  /**
   * Get the plugin property from the storage or the plugin configuration.
   *
   * @param string $name
   *   The property name to get.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The configuration property
   */
  protected function getPluginProperty($name, $default) {
    // Try first reading from the filtered storage.
    if ($this->getFilteredStorage()) {
      $config = $this->getFilteredStorage()->read($this->configuration['config_name']);
      if (is_array($config) && array_key_exists($name, $config)) {
        // This ensures that the deployment will work,
        // but it makes overrides impossible.
        return $config[$name];
      }
    }
    // Otherwise return the value passed as plugin configuration.
    if (isset($this->configuration[$name])) {
      return $this->configuration[$name];
    }
    return $default;
  }

  /**
   * Do an array_merge with array_unique and sorting.
   *
   * @param array $original
   *   The first array.
   * @param array $addition
   *   The second array.
   *
   * @return array
   *   The merged array
   */
  protected function mergePermissions(array $original, array $addition) {
    $permissions = array_unique(array_merge($original, $addition));
    sort($permissions);
    return $permissions;
  }

  /**
   * Get the permissions of the configuration in the source storage.
   *
   * @param string $id
   *   The role id.
   *
   * @return string[]
   *   The permissions of the role in the unfiltered storage.
   */
  protected function getSourcePermissions($id) {
    if ($this->getSourceStorage()) {
      $config = $this->getSourceStorage()->read('user.role.' . $id);
      if (is_array($config)) {
        return $config['permissions'];
      }
    }
    return [];
  }

}
