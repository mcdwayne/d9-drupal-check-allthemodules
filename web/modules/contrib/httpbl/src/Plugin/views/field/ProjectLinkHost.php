<?php

namespace Drupal\httpbl\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url as CoreUrl;
use Drupal\views\Plugin\views\field\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to present a project link for a host entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("host_ip")
 */
class ProjectLinkHost extends Url {

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    unset($options['display_as_link']);
    $options['display_profile_link'] = array('default' => TRUE);
    return $options;
  }

  /**
   * Provide link to the page being visited.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    unset ($form['display_as_link']);
    $form['display_profile_link'] = array(
      '#title' => $this->t('Display a link to the host\'s IP profile @ Project Honey Pot'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['display_profile_link']),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    if (!empty($this->options['display_profile_link'])) {
      return \Drupal::l($this->sanitizeValue($value), CoreUrl::fromUri('http://www.projecthoneypot.org/search_ip.php?ip=' . $value));
    }
    else {
      return $this->sanitizeValue($value, 'url');
    }
  }

}
