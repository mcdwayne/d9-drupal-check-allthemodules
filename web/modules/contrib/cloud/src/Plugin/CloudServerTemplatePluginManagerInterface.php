<?php

namespace Drupal\cloud\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\cloud\Entity\CloudServerTemplateInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for cloud_server_template_plugin managers.
 */
interface CloudServerTemplatePluginManagerInterface extends PluginManagerInterface {

  /**
   * Load a plugin using the cloud_context.
   *
   * @param string $cloud_context
   *   Cloud context string.
   *
   * @return CloudServerTemplatePluginInterface
   *   loaded CloudServerTemplatePlugin.
   */
  public function loadPluginVariant($cloud_context);

  /**
   * Launch a cloud server template.
   *
   * @param \Drupal\cloud\Entity\CloudServerTemplateInterface $cloud_server_template
   *   A Cloud Server Template  object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state if launch is called from a form.
   *
   * @return mixed
   *   An associative array with a redirect route and any parameters to build
   *   the route.
   */
  public function launch(CloudServerTemplateInterface $cloud_server_template, FormStateInterface $form_state = NULL);

}
