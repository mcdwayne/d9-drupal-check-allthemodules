<?php

namespace Drupal\twig_addons;

class TwigExtensions {
  public $extensionClasses = [];
  public $extensions = [];

  /**
   * TwigExtensions constructor.
   * @param \Drupal\Core\Template\TwigEnvironment $env
   * @param \Drupal\twig_addons\GetInfo $info
   */
  public function __construct(\Drupal\Core\Template\TwigEnvironment $env, $info) {
    if (empty($info->extensionClasses)) {
      return;
    }
    $this->extensionClasses = $info->extensionClasses;

    foreach ($this->extensionClasses as $extensionClass) {
      $extension = new $extensionClass();
      $env->addExtension($extension);
      $this->extensions[] = $extension;
    }
  }
}
