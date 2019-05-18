<?php

/**
 * @file
 * Contains \Drupal\qyweixin\AgentInterface.
 */

namespace Drupal\qyweixin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for QiyeWeixin Agents.
 *
 * @see plugin_api
 */
interface AgentInterface extends PluginInspectionInterface, ConfigurablePluginInterface {
	public function agentGet();
	public function agentSet($agent);
	
	public function messageSend($body, $issafe=FALSE);
	
	public function menuDelete();
	public function menuGet();
}
