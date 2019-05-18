<?php
/**
 * @file
 * Definition of Drupal\docker\DockerContainerConfig.
 */

namespace Drupal\docker\DockerContainerConfig;

class dockerContainerConfig {
  public $hostname;
  public $user;
  public $memory;
  public $memorySwap;
  public $attachStdin;
  public $attachStderr;
  public $portSpecs;
  public $privileged;
  public $tty;
  public $openStdin;
  public $env;
  public $cmd;
  public $dns;
  public $image;
  public $volumes;
  public $volumesFrom;
  public $workingDir;
}