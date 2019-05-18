<?php

namespace Drupal\competition\Entity;

use Drupal\competition\CompetitionEntryInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\competition\CompetitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the Competition entity.
 *
 * @ConfigEntityType(
 *   id = "competition",
 *   label = @Translation("Competition"),
 *   label_plural = @Translation("Competitions"),
 *   handlers = {
 *     "list_builder" = "Drupal\competition\CompetitionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\competition\Form\CompetitionForm",
 *       "edit" = "Drupal\competition\Form\CompetitionForm",
 *       "delete" = "Drupal\competition\Form\CompetitionDeleteForm"
 *     }
 *   },
 *   config_prefix = "type",
 *   bundle_of = "competition_entry",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "label",
 *     "status" = "status",
 *     "uuid" = "uuid"
 *   },
 *   admin_permission = "administer competitions",
 *   links = {
 *     "canonical" = "/competition/{competition}/enter",
 *     "add-form" = "/admin/structure/competition/add",
 *     "edit-form" = "/admin/structure/competition/{competition}/edit",
 *     "delete-form" = "/admin/structure/competition/{competition}/delete",
 *     "collection" = "/admin/structure/competition"
 *   }
 * )
 */
class Competition extends ConfigEntityBase implements CompetitionInterface {

  use StringTranslationTrait;

  /**
   * The Competition type (machine name).
   *
   * @var string
   */
  protected $type;

  /**
   * The Competition label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Competition active cycle.
   *
   * @var string
   */
  protected $cycle;

  /**
   * The Competition archived cycles.
   *
   * @var string
   */
  protected $cycles_archived;

  /**
   * The Competition status.
   *
   * @var int
   */
  protected $staus = CompetitionInterface::STATUS_OPEN;

  /**
   * The Competition entry limits.
   *
   * @var array
   */
  protected $entry_limit;

  /**
   * The Competition long descriptions.
   *
   * @var array
   */
  protected $longtext;

  /**
   * The Competition judging settings.
   *
   * @var array
   */
  protected $judging;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusLabel() {
    $label_status = $this->t('Open');
    switch ($this->getStatus()) {
      case CompetitionInterface::STATUS_CLOSED:
        $label_status = $this->t('Closed');
        break;
    }

    return $label_status;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getCycle() {
    $cycle_default = date('Y');

    return (!empty($this->cycle) ? $this->cycle : $cycle_default);
  }

  /**
   * {@inheritdoc}
   */
  public function getCycleLabel() {
    $cycles = \Drupal::configFactory()
      ->get('competition.settings')
      ->get('cycles');

    $label = $cycles[$this->getCycle()];

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getCyclesArchived() {
    $cycle_default = [];

    return (!empty($this->cycles_archived) ? unserialize($this->cycles_archived) : $cycle_default);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntryLimits() {
    return (object) $this->entry_limit;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntryLimits(array $limits) {
    $this->entry_limit = $limits;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLongtext() {
    return (object) $this->longtext;
  }

  /**
   * {@inheritdoc}
   */
  public function setLongtext(array $longtext) {
    $this->longtext = $longtext;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getJudging() {
    return (object) $this->judging;
  }

  /**
   * {@inheritdoc}
   */
  public function setJudging(array $judging) {
    $this->judging = $judging;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getJudgingQueueLabel($queue) {

    $queues = \Drupal::configFactory()
      ->get('competition.settings')
      ->get('queues');

    $label = $queues[$queue];

    return $label;

  }

  /**
   * Checks if the Competition has any judging data for the current cycle.
   *
   * @return bool
   *   Does this entry have judging data.
   */
  public function hasJudgingData() {

    $query = $this->entityTypeManager()
      ->getStorage('competition_entry')
      ->getQuery()
      ->condition('type', $this->id())
      ->condition('cycle', $this->getCycle())
      ->condition('status', CompetitionEntryInterface::STATUS_FINALIZED);

    $entries = $query->execute();
    $index = [];
    if (!empty($entries)) {
      $index = \Drupal::database()
        ->select('competition_entry_index', 'cei')
        ->fields('cei', ['ceid'])
        ->condition('cei.ceid', $entries, 'IN')
        ->execute()
        ->fetchCol();
    }

    return (count($index) !== 0);
  }

}
