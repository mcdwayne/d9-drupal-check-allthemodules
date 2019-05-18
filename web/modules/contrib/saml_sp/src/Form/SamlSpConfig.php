<?php

namespace Drupal\saml_sp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use OneLogin\Saml2\Utils;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Provides the configuration form.
 */
class SamlSpConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saml_sp_config_sp';
  }

  /**
   * Check if config variable is overridden by the settings.php.
   *
   * @param string $name
   *   SAML SP settings key.
   *
   * @return bool
   *   Boolean.
   */
  protected function isOverridden($name) {
    $original = $this->configFactory->getEditable('saml_sp.settings')->get($name);
    $current = $this->configFactory->get('saml_sp.settings')->get($name);
    return $original != $current;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('saml_sp.settings');
    $values = $form_state->getValues();
    $this->configRecurse($config, $values['contact'], 'contact');
    $this->configRecurse($config, $values['organization'], 'organization');
    $this->configRecurse($config, $values['security'], 'security');
    $config->set('strict', (boolean) $values['strict']);
    $config->set('debug', (boolean) $values['debug']);
    $config->set('key_location', $values['key_location']);
    $config->set('cert_location', $values['cert_location']);
    $config->set('new_cert_location', $values['new_cert_location']);
    $config->set('entity_id', $values['entity_id']);

    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Ensure the cert and key files are provided and exist in the system if
    // signed or encryption options require them.
    $values = $form_state->getValues();
    if (
      $values['security']['authnRequestsSigned'] ||
      $values['security']['logoutRequestSigned'] ||
      $values['security']['logoutResponseSigned'] ||
      $values['security']['wantNameIdEncrypted'] ||
      $values['security']['signMetaData']
    ) {
      foreach (['key_location', 'cert_location'] as $key) {
        if (empty($values[$key])) {
          $form_state->setError($form[$key], $this->t('The %field must be provided.', ['%field' => $form[$key]['#title']]));
        }
        elseif (!file_exists($values[$key])) {
          $form_state->setError($form[$key], $this->t('The %input file does not exist.', ['%input' => $values[$key]]));
        }
      }
    }
  }

  /**
   * Recursively go through the set values to set the configuration.
   */
  protected function configRecurse($config, $values, $base = '') {
    foreach ($values as $var => $value) {
      if (!empty($base)) {
        $v = $base . '.' . $var;
      }
      else {
        $v = $var;
      }
      if (!is_array($value)) {
        $config->set($v, $value);
      }
      else {
        $this->configRecurse($config, $value, $v);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['saml_sp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('saml_sp.settings');

    $form['entity_id'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Entity ID'),
      '#description'    => $this->t(
        'This is the unique name that the Identity Providers will know your site as. Defaults to the login page %login_url',
        [
          '%login_url' => \Drupal::url('user.page', [], ['absolute' => TRUE]),
        ]),
      '#default_value'  => $config->get('entity_id'),
    ];

    $form['contact'] = [
      '#type'           => 'fieldset',
      '#title'          => $this->t('Contact Information'),
      '#description'    => $this->t('Information to be included in the federation metadata.'),
      '#tree'           => TRUE,
    ];
    $form['contact']['technical'] = [
      '#type'           => 'fieldset',
      '#title'          => $this->t('Technical'),
    ];
    $form['contact']['technical']['name'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Name'),
      '#default_value'  => $config->get('contact.technical.name'),
      '#disabled'       => $this->isOverridden('contact.technical.name'),
    ];
    $form['contact']['technical']['email'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Email'),
      '#default_value'  => $config->get('contact.technical.email'),
      '#disabled'       => $this->isOverridden('contact.technical.email'),
    ];
    $form['contact']['support'] = [
      '#type'           => 'fieldset',
      '#title'          => $this->t('Support'),
    ];
    $form['contact']['support']['name'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Name'),
      '#default_value'  => $config->get('contact.support.name'),
      '#disabled'       => $this->isOverridden('contact.support.name'),
    ];
    $form['contact']['support']['email'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Email'),
      '#default_value'  => $config->get('contact.support.email'),
      '#disabled'       => $this->isOverridden('contact.support.email'),
    ];

    $form['organization'] = [
      '#type'           => 'fieldset',
      '#title'          => $this->t('Organization'),
      '#description'    => $this->t('Organization information for the federation metadata'),
      '#tree'           => TRUE,
    ];
    $form['organization']['name'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Name'),
      '#description'    => $this->t('This is a short name for the organization'),
      '#default_value'  => $config->get('organization.name'),
      '#disabled'       => $this->isOverridden('organization.name'),
    ];
    $form['organization']['display_name'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('Display Name'),
      '#description'    => $this->t('This is a long name for the organization'),
      '#default_value'  => $config->get('organization.display_name'),
      '#disabled'       => $this->isOverridden('organization.display_name'),
    ];
    $form['organization']['url'] = [
      '#type'           => 'textfield',
      '#title'          => $this->t('URL'),
      '#description'    => $this->t('This is a URL for the organization'),
      '#default_value'  => $config->get('organization.url'),
      '#disabled'        => $this->isOverridden('organization.ur'),
    ];

    $form['strict'] = [
      '#type'           => 'checkbox',
      '#title'          => t('Strict Protocol'),
      '#description'    => t('SAML 2 Strict protocol will be used.'),
      '#default_value'  => $config->get('strict'),
      '#disabled'       => $this->isOverridden('strict'),
    ];

    $form['security'] = [
      '#type'           => 'fieldset',
      '#title'          => $this->t('Security'),
      '#tree'           => TRUE,
    ];
    $form['security']['offered'] = [
      '#markup'         => t('Signatures and Encryptions Offered:'),
    ];
    $form['security']['nameIdEncrypted'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('NameID Encrypted'),
      '#default_value'  => $config->get('security.nameIdEncrypted'),
      '#disabled'       => $this->isOverridden('security.nameIdEncrypted'),
    ];
    $form['security']['authnRequestsSigned'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Authn Requests Signed'),
      '#default_value'  => $config->get('security.authnRequestsSigned'),
      '#disabled'       => $this->isOverridden('security.authnRequestsSigned'),
    ];
    $form['security']['logoutRequestSigned'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Logout Requests Signed'),
      '#default_value'  => $config->get('security.logoutRequestSigned'),
      '#disabled'       => $this->isOverridden('security.logoutRequestSigned'),
    ];
    $form['security']['logoutResponseSigned'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Logout Response Signed'),
      '#default_value'  => $config->get('security.logoutResponseSigned'),
      '#disabled'       => $this->isOverridden('security.logoutResponseSigned'),
    ];

    $form['security']['required'] = [
      '#markup'         => $this->t('Signatures and Encryptions Required:'),
    ];
    $form['security']['wantMessagesSigned'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Want Messages Signed'),
      '#default_value'  => $config->get('security.wantMessagesSigned'),
      '#disabled'       => $this->isOverridden('security.wantMessagesSigned'),
    ];
    $form['security']['wantAssertionsSigned'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Want Assertions Signed'),
      '#default_value'  => $config->get('security.wantAssertionsSigned'),
      '#disabled'       => $this->isOverridden('security.wantAssertionsSigned'),
    ];
    $form['security']['wantNameIdEncrypted'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Want NameID Encrypted'),
      '#default_value'  => $config->get('security.wantNameIdEncrypted'),
      '#disabled'       => $this->isOverridden('security.wantNameIdEncrypted'),
    ];
    $form['security']['metadata'] = [
      '#markup'         => $this->t('Metadata:'),
    ];

    $form['security']['signMetaData'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Sign Meta Data'),
      '#default_value'  => $config->get('security.signMetaData'),
      '#disabled'       => $this->isOverridden('security.signMetaData'),
    ];
    $form['security']['signatureAlgorithm'] = [
      '#type'           => 'select',
      '#title'          => $this->t('Signature Algorithm'),
      '#description'    => $this->t('What algorithm do you want used for messages signatures?'),
      '#options'        => [
        /*
        XMLSecurityKey::DSA_SHA1 => 'DSA SHA-1',
        XMLSecurityKey::HMAC_SHA1 => 'HMAC SHA-1',
        /**/
        XMLSecurityKey::RSA_SHA1 => 'SHA-1',
        XMLSecurityKey::RSA_SHA256 => 'SHA-256',
        XMLSecurityKey::RSA_SHA384 => 'SHA-384',
        XMLSecurityKey::RSA_SHA512 => 'SHA-512',
      ],
      '#default_value'   => $config->get('security.signatureAlgorithm'),
      '#disabled'        => $this->isOverridden('security.signatureAlgorithm'),
    ];
    $form['security']['lowercaseUrlencoding'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Lowercase Url Encoding'),
      /*
      '#description'    => $this->t(""),
      /**/
      '#default_value'  => $config->get('security.lowercaseUrlencoding'),
      '#disabled'       => $this->isOverridden('security.lowercaseUrlencoding'),
    ];

    $form['cert_location'] = [
      '#type'   => 'textfield',
      '#title'  => $this->t('Certificate Location'),
      '#description'  => $this->t('The location of the X.509 certificate file on the server. This must be a location that PHP can read.'),
      '#default_value' => $config->get('cert_location'),
      '#disabled'      => $this->isOverridden('cert_location'),
      '#states' => [
        'required' => [
          ['input[name="security[authnRequestsSigned]"' => ['checked' => TRUE]],
          ['input[name="security[logoutRequestSigned]"' => ['checked' => TRUE]],
          ['input[name="security[logoutResponseSigned]"' => ['checked' => TRUE]],
          ['input[name="security[wantNameIdEncrypted]"' => ['checked' => TRUE]],
          ['input[name="security[signMetaData]"' => ['checked' => TRUE]],
        ],
      ],
      '#suffix' => $this->certInfo($config->get('cert_location')),
    ];

    $form['key_location'] = [
      '#type'   => 'textfield',
      '#title'  => $this->t('Key Location'),
      '#description'  => $this->t('The location of the X.509 key file on the server. This must be a location that PHP can read.'),
      '#default_value' => $config->get('key_location'),
      '#disabled'      => $this->isOverridden('key_location'),
      '#states' => [
        'required' => [
          ['input[name="security[authnRequestsSigned]"' => ['checked' => TRUE]],
          ['input[name="security[logoutRequestSigned]"' => ['checked' => TRUE]],
          ['input[name="security[logoutResponseSigned]"' => ['checked' => TRUE]],
          ['input[name="security[wantNameIdEncrypted]"' => ['checked' => TRUE]],
          ['input[name="security[signMetaData]"' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['new_cert_location'] = [
      '#type'   => 'textfield',
      '#title'  => $this->t('New Certificate Location'),
      '#description'  => $this->t('The location of the x.509 certificate file on the server. If the certificate above is about to expire add your new certificate here after you have obtained it. This will add the new certificate to the metadata to let the IdP know of the new certificate. This must be a location that PHP can read.'),
      '#default_value' => $config->get('new_cert_location'),
      '#disabled'      => $this->isOverridden('new_cert_location'),
      '#states' => [
        'required' => [
          ['input[name="security[authnRequestsSigned]"' => ['checked' => TRUE]],
          ['input[name="security[logoutRequestSigned]"' => ['checked' => TRUE]],
          ['input[name="security[logoutResponseSigned]"' => ['checked' => TRUE]],
          ['input[name="security[wantNameIdEncrypted]"' => ['checked' => TRUE]],
          ['input[name="security[signMetaData]"' => ['checked' => TRUE]],
        ],
      ],
      '#suffix' => $this->certInfo($config->get('new_cert_location')),
    ];

    $error = FALSE;
    try {
      $metadata = saml_sp__get_metadata(FALSE);
      if (is_array($metadata)) {
        if (isset($metadata[1])) {
          $errors = $metadata[1];
        }
        $metadata = $metadata[0];
      }
    }
    catch (Exception $e) {
      \Drupal::messenger()->addMessage($this->t('Attempt to create metadata failed: %message.', [
        '%message' => $e->getMessage(),
      ]), MessengerInterface::TYPE_ERROR);
      $metadata = '';
      $error = $e;
    }
    if (empty($metadata) && $error) {
      $no_metadata = $this->t('There is currently no metadata because of the following error: %error. Please resolve the error and return here for your metadata.', ['%error' => $error->getMessage()]);
    }
    $form['metadata'] = [
      '#type'         => 'fieldset',
      '#collapsed'    => TRUE,
      '#collapsible'  => TRUE,
      '#title'        => $this->t('Metadata'),
      '#description'  => $this->t('This is the Federation Metadata for this SP, please provide this to the IdP to create a Relying Party Trust (RPT)'),
    ];

    if ($metadata) {
      $form['metadata']['data'] = [
        '#type'           => 'textarea',
        '#title'          => $this->t('XML Metadata'),
        '#description'    => $this->t(
          'This metadata can also be accessed <a href="@url" target="_blank">here</a>',
          [
            '@url' => Url::fromRoute('saml_sp.metadata')->toString(),
          ]),
        '#disabled'       => TRUE,
        '#rows'           => 20,
        '#default_value'  => trim($metadata),
      ];
    }
    else {
      $form['metadata']['none'] = [
        '#markup'         => $no_metadata,
      ];
    }
    $form['debug'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Turn on debugging'),
      '#description'    => $this->t('Some debugging messages will be shown.'),
      '#default_value'  => $config->get('debug'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Retrieves pertinent certificate data and output in a string for display.
   *
   * @param string $cert_location
   *   The location of the certificate file.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|false
   *   Certificate information, or false if the it can't be read or parsed.
   */
  private function certInfo($cert_location) {
    if (!empty($cert_location) && file_exists($cert_location) && function_exists('openssl_x509_parse')) {
      $encoded_cert = trim(file_get_contents($cert_location));
      $cert = openssl_x509_parse(Utils::formatCert($encoded_cert));
      // Flatten the issuer array.
      if (!empty($cert['issuer'])) {
        foreach ($cert['issuer'] as $key => &$value) {
          if (is_array($value)) {
            $value = implode("/", $value);
          }
        }
      }

      if ($cert) {
        $info = t('Name: %cert-name<br/>Issued by: %issuer<br/>Valid: %valid-from - %valid-to', [
          '%cert-name' => isset($cert['name']) ? $cert['name'] : '',
          '%issuer' => isset($cert['issuer']) && is_array($cert['issuer']) ? implode('/', $cert['issuer']) : '',
          '%valid-from' => isset($cert['validFrom_time_t']) ? date('c', $cert['validFrom_time_t']) : '',
          '%valid-to' => isset($cert['validTo_time_t']) ? date('c', $cert['validTo_time_t']) : '',
        ]);
        return $info;
      }
    }
    return FALSE;
  }

}
