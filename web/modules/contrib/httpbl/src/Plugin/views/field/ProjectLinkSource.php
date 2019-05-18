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
 * @ViewsField("host_source")
 */
class ProjectLinkSource extends Url {

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
      '#title' => $this->t('Links to original Project Honey Pot profile.'),
      '#description' => $this->t('This link is active only when the evaluated host is still originally sourced and has not been admin managed.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['display_profile_link']),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    $host_ip = $values->httpbl_host_host_ip;
    if (!empty($this->options['display_profile_link']) && $value == t(HTTPBL_ORIGINAL_SOURCE)) {
      return \Drupal::l($this->sanitizeValue($value), CoreUrl::fromUri('http://www.projecthoneypot.org/search_ip.php?ip=' . $host_ip));
    }
    else {
      return $this->sanitizeValue($value, 'url');
    }
  }

}
