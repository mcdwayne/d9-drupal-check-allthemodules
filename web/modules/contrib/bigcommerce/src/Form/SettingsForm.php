<?php

namespace Drupal\bigcommerce\Form;

use BigCommerce\Api\v3\ApiClient;
use BigCommerce\Api\v3\Api\CatalogApi;
use BigCommerce\Api\v3\ApiException;
use BigCommerce\Api\v3\Model\Channel;
use BigCommerce\Api\v3\Model\CreateChannelRequest;
use BigCommerce\Api\v3\Model\Site;
use BigCommerce\Api\v3\Model\SiteCreateRequest;
use Drupal\bigcommerce\ClientFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure example settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestContext $request_context, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->requestContext = $request_context;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('router.request_context'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bigcommerce_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bigcommerce.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bigcommerce.settings');

    if (!$this->entityTypeManager->getStorage('commerce_store')->loadDefault()) {
      $this->messenger()->addError(
        $this->t(
          'A default commerce store must exist before BigCommerce can be used. <a href=":create_store">Create a commerce store</a>.',
          [':create_store' => Url::fromRoute('entity.commerce_store.add_page')->toString()])
      );
    }

    $form['#wrapper_id'] = Html::getUniqueId('js-bigcommerce-settings');
    $form['#prefix'] = '<div id="' . $form['#wrapper_id'] . '">';
    $form['#suffix'] = '</div>';

    $form['connection_status'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Connection status'),
      '#access' => FALSE,
    ];
    $form['connection_status']['message'] = [
      '#markup' => $this->t('Connected successfully.'),
      '#type' => 'item',
      '#wrapper_attributes' => [
        'class' => ['messages', 'messages--status'],
      ],
    ];

    $form['api_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API Credentials'),
      '#tree' => TRUE,
    ];

    $form['api_settings']['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Path'),
      '#description' => $this->t('The API Path of your BigCommerce store.'),
      '#default_value' => $config->get('api.path'),
      '#placeholder' => 'https://api.bigcommerce.com/stores/STORE_ID/v3/',
      '#required' => TRUE,
    ];

    // Used with the access token to make API calls.
    $form['api_settings']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#description' => $this->t('The API Client ID from BigCommerce.'),
      '#default_value' => $config->get('api.client_id'),
      '#required' => TRUE,
    ];

    // @TODO Where is this used?
    $form['api_settings']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#description' => $this->t('The API Client ID from BigCommerce.'),
      '#default_value' => $config->get('api.client_secret'),
    ];

    // Used with the client id to make API calls.
    $form['api_settings']['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#description' => $this->t('The API Access Token from BigCommerce.'),
      '#default_value' => $config->get('api.access_token'),
      '#required' => TRUE,
    ];

    $form['channel'] = [
      '#type' => 'details',
      '#title' => $this->t('Channel Settings'),
      '#open' => TRUE,
    ];

    // Test the connection if we have some details. This is not done in
    // validation so that this configuration page acts as a connection status
    // page too.
    if ($config->get('api.path')) {
      $form['connection_status']['#access'] = TRUE;
      $failed_message = $this->testConnection($config->get('api'));
      if ($failed_message) {
        $form['connection_status']['message']['#markup'] = $failed_message;
        $form['connection_status']['message']['#wrapper_attributes']['class'] = ['messages', 'messages--error'];
      }
    }

    $has_channel_id = mb_strlen($config->get('channel_id')) > 0;
    $form['channel']['no_channel'] = [
      '#markup' => $this->t('No channel is currently configured, once you provide valid API credentials this can be configured.'),
      '#access' => !$has_channel_id,
    ];

    $options = ['Create New Channel' => $this->t('Create New Channel')];
    if ((bool) $config->get('api.path')) {
      $options += $this->getChannels();
    }

    $form['channel']['channel_select'] = [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $has_channel_id ? $config->get('channel_id') : NULL,
      '#title' => $this->t('Select channel'),
      '#description' => $this->t('Channel you wish to pair with your Drupal site.'),
      '#access' => $config->get('api.path') && !empty($options),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefresh'],
        'wrapper' => $form['#wrapper_id'],
      ],
    ];

    $new_channel = $form_state->getValue('channel_select') == 'Create New Channel' || !$has_channel_id;
    $form['channel']['create_new_channel_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('New Channel Name'),
      '#description' => $this->t('Channel Name from BigCommerce, user friendly tag used to identify 3rd party sales channels like Drupal or Amazon.'),
      '#access' => $new_channel && (bool) $config->get('api.path'),
    ];
    $form['channel']['create_new_channel_submit'] = [
      '#type' => 'submit',
      '#value' => t('Create new BigCommerce channel'),
      '#submit' => ['::createNewChannel'],
      '#access' => $new_channel && (bool) $config->get('api.path'),
    ];

    $site = $has_channel_id ? $this->getSiteForChannel($config->get('channel_id')) : NULL;

    $form['channel']['site_id'] = [
      '#type' => 'item',
      '#title' => $this->t('Site ID'),
      '#description' => $this->t('Site ID for BigCommerce, always attached to a channel and links to a specific URL.'),
      '#markup' => $site ? $site->getId() : '',
      '#access' => !$new_channel && $site,
    ];
    $form['channel']['site_url'] = [
      '#type' => 'item',
      '#title' => $this->t('Site URL'),
      '#description' => $this->t('Site URL for BigCommerce, must match your Drupal URL for the checkout to load.'),
      '#markup' => $site ? $site->getUrl() : '',
      '#access' => !$new_channel && $site,
    ];
    $form['channel']['update_site_url'] = [
      '#type' => 'submit',
      '#value' => t('Update BigCommerce Site URL'),
      '#submit' => ['::updateSiteUrl'],
      '#access' => !$new_channel && $site,
    ];
    $form['channel']['create_site'] = [
      '#type' => 'submit',
      '#value' => t('Create BigCommerce Site'),
      '#submit' => ['::setupSite'],
      '#access' => !$new_channel && !$site,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Lists all the available channels.
   *
   * @return string[]
   *   List of channel names keyed by channel ID.
   */
  protected function getChannels() {
    try {
      $channels = (array) \Drupal::service('bigcommerce.channels')->listChannels()->getData();
    }
    catch (ApiException $e) {
      $channels = [];
    }
    $channels = array_filter($channels, function (Channel $channel) {
      return $channel->getPlatform() === CreateChannelRequest::PLATFORM_DRUPAL;
    });

    $list = [];
    foreach ($channels as $channel) {
      $list[$channel->getId()] = $channel->getName();
    }
    natsort($list);
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('bigcommerce.settings');

    $config->set('api.path', $form_state->getValue(['api_settings', 'path']))
      ->set('api.access_token', $form_state->getValue(['api_settings', 'access_token']))
      ->set('api.client_id', $form_state->getValue(['api_settings', 'client_id']))
      ->set('api.client_secret', $form_state->getValue(['api_settings', 'client_secret']));

    if ($form_state->getValue(['channel_select']) != 'Create New Channel') {
      $config->set('channel_id', $form_state->getValue(['channel_select']));
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Tests the API connection configuration.
   *
   * @param array $settings
   *   An array based on bigcommerce.settings:api.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   Returns a TranslatableMarkup with the connection error or NULL if there
   *   is no error.
   */
  protected function testConnection(array $settings) {
    try {
      // Note this uses creates the CatalogAPI manually in order to provide a
      // connection tester not dependent on configuration.
      $base_client = new ApiClient(ClientFactory::createApiConfiguration($settings));
      $catalog_client = new CatalogApi($base_client);
      $catalog_client->catalogSummaryGet();
    }
    catch (\Exception $e) {
      return $this->t(
        'There was an error connecting to the BigCommerce API ( <a href=":status_url">System Status</a> | <a href=":contact_url">Contact Support</a> ). Connection failed due to: %message',
        [
          ':status_url' => 'http://status.bigcommerce.com/',
          ':contact_url' => 'https://support.bigcommerce.com/contact',
          '%message' => $e->getMessage(),
        ]
      );
    }

    return NULL;
  }

  /**
   * Creates a BigCommerce channel.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function createNewChannel(array &$form, FormStateInterface $form_state) {
    $config = $this->config('bigcommerce.settings');
    try {
      /** @var \BigCommerce\Api\v3\Api\ChannelsApi $channel_api */
      $channel_api = \Drupal::service('bigcommerce.channels');

      $create_channel_request = new CreateChannelRequest();
      $create_channel_request->setType(CreateChannelRequest::TYPE_STOREFRONT);
      $create_channel_request->setPlatform(CreateChannelRequest::PLATFORM_DRUPAL);
      $create_channel_request->setName($form_state->getValue('create_new_channel_name'));

      $response = $channel_api->createChannel($create_channel_request);
      $channel = $response->getData();

      $this->config('bigcommerce.settings')
        ->set('channel_id', $channel->getId())
        ->save();

      $this->messenger()->addStatus($this->t('Created new BigCommerce channel %channel', ['%channel' => $channel->getName()]));
      // Automatically create a site for the new channel.
      $this->setupSite();
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t(
        'There was an error setting up a channel via the BigCommerce API ( <a href=":status_url">System Status</a> | <a href=":contact_url">Contact Support</a> ). Connection failed due to: %message',
        [
          ':status_url' => 'http://status.bigcommerce.com/',
          ':contact_url' => 'https://support.bigcommerce.com/contact',
          '%message' => $e->getMessage(),
        ]
      ));
    }
  }

  /**
   * Creates a BigCommerce site.
   */
  public function setupSite() {
    $config = $this->config('bigcommerce.settings');

    // See if a site already exists.
    $site = $this->getSiteForChannel($config->get('channel_id'));

    // Create a site if we need to.
    if (empty($site) || empty($site->getId())) {
      /** @var \BigCommerce\Api\v3\Api\SitesApi $sites_api */
      $sites_api = \Drupal::service('bigcommerce.sites');
      try {
        $site_create_request = new SiteCreateRequest();
        $site_create_request->setChannelId($config->get('channel_id'));
        $site_create_request->setUrl(rtrim($this->requestContext->getCompleteBaseUrl(), '/'));

        // The API lists both passing the channel id and adding it as a parameter.
        $sites_api->postChannelSite($config->get('channel_id'), $site_create_request);
      }
      catch (\Exception $e) {
        $this->messenger()->addError($this->t(
          'There was an error setting up a site via the BigCommerce API ( <a href=":status_url">System Status</a> | <a href=":contact_url">Contact Support</a> ). Connection failed due to: %message',
          [
            ':status_url' => 'http://status.bigcommerce.com/',
            ':contact_url' => 'https://support.bigcommerce.com/contact',
            '%message' => $e->getMessage(),
          ]
        ));
      }
    }
  }

  /**
   * Updates a Site URL.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function updateSiteUrl(array &$form, FormStateInterface $form_state) {
    $config = $this->config('bigcommerce.settings');
    /** @var \BigCommerce\Api\v3\Api\SitesApi $sites_api */
    $sites_api = \Drupal::service('bigcommerce.sites');

    // See if a site already exists.
    try {
      $site_id = $this->getSiteForChannel($config->get('channel_id'))->getId();

      $body = new Site([
        'url' => rtrim($this->requestContext->getCompleteBaseUrl(), '/'),
      ]);
      $sites_api->putSite($site_id, $body);
    }
    catch (ApiException $e) {
      $this->messenger()->addError($this->t('Failed to update site url due to error: %error', ['%error' => $e->getMessage()]));
    }
  }

  /**
   * Gets a BigCommerce Site object for the specified channel.
   *
   * @param $channel_id
   *   The channel ID to get the site for.
   * @param bool $throw_exception
   *   (optional) Whether to throw an exception on error. Defaults to FALSE.
   *
   * @return \BigCommerce\Api\v3\Model\Site|null
   *   The BigCommerce Site object.
   *
   * @throws \BigCommerce\Api\v3\ApiException
   *   Exception thrown if $throw_exception is TRUE and there is problem
   *   communicating with BigCommerce.
   */
  protected function getSiteForChannel($channel_id, $throw_exception = FALSE) {
    $site = NULL;
    /** @var \BigCommerce\Api\v3\Api\SitesApi $sites_api */
    $sites_api = \Drupal::service('bigcommerce.sites');
    try {
      $response = $sites_api->getChannelSite($channel_id);
      $site = $response->getData();
    }
    catch (ApiException $e) {
      if ($throw_exception) {
        throw $e;
      }
    }
    return $site;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form;
  }

}
