<?php

namespace Drupal\competition\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\file\Entity\File;
use Drupal\user\UserInterface;
use Drupal\competition\CompetitionEntryInterface;
use Drupal\competition\CompetitionJudgingSetup;

/**
 * Defines the Competition entry entity.
 *
 * @ingroup competition
 *
 * @ContentEntityType(
 *   id = "competition_entry",
 *   label = @Translation("Competition entry"),
 *   label_plural = @Translation("Competition entries"),
 *   bundle_label = @Translation("Competition"),
 *   handlers = {
 *     "storage" = "Drupal\competition\CompetitionEntryStorage",
 *     "storage_schema" = "Drupal\competition\CompetitionEntryStorageSchema",
 *     "access" = "Drupal\competition\CompetitionEntryAccessControlHandler",
 *     "views_data" = "Drupal\competition\Entity\CompetitionEntryViewsData",
 *     "view_builder" = "Drupal\competition\CompetitionEntryViewBuilder",
 *     "list_builder" = "Drupal\competition\CompetitionEntryListBuilder",
 *     "form" = {
 *       "default" = "Drupal\competition\Form\CompetitionEntryForm",
 *       "add" = "Drupal\competition\Form\CompetitionEntryForm",
 *       "reenter" = "Drupal\competition\Form\CompetitionEntryForm",
 *       "edit" = "Drupal\competition\Form\CompetitionEntryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     }
 *   },
 *   base_table = "competition_entry",
 *   admin_permission = "administer competition entries",
 *   entity_keys = {
 *     "id" = "ceid",
 *     "bundle" = "type",
 *     "label" = "ceid",
 *     "uid" = "uid",
 *     "status" = "status",
 *     "uuid" = "uuid"
 *   },
 *   bundle_entity_type = "competition",
 *   field_ui_base_route = "entity.competition.edit_form",
 *   common_reference_target = TRUE,
 *   constraints = {
 *     "CompetitionEntry" = {}
 *   },
 *   links = {
 *     "collection" = "/admin/content/competition",
 *     "canonical" = "/entry/{competition_entry}",
 *     "add-form" = "/competition/{competition}/enter",
 *     "reenter-form" = "/competition/{competition}/reenter",
 *     "edit-form" = "/entry/{competition_entry}/edit",
 *     "delete-form" = "/entry/{competition_entry}/delete"
 *   }
 * )
 */
class CompetitionEntry extends ContentEntityBase implements CompetitionEntryInterface {

  use EntityChangedTrait;
  use CompetitionEntryJudgingTrait;

