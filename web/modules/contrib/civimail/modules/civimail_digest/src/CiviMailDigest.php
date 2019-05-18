<?php

namespace Drupal\civimail_digest;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\civicrm_tools\CiviCrmApiInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CiviMailDigest.
 */
class CiviMailDigest implements CiviMailDigestInterface {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\civicrm_tools\CiviCrmApiInterface definition.
   *
   * @var \Drupal\civicrm_tools\CiviCrmApiInterface
   */
  protected $civicrmToolsApi;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Config\ImmutableConfig definition.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $digestConfig;

  /**
   * Constructs a new CiviMailDigest object.
   */
  public function __construct(Connection $database, CiviCrmApiInterface $civicrm_tools_api, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->database = $database;
    $this->civicrmToolsApi = $civicrm_tools_api;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->digestConfig = $this->configFactory->get('civimail_digest.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    $result = FALSE;
    if (!$this->digestConfig->get('is_active')) {
      \Drupal::messenger()->addWarning(t('The digest feature is not enabled.'));
    }
    else {
      $result = TRUE;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function isSchedulerActive() {
    return (bool) $this->digestConfig->get('is_scheduler_active');
  }

  /**
   * {@inheritdoc}
   */
  public function hasNextDigestContent() {
    return !empty($this->prepareDigestContent()['entities']);
  }

  /**
   * {@inheritdoc}
   */
  public function getLastDigestTimeStamp() {
    $query = $this->database->select('civimail_digest', 'cd');
    $query->condition('status', [self::STATUS_PREPARED, self::STATUS_SENT], 'IN');
    $query->addExpression('MAX(timestamp)', 'max_timestamp');
    return $query->execute()->fetchField();
  }

  /**
   * Get the content entity ids from CiviMail mailings for a digest.
   *
   * Keeping this structure as private as it is not really convenient
   * to manipulate but avoids running too many queries to get both
   * mailing ids and entity ids.
   *
   * @return array
   *   Entities (content entity ids grouped by entity type ids) and mailings.
   */
  private function prepareDigestContent() {
    $result = [];
    if ($this->isActive()) {
      $civiMailMailings = $this->selectDigestMailings();
      $result = [
        'entities' => [],
        'mailings' => [],
      ];
      foreach ($civiMailMailings as $row) {
        if (empty($result['entities'][$row->entity_type_id])) {
          $result['entities'][$row->entity_type_id] = [];
        }
        $result['entities'][$row->entity_type_id][] = $row->entity_id;
        $result['mailings'][] = $row->civicrm_mailing_id;
      }
    }
    return $result;
  }

  /**
   * Selects the CiviMail mailings to be included in a digest.
   *
   * These mailings are evaluated from CiviMail mailings that were
   * previously sent and from the configured limitations.
   *
   * @return array
   *   CiviMail mailings rows and their entity references.
   */
  private function selectDigestMailings() {
    $configuredBundles = $this->digestConfig->get('bundles');
    $bundles = [];
    // Get rid of the keys, take only values if they are the same.
    foreach ($configuredBundles as $key => $configuredBundle) {
      if ($configuredBundle === $key) {
        $bundles[] = $configuredBundle;
      }
    }

    // Get all the CiviMail mailings for entities that are matching
    // the configuration limitations.
    $query = $this->database->select('civimail_entity_mailing', 'cem')
      ->fields('cem', [
        'entity_id',
        'entity_type_id',
        'civicrm_mailing_id',
      ]
    );
    // In all cases, exclude mailings that were already part of a digest.
    $sentMailings = $this->selectSentDigestMailings();
    if (!empty($sentMailings)) {
      $query->condition('cem.civicrm_mailing_id', $sentMailings, 'NOT IN');
    }

    // Limit by bundle and entity type.
    // @todo extend to other entity types
    $query->condition('cem.entity_type_id', 'node');
    $query->condition('cem.entity_bundle', $bundles, 'IN');

    // Depending on the configuration exclude or not
    // entities that were part of a previous entity mailing
    // (some entities could have been sent multiple times).
    $includeUpdate = $this->digestConfig->get('include_update');
    // If updates must be excluded, just exclude entities that were
    // part of any digest.
    if (!$includeUpdate) {
      // @todo extend to other entity types
      $sentEntities = $this->getSentEntities('node', $sentMailings);
      $query->condition('cem.entity_id', $sentEntities, 'NOT IN');
      // If updates must be included, always exclude mailing ids for the entity
      // that are < to the included update.
      // Otherwise, previous mailing id's can be included on the next digest.
    }
    else {
      // @todo extend to other entity types
      $lastMailings = $this->getLastMailingIds();
      $query->condition('cem.civicrm_mailing_id', $lastMailings, 'IN');
    }

    // Then apply the configured limitations.
    // Maximum age.
    $maxDays = $this->digestConfig->get('age_in_days');
    // @fixme get from system settings / handle timezone
    $timeZone = new \DateTimeZone('Europe/Brussels');
    $contentAge = new \DateTime('now -' . $maxDays . ' day', $timeZone);
    $query->condition('cem.timestamp', $contentAge->getTimestamp(), '>');
    // Language.
    $language = $this->digestConfig->get('language');
    $query->condition('cem.langcode', $language);

    // Maximum quantity.
    $quantityLimit = $this->digestConfig->get('quantity_limit');
    // $query->range(0, $quantityLimit);.
    $query->orderBy('cem.timestamp', 'DESC');
    $queryResult = $query->execute()->fetchAll();

    // At this stage, we can still have the same entity sent in
    // several mailings, so reduce them to the last mailing per entity.
    // @todo refactor query with nested query and MAX
    // then apply query range instead of slice.
    $result = [];
    foreach ($queryResult as $row) {
      if (!array_key_exists($row->entity_type_id . '-' . $row->entity_id, $result)) {
        $result[$row->entity_type_id . '-' . $row->entity_id] = $row;
      }
    }

    $result = array_slice($result, 0, $quantityLimit);

    return $result;
  }

  /**
   * Returns entities that were part of an entity mailing included in a digest.
   *
   * @param string $entity_type_id
   *   Entity type id.
   * @param array $sent_digest_mailings
   *   Id of entity mailings that were part of a digest.
   *
   * @return array
   *   List of entity ids.
   */
  private function getSentEntities($entity_type_id, array $sent_digest_mailings) {
    $query = $this->database->select('civimail_entity_mailing', 'cem')
      ->fields('cem', [
        'entity_id',
      ]
    );
    $query->condition('cem.civicrm_mailing_id', $sent_digest_mailings, 'IN');
    $query->condition('cem.entity_type_id', $entity_type_id);
    $query->distinct();
    $queryResult = $query->execute()->fetchAll();
    foreach ($queryResult as $row) {
      $result[] = $row->entity_id;
    }
    return $result;
  }

  /**
   * Returns the last mailing ids for each entity.
   *
   * @return array
   *   List of mailing ids.
   */
  private function getLastMailingIds() {
    $query = $this->database->select('civimail_entity_mailing', 'cem');
    $query->groupBy('entity_type_id')->groupBy('entity_id');
    $query->addExpression('MAX(civicrm_mailing_id)', 'max_mailing_id');
    $queryResult = $query->execute()->fetchAll();
    foreach ($queryResult as $row) {
      $result[] = $row->max_mailing_id;
    }
    return $result;
  }

  /**
   * Selects all the mailing that have already been included in a digest.
   *
   * This must not be confused with the mailing id of the Digest itself.
   * These ones are the mailing that were sent via CiviMail that have
   * been part of a digest.
   *
   * @todo review implementation because the name implies a query result.
   *
   * @return array
   *   List of mailing ids.
   */
  private function selectSentDigestMailings() {
    $result = [];
    $query = $this->database->select('civimail_digest__mailing', 'cdm');
    $queryResult = $query->fields('cdm', ['civicrm_mailing_id'])->execute();
    foreach ($queryResult as $row) {
      $result[] = $row->civicrm_mailing_id;
    }
    return $result;
  }

  /**
   * Select digest content entities.
   *
   * @param int $digest_id
   *   Digest id.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   Digest content entities.
   */
  private function selectDigestEntities($digest_id) {
    $query = $this->database->select('civimail_digest__mailing', 'cdm');
    $query->condition('cdm.digest_id', $digest_id);
    $query->fields('cdm', ['digest_id']);
    $query->join(
      'civimail_entity_mailing',
      'cem',
      'cem.civicrm_mailing_id = cdm.civicrm_mailing_id');
    // Check the node status.
    // @see https://github.com/colorfield/civimail/issues/5
    // @todo support for other entity types, using entity_type_id
    $query->join(
      'node_field_data',
      'nfd',
      'nfd.nid = cem.entity_id'
    );
    $query->condition('nfd.status', 1);
    $query->fields('cem', [
      'entity_type_id',
      'entity_id',
    ]);
    $query->orderBy('cem.timestamp', 'DESC');
    $result = $query->execute();
    return $result;
  }

  /**
   * Selects the digests and their status.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   Digest related data for the digest list.
   */
  private function selectDigests() {
    $query = $this->database->select('civimail_digest', 'cd');
    $query->fields('cd', ['id', 'status', 'timestamp']);
    // leftJoin as groups couldn't be defined yet if
    // the digest status is not 'sent'.
    $query->leftJoin(
      'civimail_digest__group',
      'cg',
      'cg.digest_id = cd.id');
    $query->fields('cg', [
      'civicrm_group_id',
    ]);
    $query->orderBy('cd.id', 'DESC');
    $result = $query->execute();
    return $result;
  }

  /**
   * Updates the digest status.
   *
   * @param int $digest_id
   *   Digest id.
   * @param int $status
   *   Digest status.
   *
   * @return \Drupal\Core\Database\StatementInterface|int|null
   *   Database update result.
   */
  private function updateDigestStatus($digest_id, $status) {
    return $this->database->update('civimail_digest')
      ->fields(['status' => $status])
      ->condition('id', $digest_id)
      ->execute();
  }

  /**
   * Updates the digest status and mailing id.
   *
   * @param int $digest_id
   *   Digest id.
   * @param int $status
   *   Digest status.
   * @param int $digest_mailing_id
   *   Digest CiviCRM mailing id.
   *
   * @return \Drupal\Core\Database\StatementInterface|int|null
   *   Database update result.
   */
  private function updateDigest($digest_id, $status, $digest_mailing_id) {
    return $this->database->update('civimail_digest')
      ->fields(['status' => $status, 'civicrm_mailing_id' => $digest_mailing_id])
      ->condition('id', $digest_id)
      ->execute();
  }

  /**
   * Inserts the sent groups for a digest.
   *
   * @param int $digest_id
   *   Digest id.
   * @param array $groups
   *   List of CiviCRM group ids.
   *
   * @return bool
   *   Database insert result
   */
  private function insertDigestSentGroups($digest_id, array $groups) {
    $result = FALSE;
    try {
      foreach ($groups as $groupId) {
        // @todo insert all values in one query.
        $result = $this->database->insert('civimail_digest__group')
          ->fields([
            'digest_id' => $digest_id,
            'civicrm_group_id' => $groupId,
          ])
          ->execute();
      }
    }
    catch (\Exception $exception) {
      \Drupal::messenger()->addError($exception->getMessage());
    }
    return $result;
  }

  /**
   * Retrieves the digest content that has been prepared.
   *
   * @param int $digest_id
   *   Digest id.
   *
   * @return array
   *   List of entity ids groupe by entity type id.
   */
  private function getDigestContent($digest_id) {
    $result = [];
    $queryResult = $this->selectDigestEntities($digest_id);
    foreach ($queryResult as $row) {
      if (empty($result[$row->entity_type_id])) {
        $result[$row->entity_type_id] = [];
      }
      $result[$row->entity_type_id][] = $row->entity_id;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function previewDigest() {
    $digestContent = $this->prepareDigestContent();
    $digest = [];
    if (!empty($digestContent['entities'])) {
      $entities = $this->getDigestEntities($digestContent['entities']);
      $digest = $this->buildDigest($entities);
    }
    return $this->getDigestAsResponse($digest);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareDigest() {
    $result = NULL;
    $digestContent = $this->prepareDigestContent();
    if (!empty($digestContent['entities'])) {
      // Get a new digest id.
      $digestId = $this->getNewDigestId();
      if (NULL !== $digestId) {
        // Store each mailing id with a reference to the digest id.
        try {
          // @todo insert all values in one query.
          foreach ($digestContent['mailings'] as $mailingId) {
            $fields = [
              'digest_id' => $digestId,
              'civicrm_mailing_id' => $mailingId,
              'timestamp' => \Drupal::time()->getRequestTime(),
            ];
            $this->database->insert('civimail_digest__mailing')
              ->fields($fields)
              ->execute();
          }
          // Set then the digest status to prepared.
          $this->updateDigestStatus($digestId, CiviMailDigestInterface::STATUS_PREPARED);
          $result = $digestId;
        }
        catch (\Exception $exception) {
          \Drupal::logger('civimail_digest')->error($exception->getMessage());
          \Drupal::messenger()->addError($exception->getMessage());
        }
      }
    }

    if ($result) {
      \Drupal::messenger()->addStatus(t('The digest @id has been prepared.', ['@id' => $digestId]));
    }
    else {
      \Drupal::messenger()->addError(t('An error occured while preparing the digest. You may check if there is content available first, by using the preview.'));
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function viewDigest($digest_id) {
    $entityIds = $this->getDigestContent($digest_id);
    $digest = [];
    if (!empty($entityIds)) {
      $entities = $this->getDigestEntities($entityIds);
      $digest = $this->buildDigest($entities, $digest_id);
    }
    return $this->getDigestAsResponse($digest);
  }

  /**
   * Renders a digest and wrap it into a Response.
   *
   * @param array $digest
   *   Digest render array.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Digest response.
   */
  private function getDigestAsResponse(array $digest) {
    // @todo dependency injection
    /** @var \Drupal\Core\Render\Renderer $renderer */
    $renderer = \Drupal::service('renderer');
    if (!empty($digest)) {
      $output = $renderer->renderRoot($digest);
    }
    else {
      $noResults = [
        '#markup' => t('No content for the digest.'),
      ];
      $output = $renderer->renderRoot($noResults);
    }
    return new Response($output);
  }

  /**
   * Loads the entities and prepares the view modes for the digest content.
   *
   * @param array $content
   *   List of entities grouped by entity types.
   *
   * @return array
   *   List of rendered entities.
   */
  private function getDigestEntities(array $content) {
    $result = [];
    // @todo assert defined
    $digestViewMode = $this->digestConfig->get('view_mode');
    foreach ($content as $entityTypeId => $entityIds) {
      try {
        $entities = $this->entityTypeManager->getStorage($entityTypeId)->loadMultiple($entityIds);
        foreach ($entities as $entity) {
          $viewBuilder = $this->entityTypeManager->getViewBuilder($entityTypeId);
          $view = $viewBuilder->view($entity, $digestViewMode);
          $renderedView = \Drupal::service('renderer')->renderRoot($view);
          // @todo security check, review the need of Xss::filter
          $renderedView = Html::decodeEntities($renderedView);
          $viewWithAbsoluteUrls = $this->absolutizeUrls($renderedView);
          $result[] = $viewWithAbsoluteUrls;
        }
      }
      catch (InvalidPluginDefinitionException $exception) {
        \Drupal::messenger()->addError($exception->getMessage());
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  private function absolutizeUrls($html) {
    // @todo port CiviMail absolutizeUrls and replace this private method.
    $baseUrl = \Drupal::request()->getSchemeAndHttpHost();
    // Convert the whole message body. Returns string.
    $markupWithAbsoluteUrls = Html::transformRootRelativeUrlsToAbsolute($html, $baseUrl);
    // In case you need an instance of Markup class prepare it here.
    $result = Markup::create($markupWithAbsoluteUrls);
    return $result;
  }

  /**
   * Builds the rendered array for a digest.
   *
   * @param array $entities
   *   List of rendered entities.
   * @param int $digest_id
   *   Digest id.
   *
   * @return array
   *   Render array of the digest.
   */
  private function buildDigest(array $entities, $digest_id = NULL) {
    // @todo add text
    // @todo refactor CiviMail service
    $currentDigestId = $digest_id;
    if (is_null($digest_id)) {
      // @todo get it by incrementing the last digest id.
      $currentDigestId = 0;
    }
    return [
      '#theme' => 'civimail_digest_html',
      '#entities' => $entities,
      '#digest_title' => $this->getDigestTitle(),
      '#digest_id' => $currentDigestId,
      // Use CiviCRM token.
      '#civicrm_unsubscribe_url' => '{action.unsubscribeUrl}',
      // Allows template overrides to load assets provided by the current theme
      // with {{ base_path ~ directory }}.
      '#base_path' => \Drupal::request()->getSchemeAndHttpHost() . '/',
      '#absolute_link' => $this->getAbsoluteDigestLink($currentDigestId),
      '#absolute_url' => $this->getAbsoluteDigestUrl($currentDigestId),
    ];
  }

  /**
   * Returns the digest title.
   *
   * @return string
   *   Digest title.
   */
  private function getDigestTitle() {
    return $this->digestConfig->get('digest_title');
  }

  /**
   * Returns the absolute digest url.
   *
   * @param int $digest_id
   *   Digest id.
   *
   * @return \Drupal\Core\Url
   *   Digest url.
   */
  private function getAbsoluteDigestUrl($digest_id) {
    return Url::fromRoute('civimail_digest.view', ['digest_id' => $digest_id])->setAbsolute();
  }

  /**
   * Returns an absolute link to a digest view.
   *
   * @param int $digest_id
   *   Digest id.
   *
   * @return array|\Drupal\Core\Link
   *   Absolute link to the digest.
   */
  private function getAbsoluteDigestLink($digest_id) {
    $link = Link::fromTextAndUrl(t('View it online'), $this->getAbsoluteDigestUrl($digest_id));
    $link = $link->toRenderable();
    return $link;
  }

  /**
   * Creates a new digest id in the digest table and returns it.
   *
   * @return int
   *   The digest id.
   */
  private function getNewDigestId() {
    $result = NULL;
    try {
      $fields = [
        'status' => CiviMailDigestInterface::STATUS_CREATED,
        'timestamp' => \Drupal::time()->getRequestTime(),
      ];
      // Returns the serial id of the digest.
      $result = $this->database->insert('civimail_digest')
        ->fields($fields)
        ->execute();
    }
    catch (\Exception $exception) {
      \Drupal::logger('civimail_digest')->error($exception->getMessage());
      \Drupal::messenger()->addError($exception->getMessage());
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getDigests() {
    $result = [];
    $queryResult = $this->selectDigests();
    /** @var \Drupal\civicrm_tools\CiviCrmGroupInterface $civiCrmGroupTools */
    $civiCrmGroupTools = \Drupal::service('civicrm_tools.group');
    foreach ($queryResult as $row) {
      // Aggregate results.
      if (!array_key_exists($row->id, $result)) {
        $result[$row->id] = [
          'id' => (int) $row->id,
          'status_id' => (int) $row->status,
          'status_label' => $this->getDigestStatusLabel($row->status),
          'timestamp' => (int) $row->timestamp,
          'groups' => [],
        ];
      }
      // Set group title.
      if (NULL !== $row->civicrm_group_id) {
        $group = $civiCrmGroupTools->getGroup($row->civicrm_group_id);
        $result[$row->id]['groups'][] = $group['title'];
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function sendTestDigest($digest_id) {
    // TODO: Implement sendTestDigest() method.
  }

  /**
   * {@inheritdoc}
   */
  public function sendDigest($digest_id) {
    // Check if the digest feature is active.
    if (!$this->isActive()) {
      // @todo add hint for digest configuration.
      \Drupal::messenger()->addError(t('CiviMail digest is currently inactive.'));
      return;
    }
    // Check the digest status before sending.
    if (!$this->canSend($digest_id)) {
      \Drupal::messenger()->addError(t('This digest cannot be sent.'));
      return;
    }

    // @todo refactor CiviMail service for delegation.
    $params = $this->getMailingParams($digest_id);
    $mailingResult = $this->sendMailing($params);
    if ($mailingResult['is_error'] === 0) {
      $this->updateDigest($digest_id, CiviMailDigestInterface::STATUS_SENT, $mailingResult['id']);
      $this->insertDigestSentGroups($digest_id, $params['groups']['include']);
    }
    else {
      $this->updateDigestStatus($digest_id, CiviMailDigestInterface::STATUS_FAILED);
    }
  }

  /**
   * Checks if a digest can be sent.
   *
   * Verifies if the digest has content and if it has not been sent yet.
   * Only the prepared and failed status are allowing a send operation.
   *
   * @param int $digest_id
   *   Digest id.
   *
   * @return bool
   *   Can the digest be sent.
   */
  private function canSend($digest_id) {
    $query = $this->database->select('civimail_digest', 'cd');
    $query->condition('cd.id', $digest_id);
    $query->fields('cd', ['status']);
    $status = (int) $query->execute()->fetchField();
    return $status === CiviMailDigestInterface::STATUS_PREPARED ||  $status === CiviMailDigestInterface::STATUS_FAILED;
  }

  /**
   * Get CiviMail parameters for CiviCRM Mailing entity save.
   *
   * @param int $digest_id
   *   Digest id.
   *
   * @return array
   *   CiviCRM Mailing parameters.
   */
  public function getMailingParams($digest_id) {
    /** @var \Drupal\civimail\CiviMailInterface $civiMail */
    $civiMail = \Drupal::service('civimail');
    $fromCid = $this->digestConfig->get('from_contact');
    $fromContact = $civiMail->getContact(['contact_id' => $fromCid]);
    $text = $this->getMailingTemplateText($digest_id);
    $renderedText = \Drupal::service('renderer')->renderRoot($text);
    $html = $this->getMailingTemplateHtml($digest_id);
    $renderedHtml = \Drupal::service('renderer')->renderRoot($html);
    $subject = Unicode::truncate($this->getDigestTitle() . ' #' . $digest_id, 128, TRUE, TRUE);
    $result = [
      'subject' => $subject,
      // @todo get header and footer / get template from the bundle config
      'header_id' => '',
      'footer_id' => '',
      'body_text' => $renderedText,
      'body_html' => $renderedHtml,
      'name' => $subject,
      'created_id' => $fromContact['contact_id'],
      // @todo Sent by
      'from_name'  => $fromContact['display_name'],
      'from_email' => $fromContact['email'],
      'replyto_email'  => $fromContact['email'],
      // CiviMail removes duplicate contacts among groups.
      'groups' => [
        'include' => $this->getDestinationGroups(),
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
   * Get destination groups from the configuration.
   *
   * @return array
   *   List of group ids.
   */
  private function getDestinationGroups() {
    $groups = $this->digestConfig->get('to_groups');
    $result = [];
    // Get rid of the keys.
    foreach ($groups as $group) {
      $result[] = $group;
    }
    return $result;
  }

  /**
   * Returns the the mailing body as plain text wrapped in a mail template.
   *
   * @param int $digest_id
   *   Digest id.
   *
   * @return array
   *   Render array of the text mail template.
   */
  private function getMailingTemplateText($digest_id) {
    // @todo implement
    // Minimal implementation returns the title and url.
    return [
      '#theme' => 'civimail_digest_text',
      '#digest_title' => $this->getDigestTitle(),
      '#absolute_url' => $this->getAbsoluteDigestUrl($digest_id),
    ];
  }

  /**
   * Returns the markup for the mailing body wrapped in a mail template.
   *
   * @param int $digest_id
   *   Digest id.
   *
   * @return array
   *   Render array of the html mail template.
   */
  private function getMailingTemplateHtml($digest_id) {
    // @todo refactor with viewDigest()
    $entityIds = $this->getDigestContent($digest_id);
    $digest = [];
    if (!empty($entityIds)) {
      $entities = $this->getDigestEntities($entityIds);
      $digest = $this->buildDigest($entities, $digest_id);
    }
    return $digest;
  }

  /**
   * Schedules and sends a CiviCRM mailing.
   *
   * As a side effect, displays the status and the send immediately link.
   *
   * @param array $params
   *   The mailing parameters.
   *
   * @return array
   *   The mailing result.
   */
  private function sendMailing(array $params) {
    $mailingResult = $this->civicrmToolsApi->save('Mailing', $params);
    if ($mailingResult['is_error'] === 0) {
      $executeJobUrl = Url::fromUserInput('/civicrm/admin/runjobs?reset=1',
        ['attributes' => ['target' => '_blank']]);
      $executeJobLink = Link::fromTextAndUrl(t('Send immediately'), $executeJobUrl);
      $executeJobLink = $executeJobLink->toRenderable();
      $executeJobLink = render($executeJobLink);
      $message = t('CiviMail mailing for <em>@subject</em> scheduled. @execute_job_link.',
        [
          '@subject' => $params['subject'],
          '@execute_job_link' => $executeJobLink,
        ]
      );
      \Drupal::messenger()->addStatus($message);
    }
    else {
      // @todo get exception result
      \Drupal::messenger()->addError(t('Error while sending the CiviMail digest mailing.'));
    }
    return $mailingResult;
  }

  /**
   * {@inheritdoc}
   */
  public function getDigestStatusLabel($status_id) {
    $result = t('Unknown status');
    switch ($status_id) {
      case CiviMailDigestInterface::STATUS_CREATED:
        $result = t('Created');
        break;

      case CiviMailDigestInterface::STATUS_PREPARED:
        $result = t('Prepared');
        break;

      case CiviMailDigestInterface::STATUS_SENT:
        $result = t('Sent');
        break;

      case CiviMailDigestInterface::STATUS_FAILED:
        $result = t('Failed');
        break;
    }
    return $result;
  }

}
