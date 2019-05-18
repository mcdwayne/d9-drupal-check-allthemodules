<?php

namespace Drupal\civimail\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\civimail\CiviMailInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity send form.
 */
class EntitySendForm extends FormBase {

  /**
   * Drupal\civimail\CiviMailInterface definition.
   *
   * @var \Drupal\civimail\CiviMailInterface
   */
  protected $civiMail;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an EntitySendForm object.
   *
   * @param \Drupal\civimail\CiviMailInterface $civi_mail
   *   The CiviMail service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(CiviMailInterface $civi_mail, ConfigFactoryInterface $config_factory) {
    $this->civiMail = $civi_mail;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('civimail'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civimail_entity_send_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $bundle = NULL) {
    if ($this->civiMail->hasCiviCrmRequirements()) {
      // @todo generalize to other entity types
      $bundleSettings = civimail_get_entity_bundle_settings('all', 'node', $bundle);
      /** @var \Drupal\civicrm_tools\CiviCrmContact $civiCrmContact */
      $civiCrmContact = \Drupal::service('civicrm_tools.contact');
      $fromContacts = $civiCrmContact->getFromGroups($bundleSettings['from_groups']);

      // Try to set the sender default contact from the current logged in user.
      $fromDefaultContact = NULL;
      $currentContact = $civiCrmContact->getFromLoggedInUser(CIVICRM_DOMAIN_ID);
      if (array_key_exists($currentContact['contact_id'], $fromContacts)) {
        $fromDefaultContact = $currentContact;
      }
      $form['from_contact'] = [
        '#type' => 'select',
        '#title' => $this->t('From'),
        '#description' => $this->t('The sender CiviCRM contact.'),
        '#options' => $civiCrmContact->labelFormat($fromContacts),
        '#default_value' => $fromDefaultContact['contact_id'],
        '#required' => TRUE,
      ];
      $form['test_mode'] = [
        '#type' => 'checkbox',
        '#title' => t('Send a test'),
        // @todo remove once https://www.drupal.org/project/mimemail/issues/2863079
        // is fixed, because test mails are relying on mimemail.
        '#access' => FALSE,
      ];
      $form['test_mail'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Test email'),
        '#description' => $this->t('The email address that will receive the test.'),
        '#maxlength' => 254,
        '#size' => 64,
        '#default_value' => \Drupal::config('system.site')->get('mail'),
        '#states' => [
          'visible' => [
            ':input[name="test_mode"]' => ['checked' => TRUE],
          ],
          // @todo set back once https://www.drupal.org/project/mimemail/issues/2863079
          // is fixed, because test mails are relying on mimemail.
          // 'required' => [
          // ':input[name="test_mode"]' => ['checked' => TRUE],
          // ],
        ],
        // @todo remove once https://www.drupal.org/project/mimemail/issues/2863079
        // is fixed, because test mails are relying on mimemail.
        '#access' => FALSE,
      ];
      $form['to_groups'] = [
        '#type' => 'select',
        '#title' => t('Groups'),
        '#description' => $this->t('The CiviCRM groups that will receive the mailing.'),
        // @todo filter by configured groups for this bundle.
        // @todo use civicrm_tools.group service with labelFormat method
        '#options' => $this->civiMail->getGroupSelectOptions(),
        '#multiple' => TRUE,
        '#limit_validation_errors' => ['submit'],
        '#states' => [
          'visible' => [
            ':input[name="test_mode"]' => ['checked' => FALSE],
          ],
          'required' => [
            ':input[name="test_mode"]' => ['checked' => FALSE],
          ],
        ],
      ];

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Send'),
        '#button_type' => 'primary',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fromCid = $form_state->getValue('from_contact');
    $testMode = $form_state->getValue('test_mode');
    $testMail = $form_state->getValue('test_mail');
    $entity = $this->civiMail->getEntityFromRoute('node');
    if ($testMode) {
      $this->civiMail->sendTestMail($fromCid, $entity, $testMail);
    }
    else {
      $groups = $form_state->getValue('to_groups');
      $params = $this->civiMail->getEntityMailingParams($fromCid, $entity, $groups);
      $this->civiMail->sendMailing($params, $entity);
    }
  }

}
