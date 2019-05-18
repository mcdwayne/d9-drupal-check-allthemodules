<?php

namespace Drupal\plus\Http;

use Drupal\Component\Utility\ToStringTrait;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Url;

class UserAgent {

  use ToStringTrait;

  /**
   * A static list of extension types.
   *
   * @var array
   */
  protected static $extensionTypes = ['module', 'profile', 'theme', 'theme_engine'];

  /**
   * The list of user agents.
   *
   * @var array
   */
  protected $userAgents = [];

  /**
   * UserAgent constructor.
   *
   * @param string $user_agent
   *   The default user agent to add. If NULL, the default user agents will be
   *   added.
   */
  public function __construct($user_agent = NULL) {
    // Add default user agents.
    if ($user_agent === NULL) {
      $this->add(\GuzzleHttp\default_user_agent());
      $this->add('Drupal', \Drupal::VERSION, 'https://www.drupal.org/');
      $this->add('module:plus');
    }
    else {
      $this->add($user_agent);
    }
  }

  /**
   * Adds a user agent to the list.
   *
   * @param string|\Drupal\Core\Extension\Extension $user_agent
   *   A user agent label. This can be a string representation of an extension,
   *   e.g. "type:name", where "type" is the extension type and "name" is the
   *   machine name of the extension, e.g.: "module:block" or "theme:bartik".
   *   It may also be an direct Extension object.
   * @param string $version
   *   Optional. The version of the user agent. If not provided and $user_agent
   *   is an extension, this will automatically be determined as the version
   *   of the extension that is currently installed.
   * @param string $url
   *   Optional. The URL of the extension. If not provided and $user_agent is
   *   an extension, this will automatically default to prefixing the
   *   machine name of the extension with: "https://www.drupal.org/project/".
   *   In the event that the extension is not an actual project on drupal.org,
   *   you may wish to provide an alternate URL or explicitly set this to FALSE
   *   if you wish to not include a URL for the user agent.
   *
   * @return static
   */
  public function add($user_agent, $version = NULL, $url = NULL) {
    // Convert a string representation of an extension into a proper object.
    if (is_string($user_agent) && strpos($user_agent, ':') !== FALSE) {
      list ($type, $name) = explode(':', $user_agent . ':');
      if (in_array($type, static::$extensionTypes)) {
        $user_agent = $this->getExtensionByName($type, $name);
      }
    }

    if ($user_agent instanceof Extension) {
      $extension = $user_agent;
      $info = $this->getExtensionInfo($extension);
      $project = $this->getExtensionProject($extension);
      $project_info = $project ? $this->getExtensionInfo($project) : [];

      // Determine the user agent to use.
      $user_agent = $project_info ? $project_info['name'] : $info['name'];

      // Determine the version to use.
      if ($version === NULL) {
        $version = isset($info['version']) ? $info['version'] : \Drupal::CORE_COMPATIBILITY . '-dev';
      }

      // Determine the URL to use, if any.
      if ($url === NULL && $project) {
        $url = $project ? "https://www.drupal.org/project/{$project->getName()}" : NULL;
      }
    }

    $value = (string) $user_agent;
    if ($version) {
      $value .= "/$version";
    }
    if ($url) {
      if ($url instanceof Url) {
        $url = $url->toString();
      }
      $value .= " (+$url)";
    }

    $this->userAgents[] = $value;

    return $this;
  }

  /**
   * Retrieves the info array for an Extension object.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   The Extension object to retrieve the info for.
   *
   * @return array
   *   The Extension object info array.
   */
  protected function getExtensionInfo(Extension $extension) {
    // @todo Replace with proper service usage in 8.6.x.
    // @see https://www.drupal.org/node/2709919
    return system_get_info($extension->getType(), $extension->getName());
  }

  /**
   * Retrieves an Extension object by type and name.
   *
   * @param string $type
   *   The type of extension.
   * @param string $name
   *   The machine name of the extension.
   *
   * @return \Drupal\Core\Extension\Extension|null
   */
  protected function getExtensionByName($type, $name) {
    // @todo Replace with proper service usage in 8.6.x.
    // @see https://www.drupal.org/node/2709919
    $list = $type === 'theme' ? system_list($type) : \Drupal::moduleHandler()->getModuleList();

    /** @var \Drupal\Core\Extension\Extension $extension */
    return isset($list[$name]) ? $list[$name] : NULL;
  }

  /**
   * Retrieves the Extension object for the project of an Extension.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   The Extension object to retrieve the project Extension object for.
   *
   * @return \Drupal\Core\Extension\Extension|null
   */
  protected function getExtensionProject(Extension $extension) {
    $info = $this->getExtensionInfo($extension);
    return !empty($info['project']) ? $this->getExtensionByName($extension->getType(), $info['project']) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return implode(' ', array_reverse($this->userAgents));
  }

  /**
   * Resets the entire UserAgent instance.
   *
   * @return static
   */
  public function reset() {
    $this->userAgents = [];
    return $this;
  }

}
