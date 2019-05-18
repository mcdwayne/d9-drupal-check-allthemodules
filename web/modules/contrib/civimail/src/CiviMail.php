<?php

namespace Drupal\civimail;

use Drupal\civicrm_tools\CiviCrmApiInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;

/**
 * Class CiviMail.
 */
class CiviMail implements CiviMailInterface {

  /**
   * Drupal\civicrm_tools\CiviCrmApiInterface.
   *
   * @var \Drupal\civicrm_tools\CiviCrmApiInterface
   */
  protected $civiCrmToolsApi;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Language\LanguageManagerInterface definition.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new CiviMail object.
   */
  public function __construct(CiviCrmApiInterface $civicrm_tools_api, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, MessengerInterface $messenger) {
    $this->civiCrmToolsApi = $civicrm_tools_api;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityMailingParams($from_cid, ContentEntityInterface $entity, array $groups) {
    // Contact (Drupal) entity does not return an email without relationships,
    // so get the contact from the CiviCRM API.
    $fromContactDetails = $this->getContact(['contact_id' => $from_cid]);

    $text = $this->getMailingTemplateText($entity);
    $renderedText = \Drupal::service('renderer')->renderRoot($text);
    $html = $this->getMailingTemplateHtml($entity);
    $renderedHtml = \Drupal::service('renderer')->renderRoot($html);
    $subject = Unicode::truncate($entity->label(), 128, TRUE, TRUE);
    $result = [
      'subject' => $subject,
      // @todo get header and footer / get template from the bundle config
      'header_id' => '',
      'footer_id' => '',
      'body_text' => $renderedText,
      'body_html' => $renderedHtml,
      'name' => $subject,
      'created_id' => $fromContactDetails['contact_id'],
      // @todo Sent by
      'from_name'  => $fromContactDetails['sort_name'],
      'from_email' => $fromContactDetails['email'],
      'replyto_email'  => $fromContactDetails['email'],
      // CiviMail removes duplicate contacts among groups.
      'groups' => [
        'include' => $groups,
        'exclude' => [],
      ],
      'api.mailing_job.create' => 1,
      'api.MailingRecipients.get' => [
        'mailing_id' => '$value.id',
        'api.contact.getvalue' => [
          'return' => 'display_name',
        ],
        'api.email.getvalue' => [
          'return' => 'email',
        ],
      ],
    ];
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getMailingTemplateHtml(ContentEntityInterface $entity) {
    $link = Link::fromTextAndUrl(t('View it online'), $this->getAbsoluteEntityUrl($entity));
    $link = $link->toRenderable();
    $build = [
      '#theme' => 'civimail_html',
      '#entity' => $entity,
      '#entity_view' => $this->getMailingBodyHtml($entity),
      // Allows template overrides to load assets provided by the current theme
      // with {{ base_path ~ directory }}.
      '#base_path' => \Drupal::request()->getSchemeAndHttpHost() . '/',
      '#absolute_link' => \Drupal::service('renderer')->renderRoot($link),
    // @todo
      '#translation_links' => NULL,
    // @todo
      '#civicrm_header' => NULL,
    // @todo
      '#civicrm_footer' => NULL,
      // Use CiviCRM token.
      '#civicrm_unsubscribe_url' => '{action.unsubscribeUrl}',
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getMailingTemplateText(ContentEntityInterface $entity) {
    $build = [
      '#theme' => 'civimail_text',
      '#entity' => $entity,
      '#entity_view' => $this->getMailingBodyText($entity),
      '#absolute_url' => $this->getAbsoluteEntityUrl($entity)->toString(),
    // @todo
      '#translation_urls' => NULL,
    // @todo
      '#civicrm_header' => NULL,
    // @todo
      '#civicrm_footer' => NULL,
      // Use CiviCRM token.
      '#civicrm_unsubscribe_url' => '{action.unsubscribeUrl}',
    ];
    return $build;
  }

  /**
   * Returns the markup for the mailing body.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity used for the body.
   *
   * @return string
   *   Markup of the entity view mode.
   */
  private function getMailingBodyHtml(ContentEntityInterface $entity) {
    $viewBuilder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    $viewMode = civimail_get_entity_bundle_settings('view_mode', $entity->getEntityTypeId(), $entity->bundle());
    $view = $viewBuilder->view($entity, $viewMode);
    $renderedView = \Drupal::service('renderer')->renderRoot($view);
    $viewWithAbsoluteUrls = $this->absolutizeUrls($renderedView);
    $build = [
      '#type' => 'markup',
      '#markup' => $viewWithAbsoluteUrls,
    ];
    return \Drupal::service('renderer')->renderRoot($build);
  }

  /**
   * Returns the mailing body as plain text.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity used for the body.
   *
   * @return string
   *   Markup of the entity view mode.
   */
  private function getMailingBodyText(ContentEntityInterface $entity) {
    // @todo implement, review Mime Mail helpers
    // $result = $this->absolutizeUrls($result);
    return 'Plain text mail not implemented yet';
  }

  /**
   * {@inheritdoc}
   */
  public function absolutizeUrls($text) {
    // @todo review possible security issue.
    $baseUrl = \Drupal::request()->getSchemeAndHttpHost();
    // Cover multi-site or public files configuration override.
    // @todo review private files and other possible cases.
    if ($wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri('public://')) {
      $publicDirectory = $wrapper->getDirectoryPath();
      $publicFilesBaseUrl = $wrapper->getExternalUrl();
    }
    $result = str_replace(
      ['href="/', 'src="/' . $publicDirectory . '/'],
      ['href="' . $baseUrl . '/', 'src="' . $publicFilesBaseUrl],
      $text
    );
    return $result;
  }

  /**
   * Returns an absolute Url to an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity to get the Url from.
   *
   * @return \Drupal\Core\Url
   *   The absolute Url to the entity.
   */
  private function getAbsoluteEntityUrl(ContentEntityInterface $entity) {
    // @todo cover other entity types.
    $result = NULL;
    switch ($entity->getEntityTypeId()) {
      case 'node':
        $result = Url::fromRoute('entity.node.canonical', ['node' => $entity->id()])->setAbsolute();
        break;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMailing(array $params, ContentEntityInterface $entity) {
    $result = FALSE;
    $mailingResult = $this->civiCrmToolsApi->save('Mailing', $params);
    if ($mailingResult['is_error'] === 0) {
      $result = TRUE;
      $executeJobUrl = Url::fromUserInput('/civicrm/admin/runjobs?reset=1',
        ['attributes' => ['target' => '_blank']]);
      $executeJobLink = Link::fromTextAndUrl(t('Send immediately'), $executeJobUrl);
      $executeJobLink = $executeJobLink->toRenderable();
      $executeJobLink = render($executeJobLink);
      // @todo review execute job link by something more accurate than running all the jobs.
      // $result = civicrm_api3('Mailing', 'submit', $params);
      // @todo optionally execute process_mailing job via bundle configuration
      // civicrm_api3_job_process_mailing($params); // in API v3 Job.php
      $message = t('CiviMail mailing for <em>@subject</em> scheduled. @execute_job_link.',
        [
          '@subject' => $params['subject'],
          '@execute_job_link' => $executeJobLink,
        ]
      );
      $this->messenger->addStatus($message);
      $this->logMailing($mailingResult, $entity, $params['groups']['include']);
    }
    else {
      // @todo get exception result
      $this->messenger->addError(t('Error while sending the mailing.'));
    }
    return $result;
  }

  /**
   * Logs the relation between the CiviCRM mailing, groups and the entity.
   *
   * @param array $mailing_result
   *   The CiviCRM mailing result.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity that is the subject of the mailing.
   * @param array $groups
   *   List of CiviCRM group ids for the mailing.
   */
  private function logMailing(array $mailing_result, ContentEntityInterface $entity, array $groups) {
    $user = \Drupal::currentUser();
    $fields = [
      'entity_id' => (int) $entity->id(),
      'entity_type_id' => (string) $entity->getEntityTypeId(),
      'entity_bundle' => (string) $entity->bundle(),
      'langcode' => (string) $entity->language()->getId(),
      'uid' => (int) $user->id(),
      'civicrm_mailing_id' => (int) $mailing_result['id'],
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];
    try {
      $insert = \Drupal::database()->insert('civimail_entity_mailing');
      $insert->fields($fields);
      $insert->execute();

      foreach ($groups as $groupId) {
        $insert = \Drupal::database()->insert('civimail_entity_mailing__group');
        $fields = [
          'civicrm_mailing_id' => (int) $mailing_result['id'],
          'civicrm_group_id' => (int) $groupId,
        ];
        $insert->fields($fields);
        $insert->execute();
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('civimail')->error($e->getMessage());
      \Drupal::messenger()->addError($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityMailingHistory(ContentEntityInterface $entity) {
    $result = [];
    // @todo optimization is necessary here.
    $query = \Drupal::database()->select('civimail_entity_mailing', 'logs');
    $query->fields('logs', ['civicrm_mailing_id'])
      ->condition('logs.entity_id', $entity->id())
      ->condition('logs.entity_type_id', $entity->getEntityTypeId())
      ->condition('logs.entity_bundle', $entity->bundle())
      ->condition('logs.langcode', $entity->language()->getId());
    $query->orderBy('logs.civicrm_mailing_id', 'DESC');
    $logsResult = $query->execute()->fetchAll();
    foreach ($logsResult as $row) {
      // Get the details of the mailing.
      $civiCrmMailing = $this->civiCrmToolsApi->get('Mailing', ['id' => $row->civicrm_mailing_id]);
      // There does not seem to be any api that gets mailing groups,
      // an issue could be opened for that.
      // A Drupal table currently stores the results.
      $query = \Drupal::database()->select('civimail_entity_mailing__group', 'mailing_group');
      $query->fields('mailing_group', ['civicrm_group_id'])
        ->condition('mailing_group.civicrm_mailing_id', $row->civicrm_mailing_id);
      $groupsResult = $query->execute()->fetchAll();
      $rowResult = [];
      $rowResult['mailing'] = $civiCrmMailing[$row->civicrm_mailing_id];
      $rowResult['groups'] = [];
      foreach ($groupsResult as $groupRow) {
        $rowResult['groups'][] = $groupRow->civicrm_group_id;
      }
      // Wrap all together.
      $result[$row->civicrm_mailing_id] = $rowResult;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function sendTestMail($from_cid, ContentEntityInterface $entity, $to_mail) {
    // This should be available from the CiviCRM API,
    // currently delegating it to Drupal mail.
    if (!\Drupal::moduleHandler()->moduleExists('mimemail')) {
      // And ideally tests should not be done by Mime Mail
      // but straight from CiviMail. CiviCRM tokens will not be available.
      $this->messenger->addWarning(t('You can add support for HTML test mails by installing the Mime Mail module.'));
    }
    // @todo use text mode
    // $textBuild = $this->getMailingTemplateText($entity);
    // $textBuild = $this->removeCiviCrmTokens($textBuild);
    // $textBuild['#is_test'] = TRUE;
    // $renderedText = \Drupal::service('renderer')->renderRoot($textBuild);
    $htmlBuild = $this->getMailingTemplateHtml($entity);
    $htmlBuild = $this->removeCiviCrmTokens($htmlBuild);
    $htmlBuild['#is_test'] = TRUE;
    $renderedHtml = \Drupal::service('renderer')->renderRoot($htmlBuild);

    // @todo the subject in an email can't be with HTML, so strip it.
    $params['subject'] = t('[TEST] @subject', ['@subject' => $entity->label()]);
    // @todo get header and footer / get template from the bundle config
    $params['body'] = $renderedHtml;
    // Pass the message entity along to hook_drupal_mail().
    $params['entity'] = $entity;

    // Pass the relevant from contact data to hook_drupal_mail().
    // Fallback to system defaults.
    $fromContact = $this->getContact(['contact_id' => $from_cid]);
    $systemConfig = \Drupal::configFactory()->get('system.site');
    $params['from_mail'] = empty($fromContact['email']) ? $systemConfig->get('mail') : $fromContact['email'];
    $params['from_name'] = empty($fromContact['display_name']) ? $systemConfig->get('name') : $fromContact['display_name'];

    $result = \Drupal::service('plugin.manager.mail')->mail(
      'civimail',
      'entity_test_mail',
      $to_mail,
      $entity->language()->getId(),
      $params
    );

    if ($result) {
      \Drupal::messenger()->addMessage(t('Your test has been sent to @mail.', ['@mail' => $to_mail]));
    }
    else {
      \Drupal::messenger()->addError(t('There has been an error while sending the test to @mail.', ['@mail' => $to_mail]));
    }

    return $result;
  }

  /**
   * Removes CiviCRM tokens from a mail render array.
   *
   * To be used by sendTestMail() because of Mime Mail delegation
   * that ignores the CiviCRM context.
   *
   * @param array $build
   *   The mail render array.
   *
   * @return array
   *   The rendered array without the CiviCRM tokens.
   */
  public function removeCiviCrmTokens(array $build) {
    $build['#civicrm_unsubscribe_url'] = NULL;
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRoute($entity_type_id) {
    $entity = NULL;
    $entityId = \Drupal::routeMatch()->getParameter($entity_type_id);
    try {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entityId);
      // Check if the site is multilingual.
      // If so, set the current entity to the current interface language
      // when it has a translation.
      $languageManager = \Drupal::languageManager();
      if ($languageManager->isMultilingual()) {
        $languageId = $languageManager->getCurrentLanguage()->getId();
        if ($entity->hasTranslation($languageId)) {
          $entity = $entity->getTranslation($languageId);
        }
      }
    }
    catch (InvalidPluginDefinitionException $exception) {
      $this->messenger->addError($exception->getMessage());
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getContact(array $filter) {
    $result = [];
    $contacts = $this->civiCrmToolsApi->get('Contact', $filter);
    // @todo getting the first contact found for the match
    // improve by letting know the user that there is probably
    // a contact mismatch because civicrm api returns default one if not found.
    if (!empty($contacts)) {
      reset($contacts);
      $result = $contacts[key($contacts)];
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupSelectOptions(array $filter = []) {
    $result = [];
    $groups = $this->civiCrmToolsApi->getAll('Group', $filter);
    foreach ($groups as $gid => $group) {
      $result[$gid] = $group['title'];
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getContactSelectOptions(array $filter) {
    $result = [];
    $contacts = $this->civiCrmToolsApi->getAll('Contact', $filter);
    foreach ($contacts as $cid => $contact) {
      $result[$cid] = $contact['display_name'] . ' <' . $contact['email'] . '>';
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function hasCiviCrmRequirements() {
    $result = FALSE;
    // Check if CiviCRM Tools module is installed.
    if (\Drupal::moduleHandler()->moduleExists('civicrm_tools')) {
      $result = TRUE;
    }
    else {
      $this->messenger->addError(t('CiviCRM Entity module is not installed.'));
    }
    return $result;
  }

}
