<?php

/**
 * @file
 * Contains \Drupal\adv_varnish\Form\AdvVarnishDeflateForm.
 */

namespace Drupal\adv_varnish\Form;

use Drupal\adv_varnish\AdvVarnishInterface;
use Drupal\adv_varnish\VarnishInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Configure varnish settings for this site.
 */
class AdvVarnishDeflateForm extends ConfigFormBase {

  /**
   * Stores the state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;
  protected $varnishHandler;

  /**
   * Constructs a AdvVarnishDeflateForm.php object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, VarnishInterface $varnish_handler) {
    parent::__construct($config_factory);
    $this->state = $state;
    $this->varnishHandler = $varnish_handler;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state'),
      $container->get('adv_varnish.handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adv_varnish_deflate';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['adv_varnish.deflate'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('adv_varnish.deflate');

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

    // Get info for progress bar.
    $account = \Drupal::currentUser();
    $deflate_info = $config->get('info');
    $deflate_ids = $config->get('ids');

    if (!empty($deflate_info)) {
      $form['deflate_info'] = array(
        '#title' => t('Deflate cache info'),
        '#type' => 'details',
        '#tree' => TRUE,
        '#open' => TRUE,
      );

      $form['deflate_info']['deflate_info1'] = array(
        '#type' => 'item',
        '#title' => t('Last deflation Info'),
        '#markup' => t('User = @name (@uid), Date = @date, Step = @step%, Key = @key', array(
          '@name' => $account->getUsername(),
          '@uid' => $deflate_info['uid'],
          '@date' => date('c', $deflate_info['time']),
          '@step' => $deflate_info['step'],
          '@key' => $deflate_info['key'],
        )),
      );
      $progress = 100 - count($deflate_ids);

      if ($progress < 100) {
        $build = array(
          '#theme' => 'progress_bar',
          '#percent' => $progress,
          '#message' => $this->t('Progress is not updated via ajax.'),
          '#label' => $this->t('Deflate progress.'),
        );
        $progress_bar = \Drupal::service('renderer')->renderPlain($build);
      }
      else {
        $progress_bar = t('Completed');
      }

      $form['deflate_info']['deflate_info2'] = array(
        '#type' => 'item',
        '#title' => t('Last deflation progress'),
        '#markup' => $progress_bar,
        '#suffix' => '<br />',
      );
    }

    // Step size.
    $options = array(
      '1' => '1%',
      '2' => '2%',
      '5' => '5%',
      '10' => '10%',
      '20' => '20%',
      '50' => '50%',
      '100' => '100',
    );
    $form['deflate'] = array(
      '#title' => t('Deflate cache'),
      '#type' => 'details',
      '#description' => t('Deflation is a process that will slowly invalidate all Varnish cache on cron runs.'),
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['deflate']['step'] = array(
      '#title' => t('Step size'),
      '#description' => t('Amount of cache that will be invalidated on each deflation step.'),
      '#type' => 'select',
      '#default_value' => '10',
      '#options' => $options,
    );
    $form['deflate']['start'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Start deflation'),
      '#button_type' => 'primary',
    );

    // By default, render the form using theme_system_config_form().
    $form['#theme'] = 'system_config_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('deflate');

    // Get current user.
    $account = \Drupal::currentUser();

    $deflate_info = array(
      'key' => $this->uniqueId(),
      'time' => time(),
      'uid' => $account->id(),
      'step' => $values['step'],
    );

    $deflate_ids = array();
    for ($i = 0; $i < 100; $i++) {
      $deflate_ids[] = str_pad($i, 2, '0', STR_PAD_LEFT);
    }

    $this->config('adv_varnish.deflate')
      ->set('info', $deflate_info)
      ->set('ids', $deflate_ids)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Generated unique id based on time.
   *
   * @return string
   *   Unique id.
   */
  protected static function uniqueId() {
    $id = uniqid(time(), TRUE);
    return substr(md5($id), 5, 10);
  }

}
