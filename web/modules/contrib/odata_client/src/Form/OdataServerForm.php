<?php

namespace Drupal\odata_client\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OdataServerForm.
 */
class OdataServerForm extends EntityForm {

  protected $odataPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContainerInterface $container) {
    $this->odataPluginManager = $container->get('plugin.manager.odata_auth_plugin');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form,
    FormStateInterface $form_state) {
    if (empty($odata_plugins_options)) {
      $odata_plugins_options = $this->odataPluginManager->optionList();
    }

    $form = parent::form($form, $form_state);

    $odata_server = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $odata_server->label(),
      '#description' => $this->t("Label for the Odata server."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $odata_server->id(),
      '#machine_name' => [
        'exists' => '\Drupal\odata_client\Entity\OdataServer::load',
      ],
      '#disabled' => !$odata_server->isNew(),
    ];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Url'),
      '#maxlength' => 255,
      '#default_value' => $odata_server->getUrl(),
      '#description' => $this->t("Url for the Odata server connection."),
      '#required' => TRUE,
    ];

    $form['authentication_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Authentication method'),
      '#options' => [
        'none' => $this->t('None'),
        'basic' => $this->t('Basic auth'),
        'oauth' => $this->t('Oauth'),
      ],
      '#description' => $this->t('Choose an authetication method'),
      '#default_value' => $odata_server->getAuthenticationMethod(),
      '#required' => FALSE,
    ];

    $form['oauth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Oauth authentication'),
      '#states' => [
        'visible' => [
          ':input[name="authentication_method"]' => ['value' => 'oauth'],
        ],
      ],
    ];

    $form['oauth']['token_provider'] = [
      '#type' => 'select',
      '#title' => $this->t('Select token provider'),
      '#description' => $this->t('Select an authentication token provider.'),
      '#options' => $odata_plugins_options,
      '#default_value' => $odata_server->getTokenProvider(),
    ];

    $form['oauth']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client id'),
      '#maxlength' => 255,
      '#default_value' => $odata_server->getClientId(),
      '#description' => $this->t("Client id of web application"),
      '#required' => FALSE,
    ];

    $form['oauth']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#maxlength' => 255,
      '#default_value' => $odata_server->getClientSecret(),
      '#description' => $this->t("Client secret of web application"),
      '#required' => FALSE,
    ];

    $form['oauth']['redirect_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect uri'),
      '#maxlength' => 255,
      '#default_value' => $odata_server->getRedirectUri(),
      '#description' => $this->t("Web application redirect uri."),
      '#required' => FALSE,
    ];

    $form['oauth']['tenant'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tenant'),
      '#maxlength' => 255,
      '#default_value' => $odata_server->getTenant(),
      '#description' => $this->t("The azure client id (tenant)."),
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="token_provider"]' => ['value' => 'azure'],
        ],
      ],
    ];

    $form['oauth']['url_authorize'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Authorize url'),
      '#maxlength' => 255,
      '#default_value' => $odata_server->getUrlAuthorize(),
      '#description' => $this->t("The authorize url of oauth server."),
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="token_provider"]' => ['value' => 'generic'],
        ],
      ],
    ];

    $form['oauth']['url_access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access token url'),
      '#maxlength' => 255,
      '#default_value' => $odata_server->getUrlToken(),
      '#description' => $this->t("The access token url of oauth server."),
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="token_provider"]' => ['value' => 'generic'],
        ],
      ],
    ];

    $form['oauth']['url_resource_owner_details'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Url resource'),
      '#maxlength' => 255,
      '#default_value' => $odata_server->getUrlResource(),
      '#description' => $this->t("The url of resource web app.."),
      '#required' => FALSE,
    ];

    $form['basic'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic authentication'),
      '#states' => [
        'visible' => [
          ':input[name="authentication_method"]' => ['value' => 'basic'],
        ],
      ],
    ];

    $form['basic']['user_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User name'),
      '#maxlength' => 255,
      '#default_value' => $odata_server->getUserName(),
      '#description' => $this->t("User name for the Odata server connection. (optional)"),
      '#required' => FALSE,
    ];

    $form['basic']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#maxlength' => 255,
      '#default_value' => $odata_server->getPassword(),
      '#description' => $this->t("Password for the Odata server connection. (optional)"),
      '#required' => FALSE,
    ];

    $form['odata_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Odata type'),
      '#maxlength' => 255,
      '#default_value' => $odata_server->getOdataType(),
      '#description' => $this->t("Odata type for the Odata server connection."),
      '#required' => FALSE,
    ];

    $form['default_collection'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default collection'),
      '#maxlength' => 255,
      '#default_value' => $odata_server->getDefaultCollection(),
      '#description' => $this->t("Odata default collection of the Odata server connection."),
      '#required' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form,
    FormStateInterface $form_state) {
    $odata_server = $this->entity;
    $status = $odata_server->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Odata server.', [
          '%label' => $odata_server->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Odata server.', [
          '%label' => $odata_server->label(),
        ]));
    }
    $form_state->setRedirectUrl($odata_server->toUrl('collection'));
  }

}
