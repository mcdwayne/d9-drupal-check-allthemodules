<?php

namespace Drupal\saml_sp\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use OneLogin\Saml2\Constants;
use OneLogin\Saml2\Utils;

/**
 * Provides the form to configure the IdP.
 */
class IdpForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $idp = $this->entity;

    $form['idp_metadata'] = [
      '#type' => 'textarea',
      '#title'  => t('XML Metadata'),
      '#description' => t('Paste in the metadata provided by the Identity Provider here and the form will be automatically filled out, or you can manually enter the information.'),
    ];
    $form['#attached']['library'][] = 'saml_sp/idp_form';

    $form['idp'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
    ];

    $form['idp']['label'] = [
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#default_value' => $idp->label(),
      '#description' => t('The human-readable name of this IdP. This text will be displayed to administrators who can configure SAML.'),
      '#required' => TRUE,
      '#size' => 30,
      '#maxlength' => 30,
    ];

    $form['idp']['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $idp->id(),
      '#maxlength' => 32,
      '#machine_name' => [
        'exists' => 'saml_sp_idp_load',
        'source' => ['idp', 'label'],
      ],
      '#description' => t('A unique machine-readable name for this IdP. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['idp']['entity_id'] = [
      '#type' => 'textfield',
      '#title' => t('Entity ID'),
      '#description' => t('The entityID identifier which the Identity Provider will use to identiy itself by, this may sometimes be a URL.'),
      '#default_value' => $idp->getEntityId(),
      '#maxlength' => 255,
    ];

    $form['idp']['app_name'] = [
      '#type' => 'textfield',
      '#title' => t('App name'),
      '#description' => t('The app name is provided to the Identiy Provider, to identify the origin of the request.'),
      '#default_value' => $idp->getAppName(),
      '#maxlength' => 255,
    ];

    $fields = ['mail' => t('Email')];
    // TODO: Add extra fields to config.
    /*
    // @codingStandardsIgnoreStart
    if (!empty($extra_fields)) {
      foreach ($extra_fields as $value) {
        $fields[$value] = $value;
      }
    }
    // @codingStandardsIgnoreEnd
    /**/

    $form['idp']['nameid_field'] = [
      '#type' => 'select',
      '#title' => t('NameID field'),
      '#description' => t('Mail is usually used between IdP and SP, but if you want to let users change the email address in IdP, you need to use a custom field to store the ID.'),
      '#options' => $fields,
      '#default_value' => $idp->getNameIdField(),
    ];

    // The SAML login URL and X.509 certificate must match the details provided
    // by the IdP.
    $form['idp']['login_url'] = [
      '#type' => 'textfield',
      '#title' => t('IdP login URL'),
      '#description' => t('Login URL of the Identity Provider server.'),
      '#default_value' => $idp->getLoginUrl(),
      '#required' => TRUE,
      '#max_length' => 255,
    ];

    $form['idp']['logout_url'] = [
      '#type' => 'textfield',
      '#title' => t('IdP logout URL'),
      '#description' => t('Logout URL of the Identity Provider server.'),
      '#default_value' => $idp->getLogoutUrl(),
      '#required' => TRUE,
      '#max_length' => 255,
    ];

    $form['idp']['x509_cert'] = $this->createCertsFieldset($form_state);

    $form_state->setCached(FALSE);

    $refs = saml_sp_authn_context_class_refs();
    $authn_context_class_ref_options = [
      $refs[Constants::AC_PASSWORD]           => t('User Name and Password'),
      $refs[Constants::AC_PASSWORD_PROTECTED] => t('Password Protected Transport'),
      $refs[Constants::AC_TLS]                => t('Transport Layer Security (TLS) Client'),
      $refs[Constants::AC_X509]               => t('X.509 Certificate'),
      $refs[Constants::AC_WINDOWS]            => t('Integrated Windows Authentication'),
      $refs[Constants::AC_KERBEROS]           => t('Kerberos'),
    ];
    $default_auth = [];
    foreach ($refs as $key => $value) {
      $default_auth[$value] = $value;
    }

    $form['idp']['authn_context_class_ref'] = [
      '#type'           => 'checkboxes',
      '#title'          => t('Authentication methods'),
      '#description'    => t('What authentication methods would you like to use with this IdP? If left empty all methods from the provider will be allowed.'),
      '#default_value'  => $idp->id() ? $idp->getAuthnContextClassRef() : $default_auth,
      '#options'        => $authn_context_class_ref_options,
      '#required' => FALSE,
    ];

    return $form;
  }

  /**
   * Creates a fieldset for managing certificates.
   */
  public function createCertsFieldset(FormStateInterface $form_state) {
    $idp = $this->entity;
    $certs = $idp->getX509Cert();
    if (!is_array($certs)) {
      $certs = [$certs];
    }

    foreach ($certs as $key => $value) {
      if ((is_string($value) && empty(trim($value))) || $value == 'Array') {
        unset($certs[$key]);
      }
    }
    $values = $form_state->getValues();

    if (!empty($values['idp']['x509_cert'])) {
      $certs = $values['idp']['x509_cert'];
      unset($certs['actions']);
    }
    $form = [
      '#type' => 'fieldset',
      '#title' => $this->t('X.509 certificates'),
      '#description' => t('Enter the application certificate(s) provided by the IdP.'),
      '#prefix' => '<div id="certs-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    // Gather the number of certs in the form already.
    $num_certs = $form_state->get('num_certs');
    // We have to ensure that there is at least one cert field.
    if ($num_certs === NULL) {
      $num_certs = count($certs) ?: 1;
      $cert_field = $form_state->set('num_certs', $num_certs);
    }
    for ($i = 0; $i < $num_certs; $i++) {
      if (isset($certs[$i])) {
        $encoded_cert = trim($certs[$i]);
      }
      else {
        $encoded_cert = '';
      }
      if (empty($encoded_cert)) {
        $form[$i] = [
          '#type' => 'textarea',
          '#title' => $this->t('New Certificate'),
          '#default_value' => $encoded_cert,
          '#max_length' => 1024,
        ];
        continue;
      }
      $title = t('Certificate');
      if (function_exists('openssl_x509_parse')) {
        $cert = openssl_x509_parse(Utils::formatCert($encoded_cert));
        if ($cert) {
          // Flatten the issuer array.
          foreach ($cert['issuer'] as $key => &$value) {
            if (is_array($value)) {
              $value = implode("/", $value);
            }
          }
          $title = t('Name: %cert-name<br/>Issued by: %issuer<br/>Valid: %valid-from - %valid-to', [
            '%cert-name'  => $cert['name'],
            '%issuer'     => implode('/', $cert['issuer']),
            '%valid-from' => date('c', $cert['validFrom_time_t']),
            '%valid-to'  => date('c', $cert['validTo_time_t']),
          ]);
        }
      }

      $form[$i] = [
        '#type' => 'textarea',
        '#title' => $title,
        '#default_value' => $encoded_cert,
        '#max_length' => 1024,
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['add_cert'] = [
      '#type' => 'submit',
      '#value' => t('Add one more'),
      '#submit' => ['::addCertCallback'],
      '#ajax' => [
        'callback' => '::addMoreCertsCallback',
        'wrapper' => 'certs-fieldset-wrapper',
      ],
    ];
    // If there is more than one name, add the remove button.
    if ($num_certs > 1) {
      $form['actions']['remove_cert'] = [
        '#type' => 'submit',
        '#value' => t('Remove one'),
        '#submit' => ['::removeCertCallback'],
        '#ajax' => [
          'callback' => '::addMoreCertsCallback',
          'wrapper' => 'certs-fieldset-wrapper',
        ],
      ];
    }
    return $form;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the certs in it.
   */
  public function addMoreCertsCallback(array &$form, FormStateInterface $form_state) {
    $cert_field = $form_state->get('num_certs');
    return $form['idp']['x509_cert'];
  }

  /**
   * Submit handler for the "add cert" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addCertCallback(array &$form, FormStateInterface $form_state) {
    $cert_field = $form_state->get('num_certs');
    $add_button = $cert_field + 1;
    $form_state->set('num_certs', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove cert" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCertCallback(array &$form, FormStateInterface $form_state) {
    $cert_field = $form_state->get('num_certs');
    if ($cert_field > 1) {
      $remove_button = $cert_field - 1;
      $form_state->set('num_certs', $remove_button);
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $idp = $this->entity;
    $values = $form_state->getValues();

    if (!is_array($values['idp']['x509_cert'])) {
      $values['idp']['x509_cert'] = [$values['idp']['x509_cert']];
    }
    foreach ($values['idp'] as $key => $value) {
      $idp->set($key, $value);
    }

    $status = $idp->save();

    if ($status) {
      \Drupal::messenger()->addMessage($this->t('Saved the %label Identity Provider.', [
        '%label' => $idp->label(),
      ]));
    }
    else {
      \Drupal::messenger()->addMessage($this->t('The %label Identity Provider was not saved.', [
        '%label' => $idp->label(),
      ]));
    }

    $form_state->setRedirect('entity.idp.collection');
  }

  /**
   * Tests whether the IdP exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('idp')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
