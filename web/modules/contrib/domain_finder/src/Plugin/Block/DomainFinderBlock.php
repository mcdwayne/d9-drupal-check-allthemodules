<?php

namespace Drupal\domain_finder\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Domain finder form' block.
 *
 * @Block(
 *   id = "domain_finder_form_block",
 *   admin_label = @Translation("Domain finder form"),
 *   module = "domain_finder"
 * )
 */
class DomainFinderBlock extends BlockBase {

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    $config = $this->getConfiguration();
    return \Drupal::formBuilder()->getForm('Drupal\domain_finder\Form\DomainFinderSearchForm', $config);
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    $id = '';
    if (!empty($build_info['callback_object'])) {
      if ($entity = $build_info['callback_object']->getEntity()) {
        if (is_object($entity)) {
          $id = $build_info['callback_object']->getEntity()->id();
        }
      }
    }

    $form = parent::blockForm($form, $form_state);

    $form['block_id'] = [
      '#type' => 'hidden',
      '#value' => $id,
    ];

    //Retrieve existing configuration for this block.
    $config = $this->getConfiguration();
    $domains_in_form = FALSE;
    $domains = [];
    if (empty($config['domains_in_form']) && empty($config['domains'])) {
      $config = \Drupal::config('domain_finder.settings');
      $domains_in_form = $config->get('domains_in_form');
      $domains = $config->get('domains');
    }
    else {
      $domains_in_form = isset($config['domains_in_form']) ? $config['domains_in_form'] : FALSE;
      $domains = isset($config['domains']) ? $config['domains'] : [];
    }

    require_once drupal_get_path('module', 'domain_finder') . '/includes/domain_finder.domains.inc';

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Domains setings'),
      '#description' => t('This configuration page provide the domain extension list. So please choose the domain extensions you want to find.'),
      '#open' => FALSE,
    ];

    $form['settings']['domain_finder_domains_in_form'] = [
      '#type' => 'checkbox',
      '#title' => t('Show checkboxes on domain finder form'),
      '#default_value' => $domains_in_form,
    ];

    $available_domains = domain_finder_get_domains();
    $form['settings']['domain_finder_domains'] = [
      '#type' => 'checkboxes',
      '#title' => t('Available domain extensions'),
      '#options' => array_combine($available_domains['basic']['domains'], $available_domains['basic']['domains']),
      '#default_value' => $domains,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $settings = $form_state->getValue(['settings', 'domain_finder_domains']);
    $domain_exts = array_filter($settings);
    if (empty($domain_exts)) {
      $form_state->setErrorByName('domain_finder_domains', $this->t('Please chose least one domain extension from list.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    if (!$form_state->getErrors()) {
      $settings = $form_state->getValue('settings');
      $this->setConfigurationValue('domains_in_form', $settings['domain_finder_domains_in_form']);
      $this->setConfigurationValue('domains', $settings['domain_finder_domains']);
      $this->setConfigurationValue('block_id', $form_state->getValue('block_id'));
    }
  }

}
