<?php

namespace Drupal\civimail\Controller;

use Drupal\civimail\Form\EntitySendForm;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class NodeController.
 */
class NodeSendController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Datetime\DateFormatter definition.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Drupal\Core\Entity\EntityStorageInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new NodeMessageController object.
   */
  public function __construct(EntityTypeManager $entity_type_manager, DateFormatter $date_formatter) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * Builds a table header.
   *
   * @return array
   *   Header.
   */
  private function buildHeader() {
    $header = [
      'mailing_id' => [
        'data' => $this->t('Mailing Id'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'subject' => [
        'data' => $this->t('Subject'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'from' => [
        'data' => $this->t('From'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'groups' => [
        'data' => $this->t('Groups'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'created' => [
        'data' => $this->t('Created'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];
    return $header;
  }

  /**
   * Builds a table row.
   *
   * @param array $mailing
   *   CiviCRM mailing details.
   *
   * @return array
   *   Array mapped to header.
   */
  private function buildRow(array $mailing) {
    $dateTime = DrupalDateTime::createFromFormat(
      'Y-m-d H:i:s',
      $mailing['mailing']['created_date']
    );
    $timeStamp = $dateTime->getTimestamp();
    $groupLabels = [];
    /** @var \Drupal\civicrm_tools\CiviCrmGroupInterface $groupApi */
    $groupApi = \Drupal::service('civicrm_tools.group');
    foreach ($mailing['groups'] as $groupId) {
      $group = $groupApi->getGroup($groupId);
      if (!empty($group)) {
        $groupLabels[] = $group['title'];
      }
    }
    return [
      'mailing_id' => $mailing['mailing']['id'],
      'subject' => $mailing['mailing']['subject'],
    // @todo link with from_name
      'from' => $mailing['mailing']['from_email'],
      'groups' => implode(', ', $groupLabels),
      'created' => $this->dateFormatter->format($timeStamp, 'short'),
    ];
  }

  /**
   * Builds the mailing listing as a render array for table.html.twig.
   *
   * @param array $mailing_history
   *   List of CiviCRM mailings.
   *
   * @return array
   *   Table render array.
   */
  private function buildTable(array $mailing_history) {
    // @todo composition with entity list builder.
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->t('CiviCRM mailings'),
      '#rows' => [],
      // @todo empty should contain a call to action.
      '#empty' => $this->t('No mailing was sent yet for this content.'),
      // @todo
      // '#cache' => [
      // ],
    ];
    foreach ($mailing_history as $mailing) {
      if ($row = $this->buildRow($mailing)) {
        $build['table']['#rows'][] = $row;
      }
    }

    // @todo pagination
    // $build['pager'] = [
    // '#type' => 'pager',
    // ];
    return $build;
  }

  /**
   * Returns a mail preview link for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that is the subject of the preview.
   *
   * @return \Drupal\Core\Link
   *   The preview Link
   */
  private function getPreviewLink(EntityInterface $entity) {
    // @todo use ajax or entity_overlay
    $url = Url::fromRoute('civimail.mail_preview', [
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
    ], ['attributes' => ['target' => '_blank']]);
    $result = Link::fromTextAndUrl($this->t('Preview mail'), $url);
    return $result;
  }

  /**
   * Gets sent mailings per group.
   *
   * @param int $node
   *   Node entity id.
   *
   * @return array
   *   Render array of sent messages and notify groups form.
   */
  public function mailing($node) {
    $build = [];
    try {
      /** @var \Drupal\node\Entity\Node $nodeEntity */
      $nodeEntity = $this->entityTypeManager->getStorage('node')->load($node);
      if (!$nodeEntity->isPublished()) {
        $this->messenger()->addWarning($this->t('This content is currently unpublished.'));
      }

      // Check if the site is multilingual.
      // If so, set the current node to the current interface language
      // when it has a translation.
      $languageManager = \Drupal::languageManager();
      if ($languageManager->isMultilingual()) {
        $languageId = $languageManager->getCurrentLanguage()->getId();
        if ($nodeEntity->hasTranslation($languageId)) {
          $nodeEntity = $nodeEntity->getTranslation($languageId);
        }
      }

      $civiMail = \Drupal::service('civimail');
      $mailingHistory = $civiMail->getEntityMailingHistory($nodeEntity);
      // @todo set render keys
      $build = [
        '#theme' => 'entity_mailing',
        '#entity' => $nodeEntity,
        '#preview_link' => $this->getPreviewLink($nodeEntity),
        '#entity_send_form' => \Drupal::formBuilder()->getForm(EntitySendForm::class, $nodeEntity->bundle()),
        '#sent_mailings' => $this->buildTable($mailingHistory),
      ];
    }
    catch (InvalidPluginDefinitionException $exception) {
      $this->messenger->addError($exception);
    }
    return $build;
  }

}
