<?php

namespace Drupal\simply_signups\Form;

use Drupal\Core\Path;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use CommerceGuys\Addressing\AddressFormat\AddressField;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Implements a signup form.
 */
class SimplySignupsNodesSettingsForm extends FormBase {

  protected $time;
  protected $database;
  protected $currentPath;
  protected $entityTypeManager;

  /**
   * Implements __construct().
   */
  public function __construct(TimeInterface $time_interface, CurrentPathStack $current_path, Connection $database_connection, EntityTypeManagerInterface $entity_type_manager) {
    $this->time = $time_interface;
    $this->currentPath = $current_path;
    $this->database = $database_connection;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Implements create().
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('datetime.time'),
      $container->get('path.current'),
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simply_signups_nodes_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $path = $this->currentPath->getPath();
    $currentPath = ltrim($path, '/');
    $arg = explode('/', $currentPath);
    $nid = $arg[1];
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($nid);
    $isValidNode = (isset($node)) ? TRUE : FALSE;
    if (!$isValidNode) {
      throw new NotFoundHttpException();
    }
    $nid = $node->id();
    $title = $node->getTitle();
    $db = $this->database;
    $query = $db->select('simply_signups_settings', 'p');
    $query->fields('p');
    $query->condition('nid', $nid, '=');
    $count = $query->countQuery()->execute()->fetchField();
    if ($count > 0) {
      $results = $query->execute()->fetchAll();
      foreach ($results as $row) {
        $nid = $row->nid;
        $startDate = DrupalDateTime::createFromTimestamp($row->start_date);
        $endDate = DrupalDateTime::createFromTimestamp($row->end_date);
        $altPath = $row->path;
        $maxSignups = $row->max_signups;
        $status = $row->status;
        $adminSendMail = $row->admin_send_mail;
        $adminMail = $row->admin_mail;
        $adminSubject = $row->admin_subject;
        $adminMessage = $row->admin_message;
        $adminFormat = $row->admin_format;
        $clientSendMail = $row->client_send_mail;
        $clientSubject = $row->client_subject;
        $clientMessage = $row->client_message;
        $clientFormat = $row->client_format;
      }
    }
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-nodes-settings-form', 'simply-signups-form'],
    ];
    $form['signup_node_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('<em>@title:</em> node settings', ['@title' => $title]),
    ];
    $form['signup_node_fieldset']['nid'] = [
      '#type' => 'hidden',
      '#value' => ($node->id()) ? $node->id() : $nid,
    ];
    $form['signup_node_fieldset']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled signups for this node'),
      '#default_value' => (isset($status)) ? $status : 0,
    ];
    $form['signup_node_fieldset']['start_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Signups open'),
      '#required' => TRUE,
      '#default_value' => (isset($startDate)) ? $startDate : '',
    ];
    $form['signup_node_fieldset']['end_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Signups close'),
      '#required' => TRUE,
      '#default_value' => (isset($endDate)) ? $endDate : '',
    ];
    $form['signup_node_fieldset']['path'] = [
      '#type' => 'url',
      '#title' => $this->t('Alternate confirmation URL'),
      '#maxlength' => 254,
      '#default_value' => (!empty($altPath)) ? $altPath : '',
      '#description' => $this->t('Enter an alternate confirmation url (absolute), if you want to redirect the client, when a new signup is submitted'),
    ];
    $form['signup_node_fieldset']['max_signups'] = [
      '#type' => 'number',
      '#title' => $this->t('Max attending'),
      '#precision' => 10,
      '#decimals' => 0,
      '#minimum' => 0,
      '#default_value' => (isset($maxSignups)) ? $maxSignups : 0,
      '#required' => TRUE,
      '#description' => $this->t('Enter the number of allowed attendees, this will include the number of people that the attendee is bringing with them (0 is unlimited).'),
    ];
    $form['admin_mail_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Admin mail settings'),
    ];
    $form['admin_mail_fieldset']['admin_send_mail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select if you wish to send an email to the address designated below, when a new signup is submitted'),
      '#default_value' => (isset($adminSendMail)) ? $adminSendMail : 0,
    ];
    $form['admin_mail_fieldset']['admin_mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Signup admin email'),
      '#maxlength' => 254,
      '#default_value' => (isset($adminMail)) ? $adminMail : '',
      '#description' => $this->t('Enter a valid email for who will recieve emails when a signup form is submitted.'),
      '#states' => [
        'required' => [
          ':input[name="admin_send_mail"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['admin_mail_fieldset']['admin_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Signup admin subject'),
      '#maxlength' => 254,
      '#default_value' => (isset($adminSubject)) ? $adminSubject : 'RSVP for: [node:title]',
      '#states' => [
        'required' => [
          ':input[name="admin_send_mail"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['admin_mail_fieldset']['admin_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Signup admin message'),
      '#format' => (isset($adminFormat)) ? $adminFormat : NULL,
      '#default_value' => (isset($adminMessage)) ? $adminMessage : '<p>A RSVP has been submitted for an event below.</p><h3>[node:title]</h3><p>[node:url]<br /></p>',
      '#description' => $this->t('Please make sure that all urls are absolute.'),
      '#states' => [
        'required' => [
          ':input[name="admin_send_mail"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['admin_mail_fieldset']['token_help_admin'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['node'],
    ];
    $form['client_mail_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Client mail settings'),
    ];
    $form['client_mail_fieldset']['client_send_mail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select if you wish to send an email to the client, when a new signup is submitted'),
      '#default_value' => (isset($clientSendMail)) ? $clientSendMail : 0,
    ];
    $form['client_mail_fieldset']['client_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Signup client subject'),
      '#maxlength' => 254,
      '#default_value' => (isset($clientSubject)) ? $clientSubject : 'RSVP Confirmation: [node:title]',
      '#states' => [
        'required' => [
          ':input[name="client_send_mail"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['client_mail_fieldset']['client_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Signup client message'),
      '#format' => (isset($clientFormat)) ? $clientFormat : NULL,
      '#default_value' => (isset($clientMessage)) ? $clientMessage : "<h3>Thank you for your RSVP!</h3><p>We're happy to see that you're interested in coming to our event, [node:title]!</p><p>You can find event details at [node:url]</p><p>If you are no longer able to attend the event, that's fine! We kindly ask that you let us know by contacting us at: [site:mail].</p><p>Thank you! We look forward to seeing you soon.</p><p>Sincerely,<br />[site:name]</p>",
      '#description' => $this->t('Please make sure that all urls are absolute.'),
      '#states' => [
        'required' => [
          ':input[name="client_send_mail"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $form['client_mail_fieldset']['token_help_client'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['node'],
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Configure signup'),
      '#attributes' => [
        'class' => [
          'button--primary',
          'btn-primary',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!empty($values['path'])) {
      $isValidAbsolute = UrlHelper::isValid($values['path']);
      if (($isValidAbsolute == FALSE)) {
        $form_state->setErrorByName('path', $this->t('You MUST enter a VALID alternate confirmation url'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $requestTime = $this->time->getCurrentTime();
    $db = $this->database;
    $query = $db->select('simply_signups_settings', 'p');
    $query->fields('p');
    $query->condition('nid', $values['nid'], '=');
    $count = $query->countQuery()->execute()->fetchField();
    $startDatetime = $values['start_date'];
    $endDatetime = $values['end_date'];
    $startTimestamp = strtotime($startDatetime->format('Y-m-d h:i:s A'));
    $endTimestamp = strtotime($endDatetime->format('Y-m-d h:i:s A'));
    $altPath = (!empty($values['path'])) ? ltrim($values['path'], '/') : '';
    $adminMail = $values['admin_mail'];
    $adminSendMail = (isset($values['admin_send_mail'])) ? $values['admin_send_mail'] : 0;
    $adminSubject = (isset($values['admin_subject'])) ? $values['admin_subject'] : '';
    $adminMessage = (isset($values['admin_message']['value'])) ? $values['admin_message']['value'] : '';
    $adminFormat = (isset($values['admin_message']['format'])) ? $values['admin_message']['format'] : '';
    $clientSendMail = (isset($values['client_send_mail'])) ? $values['client_send_mail'] : 0;
    $clientSubject = (isset($values['client_subject'])) ? $values['client_subject'] : '';
    $clientMessage = (isset($values['client_message']['value'])) ? $values['client_message']['value'] : '';
    $clientFormat = (isset($values['client_message']['format'])) ? $values['client_message']['format'] : '';
    if ($count > 0) {
      $query = $db->update('simply_signups_settings');
      $query->fields([
        'start_date' => $startTimestamp,
        'end_date' => $endTimestamp,
        'path' => $altPath,
        'max_signups' => $values['max_signups'],
        'status' => $values['status'],
        'admin_mail' => $adminMail,
        'admin_send_mail' => $adminSendMail,
        'admin_subject' => $adminSubject,
        'admin_message' => $adminMessage,
        'admin_format' => $adminFormat,
        'client_send_mail' => $clientSendMail,
        'client_subject' => $clientSubject,
        'client_message' => $clientMessage,
        'client_format' => $clientFormat,
        'updated' => $requestTime,
      ]);
      $query->condition('nid', $values['nid'], '=');
      $query->execute();
    }
    if ($count == 0) {
      $query = $db->insert('simply_signups_settings');
      $query->fields([
        'nid' => $values['nid'],
        'start_date' => $startTimestamp,
        'end_date' => $endTimestamp,
        'path' => $altPath,
        'max_signups' => $values['max_signups'],
        'status' => $values['status'],
        'admin_mail' => $adminMail,
        'admin_send_mail' => $adminSendMail,
        'admin_subject' => $adminSubject,
        'admin_message' => $adminMessage,
        'admin_format' => $adminFormat,
        'client_send_mail' => $clientSendMail,
        'client_subject' => $clientSubject,
        'client_message' => $clientMessage,
        'client_format' => $clientFormat,
        'updated' => $requestTime,
        'created' => $requestTime,
      ]);
      $query->execute();
    }
    if ($values['status'] == 1) {
      $query = $db->select('simply_signups_templates', 'p');
      $query->fields('p');
      $query->condition('status', 1, '=');
      $count = $query->countQuery()->execute()->fetchField();
      if ($count == 1) {
        $results = $query->execute()->fetchAll();
        foreach ($results as $row) {
          $tid = $row->id;
          $templatesFields = $db->select('simply_signups_templates_fields', 'p');
          $templatesFields->fields('p');
          $templatesFields->condition('tid', $tid, '=');
          $templatesFieldsResults = $templatesFields->execute()->fetchAll();
          foreach ($templatesFieldsResults as $templatesFieldsRow) {
            $item = [
              'nid' => $values['nid'],
              'name' => $templatesFieldsRow->name,
              'field' => $templatesFieldsRow->field,
              'weight' => $templatesFieldsRow->weight,
              'created' => $requestTime,
              'updated' => $requestTime,
            ];
            $db->insert('simply_signups_fields')
              ->fields($item)
              ->execute();
          }
        }
      }
      /*if ($count == 1) {
        $results = $query->execute()->fetchAll();
        foreach ($results as $row) {
          $tid = $row->id;
          $item = [
            'nid' => $values['nid'],
            'name' => $row->name,
            'field' => $row->field,
            'weight' => $row->weight,
            'created' => $requestTime,
            'updated' => $requestTime,
          ];
          $nodeFieldTable = $db->select('simply_signups_fields', 'p');
          $nodeFieldTable->condition('nid', $values['nid'], '=');
          $countNodeFieldTable = $nodeFieldTable->countQuery()->execute()->fetchField();
          if ($countNodeFieldTable == 0) {
            $db->insert('simply_signups_fields')
              ->fields($item)
              ->execute();
          }
        }
      }*/
    }
    drupal_set_message($this->t("Your node's signup settings have been saved successfully."));
  }

}
