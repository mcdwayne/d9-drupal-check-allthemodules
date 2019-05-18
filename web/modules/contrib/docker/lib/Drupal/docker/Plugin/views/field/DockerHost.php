<?php

/**
 * @file
 * Definition of Drupal\docker\Plugin\views\field\DockerHost.
 */

namespace Drupal\docker\Plugin\views\field;

use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to allow linking to a docker host.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("docker_host")
 */
class DockerHost extends FieldPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::init().
   *
   * Provide generic option to link to docker host.
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->options['link_to_docker_host'])) {
      $this->additional_fields['dhid'] = 'dhid';
    }
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_docker_host'] = array('default' => TRUE, 'bool' => TRUE);

    return $options;
  }

  /**
   * Provide link-to-docker-host option
   */
  public function buildOptionsForm(&$form, &$form_state) {
    $form['link_to_docker_host'] = array(
      '#title' => t('Link this field to its host'),
      '#description' => t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_docker_host'],
    );
    parent::buildOptionsForm($form, $form_state);
  }

  protected function renderLink($data, ResultRow $values) {
    if (!empty($this->options['link_to_docker_host'])) {
      $this->options['alter']['make_link'] = TRUE;
      $dhid = $this->getValue($values, 'dhid');
      if (!empty($cid)) {
        $this->options['alter']['path'] = "docker/host/" . $dhid;
      }
    }

    return $data;
  }

}
