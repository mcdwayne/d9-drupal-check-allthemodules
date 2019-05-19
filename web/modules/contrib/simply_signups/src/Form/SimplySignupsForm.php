<?php

namespace Drupal\simply_signups\Form;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\simply_signups\Utility\SimplySignupsUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a signup form.
 */
class SimplySignupsForm extends FormBase {

  protected $time;
  protected $database;
  protected $currentPath;
  protected $requestStack;
  protected $configFactory;
  protected $entityTypeManager;

  /**
   * Implements __construct().
   */
  public function __construct(TimeInterface $time_interface, CurrentPathStack $current_path, RequestStack $request_stack, Connection $database_connection, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->time = $time_interface;
    $this->currentPath = $current_path;
    $this->requestStack = $request_stack;
    $this->database = $database_connection;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Implements create().
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('datetime.time'),
      $container->get('path.current'),
      $container->get('request_stack'),
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simply_signups_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $nid = NULL) {
    $db = $this->database;
    $query = $db->select('simply_signups_fields', 'p');
    $query->fields('p');
    $query->orderBy('weight');
    $query->condition('nid', $nid, '=');
    $results = $query->execute()->fetchAll();
    $fieldCount = $query->countQuery()->execute()->fetchField();
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-rsvp-form', 'simply-signups-form'],
    ];
    if ($fieldCount > 0) {
      $form['signup_fieldset'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Signup for this event'),
      ];
      $results = $query->execute()->fetchAll();
      foreach ($results as $row) {
        $field = unserialize($row->field);
        $form['signup_fieldset'][$field['#title']] = [
          '#type' => $field['#type'],
          '#title' => $row->name,
          '#description' => (isset($field['#description'])) ? $field['#description'] : '',
          '#default_value' => (isset($field['#default_value'])) ? $field['#default_value'] : '',
          '#required' => $field['#required'],
        ];
        if ($field['#type'] == 'select') {
          unset($form['signup_fieldset'][$field['#title']]);
          $form['signup_fieldset'][$field['#title']] = [
            '#type' => $field['#type'],
            '#title' => $row->name,
            '#options' => $field['#options'],
            '#description' => (isset($field['#description'])) ? $field['#description'] : '',
            '#default_value' => (isset($field['#default_value'])) ? [$field['#default_value']] : NULL,
            '#multiple' => $field['#multiple'],
            '#required' => $field['#required'],
          ];
        }
        if (($field['#type'] == 'radios') or ($field['#type'] == 'checkboxes')) {
          unset($form['signup_fieldset'][$field['#title']]);
          $form['signup_fieldset'][$field['#title']] = [
            '#type' => $field['#type'],
            '#title' => $row->name,
            '#options' => $field['#options'],
            '#description' => (isset($field['#description'])) ? $field['#description'] : '',
            '#default_value' => (isset($field['#default_value'])) ? [$field['#default_value']] : NULL,
            '#required' => $field['#required'],
          ];
        }
        if ($field['#type'] == 'number') {
          unset($form['signup_fieldset'][$field['#title']]);
          $form['signup_fieldset'][$field['#title']] = [
            '#type' => $field['#type'],
            '#title' => $row->name,
            '#options' => $field['#options'],
            '#description' => (isset($field['#description'])) ? $field['#description'] : '',
            '#default_value' => (isset($field['#default_value'])) ? [$field['#default_value']] : NULL,
            '#step' => $field['#step'],
            '#min' => $field['#min'],
            '#max' => $field['#max'],
            '#prefix' => $field['#prefix'],
            '#suffix' => $field['#suffix'],
            '#required' => $field['#required'],
          ];
        }
      }
      $form['signup_fieldset']['nid'] = [
        '#type' => 'hidden',
        '#value' => $nid,
      ];
      $form['signup_fieldset']['actions'] = [
        '#type' => 'actions',
      ];
      $form['signup_fieldset']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Signup'),
        '#attributes' => [
          'class' => [
            'btn-primary',
          ],
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $node = $values['nid'];
    $numberOfAttending = SimplySignupsUtility::getNumberOfAttending($node);
    $maxAttending = SimplySignupsUtility::getMaxAttending($node);
    $totalAttending = ($values['number_attending'] + $numberOfAttending);
    if ($maxAttending > 0 and $totalAttending > $maxAttending) {
      $form_state->setErrorByName('number_attending', $this->t('Sorry but the number of attending that you designated would be over the maximum attending allowed for this event.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $nid = $values['nid'];
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($nid);
    $requestTime = $this->time->getCurrentTime();
    $date = $this->dateFormatter->format($requestTime, 'custom', 'm/d/Y - h:i a');
    $options = ['absolute' => TRUE];
    $url = Url::fromRoute('simply_signups.nodes', ['node' => $nid], $options);
    $link = Link::fromTextAndUrl('View all submissions', $url)->toString();
    foreach ($values as $key => $value) {
      if ($key != 'submit' and $key != 'form_build_id' and $key != 'form_token' and $key != 'form_id' and $key != 'op' and isset($form['signup_fieldset'][$key]['#title'])) {
        $fields['fields'][$key]['type'] = $form['signup_fieldset'][$key]['#type'];
        $fields['fields'][$key]['value'] = $value;
        $fields['fields'][$key]['title'] = $form['signup_fieldset'][$key]['#title'];
      }
    }
    unset($fields['fields']['submit']);
    unset($fields['fields']['form_build_id']);
    unset($fields['fields']['form_token']);
    unset($fields['fields']['form_id']);
    unset($fields['fields']['op']);
    $numberAttendingFlag = (isset($fields['fields']['number_attending'])) ? 1 : 0;
    $fields['fields']['number_attending']['type'] = 'select';
    $fields['fields']['number_attending']['value'] = (isset($fields['fields']['number_attending']['value'])) ? $fields['fields']['number_attending']['value'] : 1;
    $fields['fields']['number_attending']['title'] = (isset($fields['fields']['number_attending']['title'])) ? $fields['fields']['number_attending']['title'] : '# Attending';
    $numberAttending = $fields['fields']['number_attending']['value'];
    if ($numberAttendingFlag == 0) {
      unset($fields['fields']['number_attending']);
    }
    $row = [
      'nid' => $nid,
      'fields' => serialize($fields['fields']),
      'attending' => $numberAttending,
      'status' => 0,
      'created' => $requestTime,
      'updated' => $requestTime,
    ];
    db_insert('simply_signups_data')->fields($row)->execute();
    $db = $this->database;
    $query = $db->select('simply_signups_settings', 'p');
    $query->fields('p');
    $query->condition('nid', $nid, '=');
    $results = $query->execute()->fetchAll();
    $system = $this->configFactory->get('system.site');
    $siteName = $system->get('name');
    $host = $this->requestStack->getHost();
    foreach ($results as $row) {
      $nid = $row->nid;
      $altPath = $row->path;
      $isValidAbsolute = UrlHelper::isValid($altPath, TRUE);
      $adminSendMail = $row->admin_send_mail;
      $adminMail = (valid_email_address($row->admin_mail)) ? $row->admin_mail : 'webmaster@' . $host;
      $adminSubject = (!empty($row->admin_subject)) ? $row->admin_subject : 'RSVP for: [node:title] - ' . $date;
      $adminMessage = (!empty($row->admin_message)) ? $row->admin_message : '<p>A RSVP has been submitted for an event below.</p><h3>[node:title]</h3><p>[node:url]<br />' . $link . '</p><p>Submitted on: ' . $this->dateFormatter->format($requestTime, 'custom', 'm/d/Y - h:i a') . '</p>';
      $adminFormat = $row->admin_format;
      $clientSendMail = $row->client_send_mail;
      $clientSubject = (!empty($row->client_subject)) ? $row->client_subject : 'RSVP Confirmation: [node:title] - ' . $date;
      $clientMessage = (!empty($row->send_message)) ? $row->send_message : "<h3>Thank you for your RSVP!</h3><p>We're happy to see that you're interested in coming to our event, [node:title]!</p><p>You can find event details at [node:url]</p><p>If you are no longer able to attend the event, that's fine! We kindly ask that you let us know by contacting us at: [site:mail].</p><p>Thank you! We look forward to seeing you soon.</p><p>Sincerely,<br />[site:name]</p>";
      $clientFormat = $row->client_format;
    }
    $send = new PhpMail();
    $from = $adminMail;

    $message['headers'] = [
      'Content-Type' => 'text/html; charset=UTF-8; format=flowed; delsp=yes',
      'MIME-Version' => '1.0',
      'reply-to' => $from,
      'from' => $siteName . ' <' . $from . '>',
    ];
    $message['format'] = 'text/html';
    foreach ($fields['fields'] as $key => $value) {
      if (valid_email_address($value['value'])) {
        $to = $value['value'];
      }
    }
    $token_service = $this->token;
    if ($clientSendMail == 1) {
      $clientTokenSubject = $token_service->replace($clientSubject, ['node' => $node]);
      $clientTokenMessage = $token_service->replace($clientMessage, ['node' => $node]);
      $clientSubject = check_markup($clientTokenSubject, $clientFormat);
      $clientMessage = check_markup($clientTokenMessage, $clientFormat);
      if (valid_email_address($to)) {
        $message['to'] = $to;
        $message['subject'] = $clientSubject;
        $message['body'] = Markup::create($clientMessage);
        $result = $send->mail($message);
        if ($result !== TRUE) {
          drupal_set_message($this->t('Unable to send mail please contact the webmaster.'), 'error');
        }
      }
    }
    if ($adminSendMail == 1) {
      $adminTokenMail = $token_service->replace($adminMail, ['node' => $node]);
      $adminMail = $adminTokenMail;
      $adminTokenSubject = $token_service->replace($adminSubject, ['node' => $node]);
      $adminTokenMessage = $token_service->replace($adminMessage, ['node' => $node]);
      $adminSubject = check_markup($adminTokenSubject, $adminFormat);
      $adminMessage = check_markup($adminTokenMessage, $adminFormat);
      if (valid_email_address($adminMail)) {
        $adminSubject = $adminTokenSubject;
        $adminMessage = $adminTokenMessage;
        $message['to'] = $adminMail;
        $message['subject'] = $adminSubject;
        $message['body'] = $adminMessage;
        $message['body'] = Markup::create($message['body']);
        $result = $send->mail($message);
        if ($result !== TRUE) {
          drupal_set_message($this->t('Unable to send mail please contact the webmaster.'), 'error');
        }
      }
    }
    if ($isValidAbsolute == 1) {
      $altUrl = Url::fromUri($altPath)->toString();
      $response = new TrustedRedirectResponse($altUrl);
      $form_state->setResponse($response);
    }
    drupal_set_message($this->t('Your signup has been submitted successsfully.'));
  }

}
