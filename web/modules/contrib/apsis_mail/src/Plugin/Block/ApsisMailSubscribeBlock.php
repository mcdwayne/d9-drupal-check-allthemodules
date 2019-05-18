<?php

namespace Drupal\apsis_mail\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a simple block.
 *
 * @Block(
 *   id = "apsis_mail_subscribe_block",
 *   admin_label = @Translation("Apsis mail subscribe block")
 * )
 */
class ApsisMailSubscribeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $configFactory;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $apsis = \Drupal::service('apsis');
    $form = parent::blockForm($form, $form_state);
    $config = $this->configuration;

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body text'),
      '#default_value' => !empty($config['body']) ? $config['body']['value'] : '',
    ];

    // Get allowed mailing lists.
    $mailing_lists = $apsis->getAllowedMailingLists();

    // Get allowed demographic data.
    $demographic_data = $apsis->getAllowedDemographicData();

    // Display a link to the admin configuration,
    // if there is no allowed mailing lists.
    if (empty($mailing_lists)) {
      // Get admin link.
      $url = Url::fromRoute('apsis_mail.admin');
      $link = \Drupal::l($this->t('admin page'), $url);
      // Set no lists message.
      $message = $this->t('No allowed mailing lists are set, go to the @link to configure.', ['@link' => $link]);
      drupal_set_message($message, 'error');
    }

    else {
      if (!empty($mailing_lists)) {
        // Add 'exposed' option.
        $exposed = ['exposed' => $this->t('Let user choose')];
        $mailing_lists = $exposed + $mailing_lists;

        $form['mailing_list'] = [
          '#type' => 'select',
          '#title' => $this->t('Mailing list'),
          '#description' => $this->t('Mailing list to use'),
          '#options' => $mailing_lists,
          '#default_value' => !empty($config['mailing_list']) ? $config['mailing_list'] : 'exposed' ,
          '#required' => TRUE,
        ];
      }

      if (!empty($demographic_data)) {
        $form['demographic_data'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Show demographic data'),
          '#description' => $this->t('Expose demographic data fields to user.'),
          '#default_value' => !empty($config['demographic_data']) ? $config['demographic_data'] : '' ,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('body', $form_state->getValue('body'));
    $this->setConfigurationValue('mailing_list', $form_state->getValue('mailing_list'));
    $this->setConfigurationValue('demographic_data', $form_state->getValue('demographic_data'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configuration;

    if (empty($config['mailing_list'])) {
      // Set warning and return empty array if no list is selected.
      $msg = $this->t('@prefix No newsletter selected', ['@prefix' => 'Apsis mail block: ']);
      drupal_set_message($msg, 'warning');
      return [];
    }

    // Get form.
    $form = \Drupal::formBuilder()->getForm('Drupal\apsis_mail\Form\SubscribeForm', $config['mailing_list'], $config['demographic_data']);

    // Put body content into a render array.
    $body = [
      '#markup' => $config['body']['value'],
    ];

    $output = [
      '#theme' => 'apsis_mail_block',
      '#body' => $body,
      '#form' => $form,
    ];

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(
      parent::getCacheTags(),
      $this->configFactory->get('system.site')->getCacheTags()
    );
  }

}
