<?php

namespace Drupal\gclient_storage\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Site\Settings;
use Drupal\integro\ConnectorManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure google storage.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The connector manager.
   *
   * @var \Drupal\integro\ConnectorManagerInterface
   */
  protected $connectorManager;

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\integro\ConnectorManagerInterface $connector_manager
   *   The connector manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ConnectorManagerInterface $connector_manager) {
    parent::__construct($config_factory);

    $this->connectorManager = $connector_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('integro_connector.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gclient_storage_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['gclient_storage.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gclient_storage.settings');

    $form['gclient_storage'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('GClient Storage'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $connector_options = ['' => $this->t('- Select -')] + $this->connectorManager->getOptions();

    $form['gclient_storage']['integro_connector'] = [
      '#type' => 'select',
      '#options' => $connector_options,
      '#title' => $this->t('Connector'),
      '#required' => TRUE,
      '#default_value' => $config->get('integro_connector'),
    ];

    $form['advanced_storage'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Storage settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['advanced_storage']['storage_root'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Root Folder'),
      '#default_value' => $config->get('storage_root'),
      '#description' => $this->t(
        "GClient Storage uses the specified folder as the root of the file system within your bucket (if blank, the bucket
      root is used). This is helpful when your bucket is used by multiple sites, or has additional data in it which
      this integration should not interfere with.<br>
      The metadata refresh function will not retrieve metadata for any files which are outside the Root Folder.<br>
      This setting is case sensitive. Do not include leading or trailing slashes.<br>
      Changing this setting <b>will not</b> move any files. For example, if you've already uploaded files through storage browser,
      you will need to manually move them into this folder."
      ),
    ];
    $form['advanced_storage']['storage_download_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Force Downloading'),
      '#default_value' => $config->get('storage_download_paths'),
      '#rows' => 5,
      '#description' => $this->t(
        'A list of paths for which users will be forced to download the file, rather than displaying it in the browser.<br>
      Enter one value per line. e.g. "video/*". Paths use regex patterns as per @preg_match.',
        [
          '@preg_match' => Link::fromTextAndUrl($this->t('preg_match'), Url::fromUri('http://php.net/preg_match'))->toString(),
        ]
      ),
    ];
    $form['advanced_storage']['storage_signed_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Signed URLs'),
      '#default_value' => $config->get('storage_signed_paths'),
      '#rows' => 5,
      '#description' => $this->t(
        'A list of timeouts and paths that should be delivered through a @signed_url.<br>
      Enter one value per line, in the format timeout|path. e.g. "60|private_files/*". Paths use regex patterns
      as per @preg_match. If no timeout is provided, it defaults to 60 seconds.',
        [
          '@signed_url' => Link::fromTextAndUrl($this->t('signed url'), Url::fromUri('https://cloud.google.com/storage/docs/access-control/signed-urls'))->toString(),
          '@preg_match' => Link::fromTextAndUrl($this->t('preg_match'), Url::fromUri('http://php.net/preg_match'))->toString(),
        ]
      ),
    ];

    $form['advanced_stream'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Stream settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['advanced_stream']['stream_styles_ttl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The TTL of the redirect cache to the google storage styles'),
      '#default_value' => $config->get('stream_styles_ttl'),
      '#description' => $this->t('Styles will be redirected to Google Storage and Dynamic Page Cache module will cache the response for the specified TTL.'),
    ];
    $form['advanced_stream']['stream_public'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Google Storage for public:// files'),
      '#default_value' => Settings::get('gclient_storage.stream_public'),
      '#disabled' => TRUE,
      '#description' => $this->t(
        "Enable this option to store all files which would be uploaded to or created in the web server's local file system
      within your Google Storage bucket instead. To replace public:// stream wrapper with GClient Storage stream, include the following in settings.php:<br>
      <em>\$settings['gclient_storage.stream_public'] = TRUE;</em><br><br>
      <b>PLEASE NOTE:</b> If you intend to use Drupal's performance options which aggregate your CSS or Javascript
      files, or will be using any other system that writes CSS or Javascript files into your site's public:// file system,
      you must perform some additional configuration on your webserver to make those files work correctly when stored in Google Storage.
      Please see the section titled \"Aggregated CSS and JS in Google Storage\" in the README for details."
      ),
    ];
    $form['advanced_stream']['stream_public_folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public Folder'),
      '#default_value' => $config->get('public_folder'),
      '#description' => $this->t('The name of the folder in your bucket (or within the root folder) where public:// files will be stored.'),
      '#states' => [
        'visible' => [
          ':input[id=edit-stream-public]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['advanced_stream']['stream_rewrite_cssjs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Rewrite CSS/JS file paths"),
      '#default_value' => $config->get('stream_rewrite_cssjs') !== NULL ? $config->get('stream_rewrite_cssjs') : TRUE,
      '#description' => $this->t(
        'If this box is checked, GClient Storage will rewrite the CSS/JS file paths to "/gstorage-(css|js)/...". If NOT checked,
         they will be placed on the page with their regular CDN name. Only enable this option if you <b>know</b> you need it!'
      ),
      '#states' => [
        'visible' => [
          ':input[id=edit-stream-public]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['advanced_stream']['stream_private'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Google Storage for private:// files'),
      '#default_value' => Settings::get('gclient_storage.stream_private'),
      '#disabled' => TRUE,
      '#description' => $this->t(
        "Enable this option to store all files which would be uploaded to or created in the private://
         file system (files available only to authneticated users) within your Google Storage bucket instead.
         To replace private:// stream wrapper with Google Storage stream, include the following in settings.php:<br>
        <em>\$settings['gclient_storage.stream_private'] = TRUE;</em>"
      ),
    ];
    $form['advanced_stream']['stream_private_folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private Folder'),
      '#default_value' => $config->get('private_folder'),
      '#description' => $this->t('The name of the folder in your bucket (or within the root folder) where private:// files will be stored.'),
      '#states' => [
        'visible' => [
          ':input[id=edit-stream-private]' => ['checked' => TRUE],
        ],
      ],
    ];

//    $form['advanced_cname'] = [
//      '#type' => 'fieldset',
//      '#title' => $this->t('CNAME settings'),
//      '#collapsible' => TRUE,
//      '#collapsed' => TRUE,
//    ];
//    $form['advanced_cname']['cname'] = [
//      '#type' => 'checkbox',
//      '#title' => $this->t('Use CNAME'),
//      '#default_value' => $config->get('cname'),
//      '#description' => $this->t('Serve files from a custom domain by using an appropriately named bucket, e.g. "mybucket.mydomain.com".'),
//    ];
//    $form['advanced_cname']['cname_settings'] = [
//      '#type' => 'container',
//      '#title' => $this->t('CNAME Settings'),
//      '#states' => [
//        'visible' => [
//          ':input[id=edit-cname]' => ['checked' => TRUE],
//        ],
//      ],
//    ];
//    $form['advanced_cname']['cname_settings']['cname_domain'] = [
//      '#type' => 'textfield',
//      '#title' => $this->t('CDN Domain Name'),
//      '#default_value' => $config->get('cname_domain'),
//      '#description' => $this->t('Domain name what you will use as CNAME alias.'),
//    ];

    $form['advanced_metadata'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Metadata settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['advanced_metadata']['metadata_cache'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use local metadata cache'),
      '#default_value' => $config->get('metadata_cache') !== NULL ? $config->get('metadata_cache') : TRUE,
      '#description' => $this->t('<b>Disabling this option causes GClient Storage stream wrapper to work extremely slowly, and should never be disabled on a production site.</b>'),
    ];
    $form['advanced_metadata']['metadata_cache_control'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cache-Control directive'),
      '#default_value' => $config->get('metadata_cache_control'),
      '#description' => $this->t('The cache control directive to set on all objects for CDNs and browsers, e.g. "public, max-age=300".'),
    ];

    $form['advanced_encryption'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Server-side encryption'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate connector.
    if (($value = $form_state->getValue('integro_connector')) && $value === '') {
      $form_state->setErrorByName('integro_connector', $this->t("Please choose connector."));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('gclient_storage.settings')
      ->set('integro_connector', $values['integro_connector'])
      ->set('storage_root', $values['storage_root'])
      ->set('storage_download_paths', $values['storage_download_paths'])
      ->set('storage_signed_paths', $values['storage_signed_paths'])
//      ->set('cname', $values['cname'])
//      ->set('cname_domain', $values['cname_domain'])
      ->set('metadata_cache', $values['metadata_cache'])
      ->set('metadata_cache_control', $values['metadata_cache_control'])
      ->set('stream_styles_ttl', $values['stream_styles_ttl'])
      ->set('stream_public_folder', $values['stream_public_folder'])
      ->set('stream_private_folder', $values['stream_private_folder'])
      ->set('stream_rewrite_cssjs', $values['stream_rewrite_cssjs'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