  /**
   * The Competition entry's Competition cycle.
   *
   * @var string
   */
  protected $cycle;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $data = $this->getData();
    if (!empty($data['judging']['rounds'])) {

      // Add record for each round to index table.
      $db = \Drupal::service('database');
      foreach ($data['judging']['rounds'] as $round_num => $round) {

        $db->delete(CompetitionJudgingSetup::INDEX_TABLE)
          ->condition('ceid', $this->id(), '=')
          ->condition('scores_round', $round_num, '=')
          ->execute();

        if (isset($round['votes'])) {

          // Voting round.
          $db->insert(CompetitionJudgingSetup::INDEX_TABLE)
            ->fields([
              'ceid',
              'scores_round',
              'scores_finalized',
              'scores_computed',
              'votes',
            ])
            ->values([
              $this->id(),
              $round_num,
              1,
              1,
              $round['votes'],
            ])
            ->execute();

        }
        elseif (!empty($round['scores'])) {

          // Scored round (pass/fail or criteria)
          $scores_finalized = 0;
          foreach ($round['scores'] as $score) {
            if ($score->finalized) {
              $scores_finalized++;
            }
          }

          $average_score = $this->getAverageScore($round_num);

          $db->insert(CompetitionJudgingSetup::INDEX_TABLE)
            ->fields([
              'ceid',
              'scores_round',
              'scores_finalized',
              'scores_computed',
            ])
            ->values([
              $this->id(),
              $round_num,
              $scores_finalized,
              $average_score,
            ])
            ->execute();

        }

      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['ceid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Competition entry.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The Competition type/bundle.'))
      ->setSetting('target_type', 'competition')
      ->setRequired(TRUE);

    $fields['cycle'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Cycle'))
      ->setSettings([
        'allowed_values' => \Drupal::configFactory()
          ->get('competition.settings')
          ->get('cycles'),
      ])
      ->setDefaultValue("2015")
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -100,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setSettings([
        'allowed_values' => \Drupal::configFactory()
          ->get('competition.settings')
          ->get('statuses'),
      ])
      ->setDefaultValue(CompetitionEntryInterface::STATUS_CREATED)
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -100,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Entry owner'))
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => 0,
        'settings' => [
          'link' => TRUE,
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -90,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('A weight value for custom sorting, to be assigned by admins.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'weight' => -80,
      ]);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Competition entry.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['ceid_referrer'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Referrer ID'))
      ->setDescription(t('Indicates if the Competition entry is a referral triggered by another entry.'));

    $fields['data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Data'))
      ->setDescription(t('Storage data'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getCompetition() {
    return \Drupal::entityTypeManager()
      ->getStorage($this->getEntityType()->getBundleEntityType())
      ->load($this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getCycle() {
    return $this->get('cycle')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * Retrieve the keys/labels for all configured statuses.
   *
   * @return array
   *   Keys are integers; labels are translated strings.
   *
   * @see CompetitionSettingsForm::submitForm()
   *
   * TODO: inject string translation service and use StringTranslationTrait?
   * t() is not already a method.
   */
  public static function getStatusLabels() {
    // These can be customized on the competition settings admin form.
    // However, the four defined by and used in this module's code - 'Created'
    // 'Updated', 'Finalized', 'Archived' - are ensured to be included with the
    // correct integer keys via the submit handler.
    $statuses = \Drupal::configFactory()
      ->get('competition.settings')
      ->get('statuses');

    $return = [];

    foreach ($statuses as $key => $label) {
      // Escaping is an extra precaution - labels are already sanitized on
      // settings form submit.
      $return[$key] = (string) Html::Escape($label);
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   *
   * TODO: inject string translation service and use StringTranslationTrait?
   * t() is not already a method.
   */
  public function getStatusLabel() {
    $label_status = t('Created');

    switch ($this->getStatus()) {
      case CompetitionEntryInterface::STATUS_UPDATED:
        $label_status = t('Updated');
        break;

      case CompetitionEntryInterface::STATUS_FINALIZED:
        $label_status = t('Finalized');
        break;

      case CompetitionEntryInterface::STATUS_ARCHIVED:
        $label_status = t('Archived');
        break;
    }

    return $label_status;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return unserialize($this->get('data')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data) {
    $this->set('data', serialize($data));
    return $this;
  }

  /**
   * Get data blob and alter it as desired for storing into reporting table.
   *
   * @see CompetitionReporter::flattenEntities()
   *
   * TODO: is this a good way to do this?
   * - all other field values are adjusted by field type or name directly in
   *   CompetitionReporter::flattenEntities()
   * - could use existing alter hook in flattenEntities() (on entire entry,
   *   before pulling its field values), implement it by competition module
   *   and call this
   * - could do a custom alter hook in there, but that's pretty one-off
   * - would event subscriber be appropriate? (maybe not, if we're altering)
   */
  public function getDataReportingAltered() {

    $data = $this->getData();

    // TODO: splitting out judging data alteration seems right - but how is
    // best to check if that's relevant?
    if (!empty($data['judging'])) {
      $this->dataReportingAlterJudging($data);
    }

    return $data;

  }

  /**
   * {@inheritdoc}
   */
  public function getTempData() {
    $data = \Drupal::service('user.data')
      ->get('competition', $this->getOwnerId(), 'entry');

    if (!empty($data)) {
      // 2017-02-05, Tory:
      // Previously, this data was keyed only by the entry's cycle. To support
      // multiple entries per user and multiple simultaneous competitions, use
      // entry ID going forward.
      $key = 'id:' . $this->id();

      // After this code change, temp data is always saved with entry ID as key;
      // check cycle key as fallback, to pull data that was saved before it.
      $key_old = $this->getCycle();

      if (isset($data[$key])) {
        $data = $data[$key];
      }
      elseif (isset($data[$key_old])) {
        $data = $data[$key_old];
      }
    }

    return (!empty($data) ? $data : FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function setTempData(array $input) {
    $user_data_service = \Drupal::service('user.data');

    // 2017-02-05, Tory:
    // Note that entry's cycle was previously used as this key. To support
    // multiple entries per user and multiple simultaneous competitions, use
    // entry ID going forward.
    // @see ::getTempData()
    $key = 'id:' . $this->id();

    $temp = [
      $key => [],
    ];

    if (empty($input)) {
      $user_data_service
        ->set('competition', $this->getOwnerId(), 'entry', $temp);

      return $this;
    }

    foreach ($input as $field_name => $data) {
      if (stripos($field_name, 'field_') !== 0) {
        continue;
      }

      $parents = [$field_name];
      $value = $input[$field_name];

      while (is_array($value)) {
        $value = NestedArray::getValue($input, $parents);

        if (is_array($value)) {
          if (count($value) == 1) {
            // If only one element in array, this should be nesting - collect
            // the key to continue traveling down.
            reset($value);
            $parents[] = key($value);
          }
          else {
            $value = array_filter($value);
            break;
          }
        }
      }

      // Remove the field name from parents, as we'll be working within each
      // field's widget when re-populating.
      array_shift($parents);

      // If this field value is an uploaded file, set file status to permanent
      // so Drupal will not automatically delete it during cleanup.
      if (!empty($value['fids'])) {
        // ('fids' is not an array at this point.)
        $fid = $value['fids'];
        if (is_numeric($value['fids'])) {
          if ($file = File::load((int) $fid)) {
            $file->setPermanent();
            $file->save();
            $file_usage = \Drupal::service('file.usage');
            $file_usage->add($file, 'competition', 'competition_entry', $this->id());
          }
        }
      }

      $temp[$key][$field_name] = [
        'input' => $value,
        'parents' => $parents,
      ];
    }

    // Set temp entry data to be copied on to entity on full submission.
    $user_data_service
      ->set('competition', $this->getOwnerId(), 'entry', $temp);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setReferrerEntry(CompetitionEntryInterface $entry) {
    $this->set('ceid_referrer', $entry->id());
    return $this;
  }

  /**
   * Send submission confirmation email.
   *
   * Utility method to send an email confirming entry submission. This should
   * be called when status changes to Finalized.
   *
   * Email will ONLY be sent if the "Entry confirmation email" text set on the
   * competition is non-empty.
   *
   * @see competition_mail()
   * @see CompetitionEntryForm::save()
   *
   * TODO: does this method make sense on the entity class? It requires a
   * service; is there a practice to inject service to an entity?
   */
  public function sendSubmissionConfirmEmailIfConfigured() {

    /** @var \Drupal\competition\CompetitionInterface $competition */
    $competition = $this->getCompetition();

    // Check competition entity - is email confirmation text non-empty?
    $longtext = $competition->getLongtext();
    if (empty($longtext->confirmation_email)) {
      return;
    }

    /** @var \Drupal\Core\Mail\MailManager $mailManager */
    $mailManager = \Drupal::service('plugin.manager.mail');

    $owner = $this->getOwner();

    $module = 'competition';
    $key = 'competition_entry_submit_confirm';

    $to = $owner->getEmail();
    $langcode = $owner->getPreferredLangcode();

    // Collect params needed by competition_mail().
    $params = [
      'entry_id' => $this->id(),
      'competition_title' => $competition->getLabel(),
      'body_text' => $longtext->confirmation_email,
    ];

    $send = TRUE;
    $message = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);

    if ($message['result'] !== TRUE) {
      // Log error for admins.
      \Drupal::logger('competition')->error(t("Error attempting to send entry submission confirmation email:<br/><br/>
        Recipient email address(es): %to<br/>
        User ID: %uid<br/>
        Entry ID: %ceid",
        [
          '%to' => $message['to'],
          '%uid' => $owner->id(),
          '%ceid' => $this->id(),
        ]
      ));
    }
  }

}
