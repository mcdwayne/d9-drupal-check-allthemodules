<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\Form\AdvVarnishPurgeForm.
 */

namespace Drupal\adv_varnish\Form;

use Drupal\adv_varnish\AdvVarnishInterface;
use Drupal\adv_varnish\VarnishInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Render\Element\StatusMessages;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Configure varnish settings for this site.
 */
class AdvVarnishPurgeForm extends FormBase {

  /**
   * Stores the state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;
  protected $varnishHandler;

  /**
   * Constructs a AdvVarnishSettingsForm object.
   *
   * @param \Drupal\adv_varnish\AdvVarnishInterface $varnish_handler
   *   The factory for configuration objects.
   */
  public function __construct(VarnishInterface $varnish_handler) {
    $this->varnishHandler = $varnish_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('adv_varnish.handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adv_varnish_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['adv_varnish'] = [
      '#tree' => TRUE,
    ];

    // Display module status.
    $backend_status = $this->varnishHandler->varnishGetStatus();

    $_SESSION['messages'] = [];
    if (empty($backend_status)) {
      drupal_set_message(t('Varnish backend is not set.'), 'warning');
    }
    else {
      foreach ($backend_status as $backend => $status) {
        if (empty($status)) {
          drupal_set_message(t('Varnish at !backend not responding.', ['!backend' => $backend]), 'error');
        }
        else {
          drupal_set_message(t('Varnish at !backend connected.', ['!backend' => $backend]));
        }
      }
    }

    $form['adv_varnish_purge'] = array(
      '#title' => t('Purge settings'),
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => TRUE,
    );

    $options = [
      'tag' => $this->t('Tag'),
      'request' => $this->t('Request'),
    ];
    $form['adv_varnish_purge']['type'] = array(
      '#type' => 'radios',
      '#title' => t('Select purge type'),
      '#options' => $options,
      '#default_value' => $form_state->getValue('type') ?: 'request',
      '#required' => TRUE,
    );

    $form['adv_varnish_purge']['arguments'] = array(
      '#type' => 'textfield',
      '#title' => t('Tag or request to purge.'),
      '#default_value' => $form_state->getValue('arguments'),
      '#required' => TRUE,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Run purge'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $form_state->disableRedirect();

    // Get type.
    $type = $form_state->getValue('type');

    // Get arguments.
    $arguments = $form_state->getValue('arguments');

    // Purge specified request or tag in varnish.
    if ($type == 'tag') {

      // Prepare arguments.
      $arguments = explode(',', $arguments);
      $arguments = array_map('trim', $arguments);

      // Purge tags.
      $result = $this->varnishHandler->purgeTags($arguments);
    }
    else {
      $result = $this->varnishHandler->purgeRequest($arguments);
    }

    // Display information about results.
    if (empty($result)) {
      drupal_set_message(t('Server refuse to execute command.'), 'error');
    }
    else {
      foreach ($result as $server => $commands) {
        foreach ($commands as $command => $status) {
          drupal_set_message(t('Server %server executed command %command successfully.', array(
            '%server' => $server,
            '%command' => $command,
          )));
        }
      }
    }
  }

}
