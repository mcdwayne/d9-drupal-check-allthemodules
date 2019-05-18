<?php

namespace Drupal\competition;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\competition\Entity\CompetitionEntry;
use Psr\Log\LoggerInterface;

/**
 * Competition entry/user data reporting.
 */
class CompetitionReporter {

  use StringTranslationTrait;

  const REPORT_TABLE = 'competition_entry_report_data';

  const REPORT_JUDGING_TABLE = 'competition_entry_report_judging_data';

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $dbConnection;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The entity field service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  private $entityFieldManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * The entity storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $entityFormDisplayStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private $dateFormatter;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  private $state;

  /**
   * The competition judging service.
   *
   * @var \Drupal\competition\CompetitionJudgingSetup
   */
  private $competitionJudgingSetup;

  /**
   * Config stored in 'competition.settings'.
   *
   * Edited at admin/structure/competition/settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $competitionSettings;

  /**
   * Entry status labels.
   *
   * @var array
   */
  private $entryStatusLabels;

  /**
   * Local cache of field definitions on competition_entry types.
   *
   * Each key is an entry type (bundle); each value is an array of
   *   \Drupal\Core\Field\FieldDefinitionInterface.
   *
   * @var array
   */
  private $fieldDefinitionsEntry;

  /**
   * Local cache of field definitions on user entity type.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  private $fieldDefinitionsUser;

  /**
   * Names of competition_entry fields to exclude from report.
   *
   * Note: this is not currently per bundle.
   *
   * @var array
   */
  private $fieldsExcludeEntry;

  /**
   * Names of user entity fields to exclude from report.
   *
   * @var array
   */
  private $fieldsExcludeUser;

  /**
   * Local cache of field metadata and labels.
   *
   * Keyed by competition_entry type for entries, or by 'user' for user entity.
   *
   * @var array
   *
   * @see getEntityReportFieldsMeta()
   */
  private $fieldsMetaCache;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $db_connection
   *   The database service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config service.
   * @param \Drupal\competition\CompetitionJudgingSetup $competition_judging_setup
   *   The competition judging service.
   */
  public function __construct(Connection $db_connection, EntityTypeManagerInterface $entity_type_manager, EntityFieldManager $entity_field_manager, ModuleHandlerInterface $moduleHandler, DateFormatterInterface $date_formatter, LoggerInterface $logger, StateInterface $state, ConfigFactoryInterface $config_factory, CompetitionJudgingSetup $competition_judging_setup) {

    $this->dbConnection = $db_connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->moduleHandler = $moduleHandler;
    $this->entityFormDisplayStorage = $entity_type_manager->getStorage('entity_form_display');
    $this->dateFormatter = $date_formatter;
    $this->logger = $logger;
    $this->state = $state;
    $this->competitionJudgingSetup = $competition_judging_setup;
    $this->competitionSettings = $config_factory->get('competition.settings');

    // Set default fields to exclude.
    // Note: Do not exclude `data` serialized blob here - we do need it to be
    // included in the flattened report data table. It will be handled
    // separately for export to report CSV.
    $this->fieldsExcludeEntry = [
      'uuid',

      // Weight is for sorting archived entries; not really relevant to
      // larger-context reports.
      'weight',
    ];

    $this->fieldsExcludeUser = [
      // User ID is already included via the entry owner.
      'uuid',
      'langcode',
      'preferred_langcode',
      'preferred_admin_langcode',
      'pass',
      'timezone',
      'status',
      'init',
      'roles',
      'default_langcode',
    ];

  }

  /**
   * Get report data last updated.
   *
   * Get timestamp at which report data was last updated. This matches the most
   * recent timestamp in `exported` column in report data table.
   *
   * @return int|null
   *   The UNIX timestamp value, or NULL if no recorded timestamp in the current
   *   environment
   *
   * @see self::updateAllReportData()
   */
  public function getReportDataLastUpdated() {
    // If no value stored for this key, get() returns FALSE.
    return $this->state->get('competition.report_data_last_updated') ?: NULL;
  }

  /**
   * Set report data last updated.
   *
   * Set timestamp at which report data was last updated. This should always
   * be set to timestamp stored in `exported` column of the report table, when
   * data is updated there.
   *
   * @param int $timestamp
   *   The UNIX timestamp value to store.
   *
   * @see self::updateAllReportData()
   */
  protected function setReportDataLastUpdated($timestamp) {
    $this->state->set('competition.report_data_last_updated', $timestamp);
  }

  /**
   * Get report table columns.
   *
   * Get array of the column names in the custom database table in which we
   * store report data.
   */
  private function getReportTableColumns() {
    return [
      'type',
      'cycle',
      'ceid',
      'uid',
      'status',
      'exported',
      'data',
    ];
  }

  /**
   * Retrieve human labels for competition_entry.status field values.
   *
   * This is a local cache around CompetitionEntry::getStatusLabels().
   *
   * @return array
   *   integer value => human label
   */
  private function getEntryStatusLabels() {

    if (empty($this->entryStatusLabels)) {
      $this->entryStatusLabels = CompetitionEntry::getStatusLabels();
    }

    return $this->entryStatusLabels;

  }

  /**
   * Retrieve array of all field definitions for given competition_entry type.
   *
   * @param string $bundle
   *   Competition entry bundle/type.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   Field definitions.
   */
  public function getFieldDefinitionsEntry($bundle) {

    if (!isset($this->fieldDefinitionsEntry[$bundle])) {
      $this->fieldDefinitionsEntry[$bundle] = $this->entityFieldManager->getFieldDefinitions('competition_entry', $bundle);
    }

    return $this->fieldDefinitionsEntry[$bundle];

  }

  /**
   * Retrieve array of all field definitions for user entity type.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   Field definitions.
   */
  public function getFieldDefinitionsUser() {

    if (empty($this->fieldDefinitionsUser)) {
      $this->fieldDefinitionsUser = $this->entityFieldManager->getFieldDefinitions('user', 'user');
    }

    return $this->fieldDefinitionsUser;

  }

  /**
   * Retrieve array of competition_entry field names to be excluded from report.
   *
   * Note: this is not currently per bundle.
   *
   * @return array
   *   Fields.
   */
  public function getFieldsExcludeEntry() {
    return $this->fieldsExcludeEntry;
  }

  /**
   * Set the competition_entry field names to be excluded from report.
   *
   * This fully overrides the defaults.
   *
   * @param array $field_names
   *   Field names.
   */
  public function setFieldsExcludeEntry(array $field_names) {
    $this->fieldsExcludeEntry = $field_names;
  }

  /**
   * Add a competition_entry field name to be excluded from report.
   *
   * This adds to the defaults.
   *
   * @param string $field_name
   *   Field name.
   */
  public function addFieldExcludeEntry($field_name) {
    if (!in_array($field_name, $this->fieldsExcludeEntry)) {
      $this->fieldsExcludeEntry[] = $field_name;
    }
  }

  /**
   * Retrieve array of user entity field names to be excluded from report.
   *
   * @return array
   *   Non user fields.
   */
  public function getFieldsExcludeUser() {
    return $this->fieldsExcludeUser;
  }

  /**
   * Set the user entity field names to be excluded from report.
   *
   * This fully overrides the defaults.
   *
   * @param array $field_names
   *   Field names.
   */
  public function setFieldsExcludeUser(array $field_names) {
    $this->fieldsExcludeUser = $field_names;
  }

  /**
   * Add a user entity field name to be excluded from report.
   *
   * This adds to the defaults.
   *
   * @param string $field_name
   *   Field name.
   */
  public function addFieldExcludeUser($field_name) {
    if (!in_array($field_name, $this->fieldsExcludeUser)) {
      $this->fieldsExcludeUser[] = $field_name;
    }
  }

  /**
   * Get fields sorted by form display.
   *
   * Retrieve field names on an entity, sorted by their weighted order in a
   * form display config.
   *
   * Note! This will NOT include fields set to 'hidden' on that mode.
   *
   * @param string $form_display_id
   *   The ID of the entity form display - "$entity_type.$bundle.$display_mode".
   *
   * @return array|null
   *   Indexed array of field names, or NULL if form display config entity
   *   could not be loaded for given ID.
   *
   *   TODO: this method does not fully work if there are field_groups
   *   configured, as weights only order *within* each group. This can be fixed
   *   manually by editing weight values directly rather than via drag-and-drop.
   *   (This is all moot if we provide a way to config field choice + order.)
   */
  public function getFieldsSortedByFormDisplay($form_display_id) {

    $display = $this->entityFormDisplayStorage->load($form_display_id);

    if (empty($display)) {
      return NULL;
    }

    $ids = explode('.', $form_display_id);
    $entity_type = $ids[0];
    $bundle = $ids[1];

    if ($entity_type == 'competition_entry') {
      $field_definitions = $this->getFieldDefinitionsEntry($bundle);
    }
    elseif ($entity_type == 'user') {
      $field_definitions = $this->getFieldDefinitionsUser();
    }

    $fields_sorted = [];

    foreach ($display->getComponents() as $field_name => $meta) {
      $weight = (int) $meta['weight'];

      // 'account' widget includes username, email, and password fields.
      if ($entity_type == 'user' && $field_name == 'account') {
        // Drag-and-drop ordering on admin UI always results in integer weight
        // values. Use floats to split up this multi-field widget.
        $fields_sorted[$weight] = 'name';
        $fields_sorted[$weight + 0.5] = 'mail';

        continue;
      }

      // Check for definition because the *display* can include "extra" fields
      // that aren't base or configured fields. ('account' is one, but that's
      // handled specially.)
      if (empty($field_definitions[$field_name])) {
        continue;
      }

      $fields_sorted[$weight] = $field_name;
    }

    // Sort by the keys.
    ksort($fields_sorted);

    // We only need the values.
    $fields_sorted = array_values($fields_sorted);

    return $fields_sorted;
  }

  /**
   * Collect field metadata and labels for all fields to be included in report.
   *
   * @param string $entity_type
   *   Entity type - 'competition_entry' or 'user' - for which to retrieve
   *   field metadata.
   * @param string|null $bundle
   *   Type (bundle) name of competition_entry for which to retrieve field
   *   metadata. Leave NULL for user entity type.
   *
   * @return array
   *   Contains two arrays of field data. Both are ordered for output to CSV.
   *   'fields_meta' - field key => metadata
   *     field key is:
   *     {field_name}, for entry fields
   *     user.{field_name}, for user fields
   *   'field_labels_all' - key => finalized CSV column label
   *      key is:
   *      {field_name} OR {field_name}.{property_name}, for entry fields
   *      user.{field_name} OR user.{field_name}.{property_name},
   *      for user fields.
   */
  public function getEntityReportFieldsMeta($entity_type, $bundle = NULL) {

    // Check local cache and return if already loaded for this type.
    $cache_key = (!empty($bundle) ? $entity_type . '.' . $bundle : $entity_type);
    if (!empty($this->fieldsMetaCache[$cache_key])) {
      return $this->fieldsMetaCache[$cache_key];
    }
    // -------------------------------------------------------------.
    // Collect all field keys in sensible order for output.
    $keys_ordered = [];

    // --- 1. Entry fields - primary identifiers.
    if ($entity_type == 'competition_entry') {
      // Put these fields first, as categories/identification/meta
      // for the entry.
      // These are base fields on the entity type, so can be hardcoded.
      $fields_entry_primary = [
        'type',
        'cycle',
        'ceid',
        'status',
        'created',
        'changed',
        'uid',
      ];

      // TODO: include ceid_referrer here, if referrals are enabled (not
      // implemented yet?)
      // $competition = $this->entityTypeManager
      // ->getStorage('competition')->load($bundle);
      $keys_ordered = array_merge($keys_ordered, $fields_entry_primary);
    }

    // --- 2. User fields
    // Show user fields next, as registration form is the first step and
    // generally contains personal info.
    $fields_user_all = [];

    /* @var \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions_user */
    $field_definitions_user = $this->getFieldDefinitionsUser();

    // Primary identifying fields (hardcoded as they are base fields)
    $fields_user_primary = [
      'uid',
      'name',
      'mail',
    ];
    $fields_user_all = array_merge($fields_user_all, $fields_user_primary);

    // Use form display to set a sensible field order.
    // Try 'register' display mode first; fallback to default.
    $fields_user_sorted = $this->getFieldsSortedByFormDisplay('user.user.register');
    if ($fields_user_sorted === NULL) {
      $fields_user_sorted = $this->getFieldsSortedByFormDisplay('user.user.default');
    }

    $fields_user_sorted = array_diff($fields_user_sorted, $fields_user_primary);
    $fields_user_all = array_merge($fields_user_all, $fields_user_sorted);

    // Add remaining fields: not primary, not displayed in the form.
    $fields_user_all = array_merge(
      $fields_user_all,
      array_diff(array_keys($field_definitions_user), $fields_user_all)
    );

    // Exclude certain fields.
    $fields_exclude_user = $this->getFieldsExcludeUser();
    // TODO: entry and user both have uid. Entry's label is prob preferable,
    // but if exporting a user account only, entry.uid won't exist...
    // custom combine labels maybe? "Entry Owner (User: UID)"
    // $fields_exclude_user[] = 'uid';.
    $fields_user_all = array_diff($fields_user_all, $fields_exclude_user);

    // Add to master list, prepending 'user.' to key to denote as fields on
    // user entity.
    foreach ($fields_user_all as $name) {
      $keys_ordered[] = 'user.' . $name;
    }

    // --- 3. Entry fields - non-primary.
    if ($entity_type == 'competition_entry') {
      $fields_entry_all = [];

      /* @var \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions_entry */
      $field_definitions_entry = $this->getFieldDefinitionsEntry($bundle);

      // Use entry form display to set a sensible field order.
      $fields_entry_sorted = $this->getFieldsSortedByFormDisplay('competition_entry.' . $bundle . '.default');
      $fields_entry_all = array_merge($fields_entry_all, $fields_entry_sorted);

      // Add remaining fields not displayed in form.
      $fields_entry_all = array_merge(
        $fields_entry_all,
        array_diff(array_keys($field_definitions_entry), $fields_entry_all)
      );

      // Remove primary fields that were added before user fields.
      $fields_entry_all = array_diff($fields_entry_all, $fields_entry_primary);

      // Exclude certain fields.
      $fields_exclude_entry = $this->getFieldsExcludeEntry();
      $fields_entry_all = array_diff($fields_entry_all, $fields_exclude_entry);

      // Add to master list.
      $keys_ordered = array_merge($keys_ordered, $fields_entry_all);
    }

    // --- 4. Collect metadata for all fields.
    $fields_meta = [];
    $labels_seen = [];
    $labels_same = [];

    foreach ($keys_ordered as $key) {

      $on_user = (strpos($key, 'user.') === 0);

      $name = ($on_user ? substr($key, 5) : $key);

      $definition = $on_user ? $field_definitions_user[$name] : $field_definitions_entry[$name];

      $field_type = $definition->getType();

      // TODO (minor): this doesn't really fit here, as it's one more kind of
      // filtering, but is easy to do here.
      if ($field_type == 'markup') {
        continue;
      }

      // Get field label; denote whether it's same as any others.
      $label = (string) $definition->getLabel();
      if ($on_user) {
        $label = $this->t('User: @label', [
          '@label' => $label,
        ]);
      }

      if (in_array($label, $labels_seen) && !in_array($label, $labels_same)) {
        $labels_same[] = $label;
      }
      $labels_seen[] = $label;

      // Get field properties - store if more than one, to be split out later.
      $property_names = $definition->getFieldStorageDefinition()->getPropertyNames();

      // Tweak properties per field type.
      switch ($field_type) {
        case 'entity_reference':
          // Note that entry 'type' field is actually an entity_reference to
          // competition entity.
          // TODO: this might be worth handling specially, to get some more
          // identifying value off the ref'd entity.
          $property_names = ['target_id'];
          break;

        case 'file':
          // FileItem is an extension of EntityReferenceItem field type and
          // has several properties. We won't pull field values via 'target_id'
          // property name directly, but having one property name is consistent
          // and accurate.
          $property_names = ['target_id'];
          break;

        case 'datetime':
          // There are both 'date' (a DrupalDateTime object) and 'value' (date
          // string) properties; displaying the string suffices. Also, the
          // 'date' property is not loaded in getValue().
          $property_names = ['value'];
          break;

        case 'link':
          // Remove 'options' - those have to do with external URL settings,
          // HTML attributes, etc.
          $property_names = array_diff($property_names, ['options']);
          // TODO: use this when 'properties' is assoc
          // unset($meta['properties']['options']);
          // If field is config'd so user cannot enter a title, there's no
          // property value to export.
          // @see \Drupal\link\Plugin\Field\FieldType\LinkItem::fieldSettingsForm()
          if ($definition->getSetting('title') === DRUPAL_DISABLED) {
            $property_names = array_diff($property_names, ['title']);
          }

          break;

        default:
          break;
      }

      $fields_meta[$key] = [
        'field_type' => $field_type,
        'label' => $label,
        'properties' => $property_names,
      ];
    }

    // --- 5. Collect labels.
    // Build array: key => final label.
    // - For multiple fields with same label, append field machine name for
    //   clarity
    // - Expand fields with multiple properties to separate columns
    //   per property.
    $field_labels_all = [];

    foreach ($fields_meta as $key => &$meta) {

      $on_user = (strpos($key, 'user.') === 0);
      $name = ($on_user ? substr($key, 5) : $key);

      $label = $meta['label'];
      if (in_array($label, $labels_same)) {
        $label .= (' (' . $name . ')');
      }

      if (count($meta['properties']) == 1) {
        // If only one property, use single column with just field label.
        $field_labels_all[$key] = $label;
      }
      else {
        // Otherwise, split each property value into its own column.
        // TODO: get property labels into here.
        foreach ($meta['properties'] as $property_name) {
          $field_labels_all[$key . '.' . $property_name] = $label . ' - ' . $property_name;
        }
      }

    }

    // --- 6. Allow altering fields meta and labels.
    // Modules, be careful!!!
    // TODO: document this hook.
    $alter_data = [
      'fields_meta' => &$fields_meta,
      'field_labels_all' => &$field_labels_all,
    ];
    // Unalterable stuff.
    $alter_context = [
      'entity_type' => $entity_type,
      'bundle' => $bundle,
    ];
    $this->moduleHandler->alter('competition_entry_report_fields_meta', $alter_data, $alter_context);

    // Store final data sets in local cache var.
    $this->fieldsMetaCache[$cache_key] = [
      'fields_meta' => $fields_meta,
      'field_labels_all' => $field_labels_all,
    ];

    return $this->fieldsMetaCache[$cache_key];
  }

  /**
   * Get list of columns in judging reporting data table.
   *
   * @return array
   *   Table column names, as defined in schema.
   */
  public function getReportJudgingTableColumns() {
    return [
      'type',
      'cycle',
      'ceid',
      'user_name',
      'round_id',
      'judge_name',
      'score',
      'score_finalized',
      'votes',
      'log',
      'exported',
    ];
  }

  /**
   * Get list of keys and labels of all available judging reports.
   *
   * TODO: consider YAML for this?
   *
   * @return array
   *   Array of available judging reports; keys are custom keys used to identify
   *   each report; values are human labels.
   */
  public function getAllJudgingReports() {

    $reports = [
      // Everything from the master report (entry/user entity fields), plus
      // all overall round scores (up to the given round)
      'master' => $this->t('Master with Round Scores'),

      // All score values - per judge, per entry.
      'scores' => $this->t('Score Details'),

      // Overall score per entry, by judge.
      'judge_scores' => $this->t('Scores by Judge'),

      // Number of completed scores, number of entries assigned - per judge.
      'scores_completed' => $this->t('Scores Completed per Judge'),

      // Number of votes in this round, per entry.
      'votes' => $this->t('Votes per Entry'),

      // Notes (log messages) - per entry.
      'log' => $this->t('Notes'),
    ];

    return $reports;

  }

  /**
   * Get list of keys of all available judging reports.
   *
   * @return array
   *   Array of the custom keys used to identify available judging reports.
   */
  public function getAllJudgingReportKeys() {

    return array_keys($this->getAllJudgingReports());

  }

  /**
   * Get list of keys and labels of judging reports that apply to a round.
   *
   * TODO: consider YAML for this - applicability per round type.
   *
   * @param string $competition_id
   *   The ID of competition with the given judging round.
   * @param int $round_id
   *   The judging round number.
   *
   * @return array
   *   Array of applicable judging reports, according to round type. (Keys are
   *   the custom keys that identify reports; values are human labels.)
   *
   * @see CompetitionReporter::getAllJudgingReports()
   *
   * @throws \InvalidArgumentException
   *   If there is no competition entity with the given ID, or if there is no
   *   judging round with the given number in that competition's configuration.
   */
  public function getJudgingReportsByRound($competition_id, $round_id) {
    /* @var \Drupal\competition\CompetitionInterface $competition */
    $competition = $this->entityTypeManager->getStorage('competition')->load($competition_id);

    if (empty($competition)) {
      throw new \InvalidArgumentException('Argument $competition_id must be the ID of a competition entity that exists.');
    }

    // Non-numeric strings cast to 0 (which cannot be a round ID).
    $round_id = (int) $round_id;

    $judging = $competition->getJudging();

    if (empty($judging->rounds) || empty($judging->rounds[$round_id])) {
      throw new \InvalidArgumentException('Argument $round_id must be the number (integer) of a configured judging round on the "' . $competition_id . '" competition.');
    }

    // Filter available reports by round type.
    $round_type = $judging->rounds[$round_id]['round_type'];

    $reports_all = $this->getAllJudgingReports();
    $reports = [];

    if ($round_type == 'voting') {
      $reports = array_intersect_key($reports_all, array_flip(['votes', 'log']));
    }
    else {
      $reports = array_diff_key($reports_all, array_flip(['votes']));
    }

    return $reports;

  }

  /**
   * Check if a given judging report applies to a round (based on round type).
   *
   * @param string $competition_id
   *   The ID of competition with the given judging round.
   * @param int $round_id
   *   The judging round number.
   * @param string $report
   *   Custom key denoting a particular judging report.
   *
   * @return bool
   *   TRUE if the report applies to this round, FALSE if not.
   *
   * @throws \InvalidArgumentException
   *   If $report is not the key of a defined judging report.
   *
   * @see CompetitionReporter::getJudgingReportsByRound()
   */
  public function judgingReportAppliesToRound($competition_id, $round_id, $report) {

    if (!is_string($report) || !in_array($report, $this->getAllJudgingReportKeys())) {
      throw new \InvalidArgumentException('Argument $report value "' . $report . '" is not the key of a defined judging report.');
    }

    // This method validates its arguments.
    $reports_applicable = $this->getJudgingReportsByRound($competition_id, $round_id);

    return (!empty($reports_applicable[$report]));

  }

  /**
   * Get column keys and labels for a given judging report.
   *
   * TODO: consider YAML for this?
   *
   * @param string $report
   *   Custom key denoting a particular judging report.
   * @param int $round_id
   *   The round ID for which to pull judging data for this report - for use
   *   in any column labels.
   *
   * @return array
   *   Array of columns for given report; keys are custom keys defining
   *   particular judging-related data; values are columns labels.
   *
   * @throws \InvalidArgumentException
   *   If $report is not one of the available judging reports.
   */
  public function getJudgingReportColumns($report, $round_id) {

    if (!in_array($report, $this->getAllJudgingReportKeys())) {
      throw new \InvalidArgumentException('Argument $report value "' . $report . '" is not a valid judging report.');
    }

    $cols = [];

    switch ($report) {

      case 'master':
        $cols = [
          // This is a placeholder, to be expanded to all user/entry fields.
          'entities' => $this->t('[All user and entry fields]'),
        ];

        // Add score columns for all rounds up to and including the given one.
        for ($r = 1; $r <= $round_id; $r++) {
          $cols['round.' . $r . '.display_average'] = $this->t('Round @round_id Score', [
            '@round_id' => $r,
          ]);
        }

        break;

      case 'scores':
        $cols = [
          'ceid' => $this->t('Entry ID'),
          // 'user.name' => $this->t('Username'),.
          'user_name' => $this->t('Username'),
          'round.display_total_weighted_points' => $this->t('Round @round_id Total', [
            '@round_id' => $round_id,
          ]),
          'round.display_average' => $this->t('Round @round_id Average', [
            '@round_id' => $round_id,
          ]),
          'judge_name' => $this->t('Judge'),
          'round.criterion_label' => $this->t('Criteria'),
          // $this->t('Score - Weighted Points'),.
          'criterion.display_weighted_points' => $this->t('Criteria Total'),
          // $this->t('Score - Percent'),.
          'criterion.display_percent' => $this->t('Criteria Percent'),
          // $this->t('Score - Displayed'),.
          'criterion.display_points' => $this->t('Criteria Displayed'),
          // 'score.display_finalized' => $this->t('Finalized'),.
          'score_finalized' => $this->t('Finalized'),
        ];

        break;

      case 'judge_scores':
        $cols = [
          'judge_name' => $this->t('Judge'),
          'ceid' => $this->t('Entry ID'),
          'score.weighted_points' => $this->t('Round @round_id Overall Score', [
            '@round_id' => $round_id,
          ]),
        ];

        break;

      case 'scores_completed':
        $cols = [
          'judge_name' => $this->t('Judge'),
          'count_finalized' => $this->t('Num entries scored and finalized'),
          'count_assigned' => $this->t('Num entries assigned'),
        ];
        break;

      case 'votes':
        $cols = [
          'ceid' => $this->t('Entry ID'),
          'user_name' => $this->t('Username'),
          'votes' => $this->t('Round @round_id Votes', [
            '@round_id' => $round_id,
          ]),
        ];

        break;

      case 'log':
        $cols = [
          'ceid' => $this->t('Entry ID'),
          // 'user.name' => $this->t('Username'),.
          'user_name' => $this->t('Username'),
          // 'log.judge_name' => $this->t('Judge'),.
          'log.user_name' => $this->t('Judge'),
          'log.timestamp' => $this->t('Timestamp'),
          'log.message' => $this->t('Action'),
        ];

        break;

    }

    return $cols;
  }

  /**
   * Get a select query for a given judging report.
   *
   * @param string $report
   *   Custom key denoting a particular judging report.
   * @param array $filters
   *   Filter values by which to limit the report - generally for basic
   *   properties of entries:
   *   'type' - competition entry type
   *   'cycle' - cycle of competition
   *   'ceid' - entry ID, to get data on a single entry (only applicable to
   *     some report types)
   *   'round_id' - judging round number.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The query to pull data for the report - not yet executed.
   *
   * @throws \InvalidArgumentException
   *   If $report is not one of the available judging reports.
   */
  public function getJudgingReportQuery($report, array $filters = []) {

    if (!in_array($report, $this->getAllJudgingReportKeys())) {
      throw new \InvalidArgumentException('Argument $report value "' . $report . '" is not a valid judging report.');
    }

    /* @var \Drupal\Core\Database\Query\SelectInterface $select */
    $select = $this->dbConnection->select(static::REPORT_JUDGING_TABLE, 'rd');

    // Add filters that apply to all reports.
    foreach (['type', 'cycle'] as $key) {
      if (!empty($filters[$key])) {
        $select->condition('rd.' . $key, $filters[$key]);
      }
    }

    // Ensure integers - sometimes these end up as numerical strings.
    foreach (['round_id', 'ceid'] as $key) {
      if (!empty($filters[$key])) {
        $filters[$key] = (int) $filters[$key];
      }
    }

    switch ($report) {

      case 'master':

        $select->addField('rd', 'ceid');

        // Add 'score' field for this entry for each round up to the given.
        // Note: because there's a score row per judge assigned for a single
        // entry, these joins will create multiple rows per entry. Only one row
        // per entry is needed, as every score row also contains overall-round
        // data. This is handled while processing the result rows:
        // @see generateReportRowsJudging()
        for ($r = 1; $r <= $filters['round_id']; $r++) {

          $alias = 'rd';

          // Initial selected table 'rd' => round 1.
          // Join same table once for each additional round.
          if ($r > 1) {
            $alias = 'rd' . $r;
            $select->innerJoin(static::REPORT_JUDGING_TABLE, $alias, $alias . '.ceid = rd.ceid');
          }

          // Limit this joined table to the round.
          $select->condition($alias . '.round_id', $r);

          // Add score column.
          $select->addField($alias, 'score', 'round_' . $r . '_score');

        }

        $select->orderBy('rd.ceid', 'asc');

        break;

      case 'scores':
        $select->isNotNull('rd.score');

        if (!empty($filters['round_id'])) {
          $select->condition('rd.round_id', $filters['round_id']);
        }

        if (!empty($filters['ceid'])) {
          $select->condition('rd.ceid', $filters['ceid']);
        }

        $select->fields('rd', [
          'ceid',
          'user_name',
          'judge_name',
          'score',
          'score_finalized',
        ]);

        $select->orderBy('rd.ceid', 'asc');

        break;

      case 'judge_scores':
        $select->isNotNull('rd.score');

        if (!empty($filters['round_id'])) {
          $select->condition('rd.round_id', $filters['round_id']);
        }

        if (!empty($filters['ceid'])) {
          $select->condition('rd.ceid', $filters['ceid']);
        }

        $select->fields('rd', [
          'judge_name',
          'ceid',
          'score',
        ]);

        $select->orderBy('rd.judge_name', 'asc');
        $select->orderBy('rd.ceid', 'asc');

        break;

      case 'scores_completed':
        $select->isNotNull('rd.score');

        if (!empty($filters['round_id'])) {
          $select->condition('rd.round_id', $filters['round_id']);
        }

        $select->fields('rd', [
          'judge_name',
        ]);

        // TODO? these expressions for count_assigned, count_finalized are
        // MySQL-specific...
        $select->addExpression('COUNT(rd.score)', 'count_assigned');

        $select->addExpression('SUM( IF(rd.score_finalized = 1, 1, 0) )', 'count_finalized');

        $select->groupBy('rd.judge_name');

        $select->orderBy('rd.judge_name', 'asc');

        break;

      case 'votes':
        $select->isNotNull('rd.votes');

        if (!empty($filters['round_id'])) {
          $select->condition('rd.round_id', $filters['round_id']);
        }

        if (!empty($filters['ceid'])) {
          $select->condition('rd.ceid', $filters['ceid']);
        }

        $select->fields('rd', [
          'ceid',
          'user_name',
          'votes',
        ]);

        $select->orderBy('rd.ceid', 'asc');

        break;

      case 'log':
        $select->isNotNull('rd.log');

        if (!empty($filters['round_id'])) {

          // Cannot filter directly on round_id because log rows are per entry,
          // not per entry per round.
          // An inner join to same table with condition on round may result in
          // duplicate records, because there is one score record per judge
          // per round, which may be multiple per entry.
          // Use a subquery, with 'distinct', that applies the same filters plus
          // the round filter, to get all entry IDs in this round.
          $select_sub = $this->dbConnection->select(static::REPORT_JUDGING_TABLE, 'rd2')
            ->fields('rd2', ['ceid'])
            ->distinct();

          foreach (['type', 'cycle', 'ceid'] as $key) {
            if (!empty($filters[$key])) {
              $select_sub->condition('rd2.' . $key, $filters[$key]);
            }
          }

          $select_sub->condition('rd2.round_id', $filters['round_id']);

          // Then add subquery as a condition on main query.
          // Note: subqueries only work with 'IN' operator, up til 8.3.x.
          // @see https://www.drupal.org/node/2770421 (change record)
          $select->condition('rd.ceid', $select_sub, 'IN');

        }

        if (!empty($filters['ceid'])) {
          $select->condition('rd.ceid', $filters['ceid']);
        }

        $select->fields('rd', [
          'ceid',
          'user_name',
          'log',
        ]);

        $select->orderBy('rd.ceid', 'asc');

        break;

    }

    return $select;
  }

  /**
   * Flatten entities.
   *
   * Retrieve all field values from this set of entities; values are formatted
   * for final report output.
   *
   * Field metadata is retrieved as needed for any types of competition entries
   * included in the set, or for users.
   *
   * Note: this method does not chunk/batch the load and loop of entities.
   * Calling code must be responsible if that could be an issue.
   *
   * @param string $entity_type
   *   Possible values: 'competition_entry' or 'user'.
   * @param array $ids
   *   The IDs of the entities to process.
   *
   * @return array
   *   Entities.
   */
  public function flattenEntities($entity_type, array $ids) {

    $report_table_cols = $this->getReportTableColumns();

    $status_labels = $this->getEntryStatusLabels();

    // Each entity is flattened into one row.
    $rows = [];

    $entities = $this->entityTypeManager->getStorage($entity_type)->loadMultiple($ids);

    foreach ($entities as $id => $entity) {

      // Retrieve field metadata for this competition entry type, or for user.
      // (These are cached locally per type.)
      $bundle = ($entity_type == 'competition_entry' ? $entity->getType() : NULL);
      $fields_meta = $this->getEntityReportFieldsMeta($entity_type, $bundle)['fields_meta'];

      $row = [];

      $entry_temp_data = NULL;
      $owner = NULL;

      if ($entity_type == 'competition_entry') {
        // If save-for-later feature is enabled on this competition, entries may
        // have partial data stored that is not saved to entry entity yet.
        // Check for these values to pull into export.
        if (method_exists($entity, 'getTempData')) {
          $entry_temp_data = $entity->getTempData();
        }
        if (empty($entry_temp_data)) {
          $entry_temp_data = [];
        }

        // Load the entry owner's user entity.
        $owner = $entity->getOwner();
      }

      // Allow alteration of all loaded data for this entry just before looping
      // fields to output.
      // Note that we pass the entry and user objects, which allows direct
      // modification, but these variables are not used anywhere else but this
      // export, from here on.
      // TODO: document this hook.
      if ($entity_type == 'competition_entry') {
        $alter_data = [
          'entry' => &$entity,
          'entry_temp_data' => &$entry_temp_data,
          'owner' => &$owner,
        ];
      }
      else {
        $alter_data = [
          'account' => &$entity,
        ];
      }

      // This argument is passed by reference as well. Do not allow these to be
      // altered.
      $alter_context = [
        'fields_meta' => $fields_meta,
        'entity_type' => $entity_type,
      ];
      $this->moduleHandler->alter('competition_entry_report_entry_data', $alter_data, $alter_context);

      // Pull value for each field into single csv row.
      // Key is one of:
      // - {field_name} - for entry fields
      // - user.{field_name} - for user fields.
      foreach ($fields_meta as $field_key => $meta) {

        $on_user = strpos($field_key, 'user.') === 0;

        $field_name = ($on_user ? substr($field_key, 5) : $field_key);

        // Get array of all field values.
        if ($entity_type == 'competition_entry') {
          $values = ($on_user ?
            $owner->get($field_name)->getValue()
            : $entity->get($field_name)->getValue());
        }
        else {
          if ($on_user) {
            $values = $entity->get($field_name)->getValue();
          }
          else {
            // Fields that would be on entry are not in users report.
            $values = NULL;
          }
        }

        // If no value on entity for this field, check for value in temp data.
        // (This only applies to fields on the entry, not the user.)
        $values_temp = NULL;
        if ($entity_type == 'competition_entry' && !$on_user && empty($values) && !empty($entry_temp_data[$field_name])) {
          $value_temp = $entry_temp_data[$field_name]['input'];

          // IMPORTANT: This structure is a bit different - it does not
          // handle multiple values per field, due to how the temp data
          // save method loops through the form value structure.
          // @see CompetitionEntry::setTempData()
          // So, we expect the temp value to be a string if only one property,
          // or an array if multiple properties. Restructure to align with
          // saved field values' structure, for sake of processing below.
          if (is_string($value_temp) || is_int($value_temp)) {
            if (!empty($value_temp)) {
              // We don't have the property's name here; use a placeholder.
              $values_temp = [
                [
                  '_temp_value' => $value_temp,
                ],
              ];
            }
          }
          elseif (is_array($value_temp)) {
            $adjusted = FALSE;

            if ($meta['field_type'] == 'file') {
              // BANDAID:
              // File fields have a 'display' property which is currently
              // getting saved into the temp data, even if no file is uploaded.
              // Filter that out.
              if (isset($value_temp['display']) && empty($value_temp['fids'])) {
                $values_temp = [];
                $adjusted = TRUE;
              }
              // Single file ID is stored in 'fids', which is not one of the
              // property names.
              elseif (!empty($value_temp['fids'])) {
                $values_temp = [
                  [
                    'fids' => $value_temp['fids'],
                  ],
                ];
                $adjusted = TRUE;
              }
            }

            // List fields:
            // For checkboxes (and maybe for multi-value select lists?), the
            // form widget stores multiple values as a single flat array.
            // (This would be interpreted as property => value in our code
            // here.) Restructure as multiple field values.
            if (in_array($meta['field_type'], [
              'list_string',
              'list_integer',
              'list_float',
            ])) {
              $values_temp = [];
              foreach ($value_temp as $v) {
                $values_temp[] = [
                  '_temp_value' => $v,
                ];
              }
              $adjusted = TRUE;
            }

            // Generically, pare down to expected property keys.
            if (!$adjusted) {
              if (!empty($meta['properties'])) {
                $value_temp = array_intersect_key($value_temp, array_flip($meta['properties']));
              }
            }

            // If not handled specifically above, and nonempty after properties
            // filter, set final structured value.
            if (!$adjusted && !empty($value_temp)) {
              $values_temp = [
                $value_temp,
              ];
            }
          }
        }

        // Initialize the output for this field (or this field split into
        // properties) in the row.
        if (count($meta['properties']) == 1) {
          $row[$field_key] = '';
        }
        else {
          foreach ($meta['properties'] as $property_name) {
            $row[$field_key . '.' . $property_name] = '';
          }
        }

        if (!empty($values) || !empty($values_temp)) {

          if ($entity_type == 'competition_entry' && $field_key == 'data') {

            // Entry `data` field stores a serialized blob (including all
            // judging data). Pull this specially, so it can be unserialized and
            // altered specifically for the reporting context.
            // Note: this remains a structured array - it will not be flattened
            // for storage into the reporting table. Extracting judging data
            // values into separate columns (and often separate rows) is handled
            // at the point of generating the CSV.
            // $row[$field_key] = $entity->getDataReportingAltered();
            $data = $entity->getData();

            // Judging data is handled in separate reporting table altogether,
            // so don't store it here.
            if (!empty($data['judging'])) {
              unset($data['judging']);
            }

            $row[$field_key] = $data;

          }
          elseif ($meta['field_type'] == 'file') {

            // For files, the field value will only get us the file's entity
            // ID - which isn't too useful. Load the file entities and pull
            // the filenames too.
            if (!empty($values)) {
              // Getting one referenced entity out of the field value requires
              // quite a few chained calls, but there is a convenience method
              // to load all referenced entities.
              // Thx: http://drupal.stackexchange.com/questions/186315/how-to-get-instance-of-referenced-entity
              // @var \Drupal\file\Entity\File[] $files
              if ($entity_type == 'competition_entry') {
                $files = ($on_user ?
                  $owner->get($field_name)->referencedEntities()
                  : $entity->get($field_name)->referencedEntities());
              }
              else {
                $files = $entity->get($field_name)->referencedEntities();
              }
            }
            elseif (!empty($values_temp)) {
              $fids = [];
              foreach ($values_temp as $value) {
                // Single file ID is stored in 'fids'.
                $fids[] = $value['fids'];
              }
              $files = $this->entityTypeManager->getStorage('file')->loadMultiple($fids);
            }

            $multi_value = count($files) > 1;
            foreach (array_values($files) as $delta => $file) {

              // TODO:
              // For now, denote multiple values by labeling with delta.
              // This is somewhat awkward to read in a spreadsheet but more
              // noticeable than line breaks, which entirely obscure
              // additional values.
              if ($multi_value) {
                $row[$field_key] .= (' {' . $delta . '} ');
              }

              $row[$field_key] .= ($file->id() . ' - ' . $file->get('filename')->getValue()[0]['value']);

            }

          }
          elseif ($field_key == 'status') {

            // Output entry status label, rather than integer key.
            // (Entry status will never be in temp data)
            $row[$field_key] .= $status_labels[(int) $values[0]['value']];

          }
          elseif (in_array($meta['field_type'], [
            'created',
            'changed',
            'timestamp',
          ])) {
            // TODO: this field_type check does not handle properties.
            // Timestamp values:
            // Convert to human-readable date/time string.
            // (For now, all these fields are auto-generated, not user-input -
            // note we don't include 'datetime' type - so they'll never be
            // stored in entry temp data.)
            $timestamp = (int) $values[0]['value'];
            // TODO: perhaps make a custom format that only controls this.
            $row[$field_key] .= $this->dateFormatter->format($timestamp, 'short');

          }
          else {

            // Generic processing (almost).
            $values_process = (!empty($values) ? $values : $values_temp);

            $multi_value = count($values_process) > 1;

            foreach ($values_process as $delta => $value) {
              if (count($meta['properties']) == 1) {
                // Simplest case: one property; output to single column.
                // TODO:
                // For now, denote multiple values by labeling with delta.
                // This is somewhat awkward to read in a spreadsheet but more
                // noticeable than line breaks, which entirely obscure
                // additional values.
                if ($multi_value) {
                  $row[$field_key] .= (' {' . $delta . '} ');
                }

                $property_name = reset($meta['properties']);

                $value_out = '';
                if (isset($value[$property_name])) {
                  $value_out = $value[$property_name];
                }
                elseif (isset($value['_temp_value'])) {
                  // Special key used in temp saved-for-later data - see
                  // restructuring above.
                  $value_out = $value['_temp_value'];
                }

                if ($meta['field_type'] == 'boolean') {
                  // Boolean fields:
                  // Should use the configured 'On' label and 'Off' label.
                  // However, currently, the 'On' label is sometimes configured
                  // as the full text desired to show by the checkbox, which is
                  // too long for export.
                  if ($value_out === 1 || $value_out === '1') {
                    // $value_out = '[ X ]';.
                    $value_out = 'true';
                  }
                  elseif ($value_out === 0 || $value_out === '0') {
                    // $value_out = '[ ]';.
                    $value_out = 'false';
                  }
                }

                $row[$field_key] .= $value_out;
              }
              else {
                // Multiple properties: output to separate column per property
                // (as was done for the column labels).
                foreach ($meta['properties'] as $property_name) {
                  $property_key = $field_key . '.' . $property_name;

                  // TODO:
                  // For now, denote multiple values by prefix with the delta.
                  // This is extra awkward for multiple values in a multi-
                  // property field, as those values should be grouped by delta,
                  // but we split into a column per property for readability...
                  if ($multi_value) {
                    $row[$property_key] .= (' {' . $delta . '} ');
                  }

                  if (isset($value[$property_name])) {
                    $row[$property_key] .= $value[$property_name];
                  }
                }
              }
            }

          }
        }

        // Trim because of multiple-value delineators.
        if (isset($row[$field_key])) {
          if (is_string($row[$field_key])) {
            $row[$field_key] = trim($row[$field_key]);
          }
        }
        elseif (!empty($meta['properties'])) {
          foreach ($meta['properties'] as $property_name) {
            $property_key = $field_key . '.' . $property_name;
            if (isset($row[$property_key])) {
              if (is_string($row[$property_key])) {
                $row[$property_key] = trim($row[$property_key]);
              }
            }
          }
        }

      }

      // Post-process: restructure to match report data table columns.
      $report_row = [];
      // `data` column in report table holds JSON blob of all values not stored
      // in other columns.
      $json_data = $row;

      // If processing user entities, copy the user ID value so it'll be pulled
      // by 'uid' col name in the loop below.
      if ($entity_type == 'user') {
        $row['uid'] = $row['user.uid'];
      }

      // A few columns map directly to field values - retrieve these first.
      foreach ($report_table_cols as $col) {
        // JSON data blob is populated after.
        if ($col == 'data') {
          continue;
        }

        // Set default.
        // Note: entry-specific col values will remain NULL for
        // users-without-entries rows.
        $report_row[$col] = NULL;

        // `ceid` is part of primary key, so we use 0 instead of NULL.
        // @see competition_schema()
        if ($col == 'ceid') {
          $report_row['ceid'] = 0;
        }

        // Set value in report row; remove it from JSON data.
        if (array_key_exists($col, $row)) {
          $report_row[$col] = $row[$col];
          unset($json_data[$col]);
        }
      }

      // Remaining fields go into the JSON blob column.
      $json = Json::encode($json_data);
      // This wraps PHP's json_encode(), which returns FALSE on failure.
      if ($json === FALSE) {
        $this->logger->error('%entity_type %id - error occurred attempting to convert field values to JSON.<br/><br/>Error message:<br/>@message<br/><br/>Field values:<br/>@values', [
          '%entity_type' => $entity_type,
          '%id' => $entity->id(),
          '@message' => json_last_error_msg(),
          '@values' => var_export($json_data, TRUE),
        ]);

        $report_row['data'] = NULL;
      }
      else {
        $report_row['data'] = $json;
      }

      $rows[$entity->id()] = $report_row;
    }

    return $rows;
  }

  /**
   * Get all judging reporting data rows for this set of competition entries.
   *
   * Judging data is extracted from $entry->data['judging'] and mostly
   * flattened.
   *
   * If an entry is in any rounds, its judging data will be stored in multiple
   * rows of different types:
   * - score - contains an assigned judge's score data, for a single round
   * - log - contains all log messages, across all rounds this entry is in
   * - votes - contains total number of votes for this entry, for a single
   *   voting round (if entry is in any)
   *
   * @param array $ids
   *   Competition entry IDs for which to generate report rows.
   *
   * @return array
   *   Array of arrays; each sub-array is a row to be inserted in the judging
   *   reporting table.
   */
  public function flattenEntitiesJudging(array $ids) {

    $rows = [];

    // Lazy-load judging stuff by competition, since we may be processing
    // entries in more than one competition.
    $judging_cache = [];

    $storage_user = $this->entityTypeManager->getStorage('user');

    $cols = $this->getReportJudgingTableColumns();

    /* @var \Drupal\competition\CompetitionEntryInterface[] $entries */
    $entries = $this->entityTypeManager->getStorage('competition_entry')->loadMultiple($ids);

    foreach ($entries as $entry) {

      $data = $entry->getData();
      if (!empty($data['judging'])) {

        $competition = $entry->getCompetition();

        if (empty($judging_cache[$competition->id()])) {
          $judging = $competition->getJudging();

          if (!empty($round_config['weighted_criteria'])) {
            foreach ($judging->rounds as $round_id => &$round_config) {
              $i = 0;
              foreach ($round_config['weighted_criteria'] as $label => $weight) {
                $round_config['criteria_weights']['c' . $i] = (int) $weight;
                $i++;
              }
            }
          }

          $judging_cache[$competition->id()] = $judging;
        }

        $judging = $judging_cache[$competition->id()];

        // "Display name" method will retrieve the anonymous user label (no
        // translation there apparently), while "account name" method returns
        // the user.name property.
        $owner = $entry->getOwner();
        $user_name = $owner->id() > 0 ? $owner->getAccountName() : ('(' . $owner->getDisplayName() . ')');

        $row_base = [
          'type' => $entry->getType(),
          'cycle' => $entry->getCycle(),
          'ceid' => $entry->id(),
          'user_name' => $user_name,
        ];

        // Score and votes rows.
        foreach ($data['judging']['rounds'] as $round_id => $round) {

          $round_config = $judging->rounds[$round_id];

          if ($round_config['round_type'] == 'criteria' || $round_config['round_type'] == 'pass_fail') {

            // Add different displays of each judge's criteria scores.
            /*
             * Score structure example (1 judge, 3 criteria):
             *
             *      Points | Percent | Weight | Weighted Points
             *      ------   -------   ------   ---------------
             * c0 |  3/4   =   75%      30%      .75 * 30 = 22.5
             * c1 |  1/4   =   25%      20%      .25 * 20 =  5
             * c2 |  2/4   =   50%      50%      .50 * 50 = 25
             *                                  ---------------
             *          Overall weighted score =  52.5 / 100
             */
            if (!empty($round['scores'])) {

              // Collect all score rows in intermediary array.
              $score_rows = [];

              $points_max = (int) $round_config['criterion_options'];

              // Collect weighted score (total points / 100) for each judge, to
              // determine total weighted points.
              // A judge's score must be complete (values submitted for all
              // criteria) to factor into the total.
              $overall_scores = [];

              foreach ($round['scores'] as &$score) {

                // Initialize row for this judge's score.
                $row = $row_base;

                $row['round_id'] = (int) $round_id;

                $account = $storage_user->load($score->uid);
                $row['judge_name'] = $account ? $account->getAccountName() : NULL;

                $row['score'] = [];

                // In case the user entity didn't load (deleted user), include
                // the uid for potential use in output.
                $row['score']['uid'] = $score->uid;

                $overall_scores[$score->uid] = 0;

                if (!empty($score->criteria)) {
                  foreach ($score->criteria as $key => $weighted_points) {

                    if ($weighted_points === NULL) {

                      $row['score']['criteria'][$key] = [];

                      $overall_scores[$score->uid] = NULL;

                    }
                    else {

                      $weight = $round_config['criteria_weights'][$key];
                      $percent = $weighted_points / $weight;
                      $points = $percent * $points_max;
                      // This should convert back to very close to the original
                      // integer value... but because floats aren't 'precise',
                      // it may not be. Round before casting.
                      $points = (int) round($points);

                      $row['score']['criteria'][$key] = [
                        // criterion.weighted_points
                        // 22.5.
                        'weighted_points' => $weighted_points,

                        // criterion.display_weighted_points
                        // '22.5 / 30'.
                        'display_weighted_points' => $weighted_points . ' / ' . $weight,

                        // criterion.display_percent
                        // '75%'
                        // TODO: rounding/decimal points?
                        'display_percent' => ($percent * 100) . '%',

                        // criterion.display_points
                        // '3 / 4'.
                        'display_points' => $points . ' / ' . $points_max,
                      ];

                      if ($overall_scores[$score->uid] !== NULL) {
                        $overall_scores[$score->uid] += $weighted_points;
                      }

                    }

                  }
                }

                // score.weighted_points.
                if ($overall_scores[$score->uid] !== NULL) {

                  $row['score']['weighted_points'] = $overall_scores[$score->uid];

                  // Add boolean for pass/fail rounds.
                  // These have only one criterion, so pass/fail status is
                  // equivalent for the one criterion and overall score.
                  if ($round_config['round_type'] == 'pass_fail') {

                    $pass = $score->criteria['c0'] == 100;

                    $row['score']['criteria']['c0']['pass'] = $pass;
                    $row['score']['pass'] = $pass;

                  }

                }
                else {
                  // Remove incomplete scores from counting towards average.
                  unset($overall_scores[$score->uid]);
                }

                // score_finalized.
                $row['score_finalized'] = $score->finalized ? 1 : 0;

                // Add row to intermediary array.
                $score_rows[] = $row;

              }

              // These properties apply to the entry per round, not per judge.
              // Because we are flattening, we don't have a place to store
              // per-round values - so store them in 'score' in each row.
              $round_values = [];

              // round.display_average.
              if ($round['computed'] !== NULL) {
                $round_values['display_average'] = number_format(round($round['computed'], 2), 2) . '%';

                // Add boolean for pass/fail rounds.
                // Currently, overall pass is presumed to require an average
                // of 100%, i.e. all assigned judges scored as pass.
                if ($round_config['round_type'] == 'pass_fail') {
                  $round_values['pass'] = ($round['computed'] == 100);
                }
              }

              // round.display_total_weighted_points.
              if (count($overall_scores) > 0) {
                $round_values['display_total_weighted_points'] = array_sum($overall_scores) . ' / ' . (count($overall_scores) * 100);
              }

              if (!empty($round_values)) {
                foreach ($score_rows as &$row) {
                  $row['score']['round'] = $round_values;
                }
              }

            }

            // Finally, add all score rows.
            if (!empty($score_rows)) {
              foreach ($score_rows as &$row) {
                $row['score'] = Json::encode($row['score']);

                // Error encoding JSON.
                if ($row['score'] === NULL) {
                  // TODO: logger
                  // TODO: if admin, set message.
                }

                // Fill in cols not applicable to this row type with NULL.
                foreach (array_diff($cols, array_keys($row)) as $col) {
                  $row[$col] = NULL;
                }

                $rows[] = $row;
              }
            }

          }
          elseif ($round_config['round_type'] == 'voting') {

            $row = $row_base;

            $row['round_id'] = (int) $round_id;

            // We store only the current total number of votes for this entry.
            $row['votes'] = isset($round['votes']) ? $round['votes'] : NULL;

            // Fill in cols not applicable to this row type with NULL.
            foreach (array_diff($cols, array_keys($row)) as $col) {
              $row[$col] = NULL;
            }

            $rows[] = $row;

          }

        }

        // Log row.
        $logs = $entry->getJudgingLog();
        if (!empty($logs)) {

          $row = $row_base;

          // Logs are stored in chronological order; output in reverse for
          // reports, matching admin UI notes display.
          $logs = array_reverse($logs);

          // Plug in user names.
          foreach ($logs as &$log) {
            if (!empty($log['uid'])) {
              $account = $storage_user->load($log['uid']);
              if (!empty($account)) {
                $log['user_name'] = $account->getAccountName();
              }
            }
          }

          $logs_json = Json::encode($logs);

          // Error encoding JSON.
          if ($logs_json === NULL) {
            // TODO: logger
            // TODO: if admin, set message.
          }

          $row['log'] = $logs_json;

          // Fill in cols not applicable to this row type with NULL.
          foreach (array_diff($cols, array_keys($row)) as $col) {
            $row[$col] = NULL;
          }

          $rows[] = $row;
        }

      }

    }

    return $rows;
  }

  /**
   * Get count of records of given entity type in report data table.
   *
   * @param string $entity_type
   *   Entity type with possible values 'competition_entry' or 'user'
   *   user records are those users without any competition entry.
   * @param array $params
   *   Additional conditions by which to filter the count. Only params valid
   *   for the given $entity_type will be applied.
   *   For competition_entry:
   *   - type
   *   - cycle
   *   - status.
   *
   * @return int|null
   *   The count of records in `competition_entry_report_data`
   *
   * @throws \InvalidArgumentException
   *   If $entity_type is not one of 'competition_entry' or 'user'.
   */
  public function getCountReportRecords($entity_type, array $params = []) {

    if (!in_array($entity_type, ['competition_entry', 'user'])) {
      throw new \InvalidArgumentException('Argument $entity_type must be either "competition_entry" or "user".');
    }

    $count = NULL;

    $query = $this->dbConnection->select($this::REPORT_TABLE, 'rd');

    if ($entity_type == 'competition_entry') {
      $query->fields('rd', ['ceid'])
        // ->isNotNull('ceid')
        ->condition('ceid', 0, '<>');

      // Note: currently there's no validation of param values - if any are
      // unexpected values, they should simply result in the query returning
      // no results.
      if (!empty($params)) {

        // Convert status key to its label, because reporting table holds
        // ready-for-output values.
        // TODO: this is not great
        // (Use isset() to include status '0' for 'Created')
        if (isset($params['status'])) {
          $statuses = $this->competitionSettings->get('statuses');
          if (!empty($statuses[$params['status']])) {
            $params['status'] = $statuses[$params['status']];
          }
        }

        foreach ($params as $k => $v) {
          if (in_array($k, ['type', 'cycle', 'status'])) {
            $query->condition($k, $v);
          }
        }
      }
    }
    elseif ($entity_type == 'user') {
      $query->fields('rd', ['uid'])
        // ->isNull('ceid')
        ->condition('ceid', 0, '=');
    }

    $count = (int) $query->countQuery()
      ->execute()
      ->fetchField();

    return $count;
  }

  /**
   * Get a count of entries represented in judging report data table.
   *
   * This method does not check for data integrity of records - i.e. that all
   * score rows and log row exist for the given entries. If 'round_id' filter
   * exists, result is based on score rows (because only score rows have a
   * round_id column value); otherwise it's based on any records that exist.
   *
   * @param array $params
   *   Possible keys:
   *   'type' - competition entry type/bundle
   *   'cycle' - competition cycle
   *   'round_id' - judging round.
   *
   * @return int
   *   The number of entities represented in data table with given filters.
   */
  public function getCountReportJudgingEntities(array $params = []) {

    // Limit to supported cols.
    $params = array_intersect_key($params, array_flip([
      'type',
      'cycle',
      'round_id',
    ]));

    // Ensure integers for relevant params.
    foreach (['round_id'] as $col) {
      if (!empty($params[$col])) {
        $params[$col] = (int) $params[$col];
      }
    }

    /* @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->dbConnection->select(static::REPORT_JUDGING_TABLE, 'rd')
      ->fields('rd', ['ceid'])
      ->distinct();

    foreach ($params as $col => $value) {
      $query->condition('rd.' . $col, $value);
    }

    // Convert to count query after everything else, because Drupal wraps the
    // original query as a subquery.
    $query = $query->countQuery();

    return $query->execute()->fetchField();

  }

  /**
   * Update values in reporting table for all current entries and users.
   *
   * - Query for all entries and users-without-entries that need to be added or
   *   updated in report data table
   * - Flatten them (get all field values into a single array, corresponding to
   *   report table columns)
   * - Delete records that need to be updated, then insert all new + updated
   *   entity records.
   *
   * This is invoked by Drush command: 'competition-update-report-data'
   *
   * @param bool $use_batch
   *   Whether to invoke a batch process to run this. This should be TRUE
   *   when calling from a webpage context (e.g. admin UI button), where PHP
   *   timeout could happen.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
   *   If $batch is TRUE, the redirect response from batch_process().
   *   If $batch is FALSE, array of counts of data before/during/after this run,
   *   formatted with '@' for easily passing as $this->t() args. All keys:
   *   - @count_init_competition_entry
   *   - @count_init_user
   *   - @count_init_judging_ids
   *   - @count_deleted_competition_entry
   *   - @count_deleted_user
   *   - @count_deleted_judging_ids
   *   - @count_deleted_judging_records
   *   - @count_add_user
   *   - @count_update_user
   *   - @count_add_competition_entry
   *   - @count_deleted_user_now_entry
   *   - @count_update_competition_entry
   *   - @count_add_judging_ids
   *   - @count_update_judging_ids
   *   - @count_removed_judging_ids
   *   - @count_removed_judging_records
   *   - @count_total_competition_entry
   *   - @count_this_run_competition_entry
   *   - @count_total_user
   *   - @count_this_run_user
   *   - @count_total_judging_ids
   *   - @count_this_run_judging_ids
   *
   *   TODO: currently setting `ceid` to `0` for users-without-entries records.
   *
   * @see competition_schema()
   */
  public function updateAllReportData($use_batch) {

    $log_args = [];

    // --- Log starting counts of records.
    $log_args['@count_init_competition_entry'] = $this->getCountReportRecords('competition_entry');
    $log_args['@count_init_user'] = $this->getCountReportRecords('user');
    $log_args['@count_init_judging_ids'] = $this->getCountReportJudgingEntities();

    // --- Delete report records for entities that have been deleted.
    // Drupal's DeleteQuery does not support joins - so select first, then
    // delete that set of entity IDs.
    // Entries
    /*
    DELETE rd
    FROM competition_entry_report_data rd
    LEFT JOIN competition_entry ce
    ON ce.ceid = rd.ceid
    WHERE rd.ceid <> 0
    AND ce.ceid IS NULL
     */
    $select_entries_delete = $this->dbConnection->select($this::REPORT_TABLE, 'rd')
      ->fields('rd', ['ceid'])
      // ->isNotNull('rd.ceid')
      ->condition('rd.ceid', 0, '<>');
    $select_entries_delete->leftJoin('competition_entry', 'ce', 'ce.ceid = rd.ceid');
    $select_entries_delete->isNull('ce.ceid');

    $entries_delete_ids = $select_entries_delete->execute()->fetchCol();

    $log_args['@count_deleted_competition_entry'] = 0;
    if (!empty($entries_delete_ids)) {
      $log_args['@count_deleted_competition_entry'] = $this->dbConnection->delete($this::REPORT_TABLE)
        ->condition('ceid', $entries_delete_ids, 'IN')
        ->execute();
    }

    // Users
    /*
    DELETE rd
    FROM competition_entry_report_data rd
    LEFT JOIN users u
    ON u.uid = rd.uid
    WHERE rd.ceid = 0
    AND u.uid IS NULL
     */
    $select_users_delete = $this->dbConnection->select($this::REPORT_TABLE, 'rd')
      ->fields('rd', ['uid'])
      // ->isNull('rd.ceid')
      ->condition('rd.ceid', 0);
    $select_users_delete->leftJoin('users', 'u', 'u.uid = rd.uid');
    $select_users_delete->isNull('u.uid');

    $users_delete_ids = $select_users_delete->execute()->fetchCol();

    $log_args['@count_deleted_user'] = 0;
    if (!empty($users_delete_ids)) {
      $log_args['@count_deleted_user'] = $this->dbConnection->delete($this::REPORT_TABLE)
        // ->isNull('ceid')
        ->condition('ceid', 0)
        ->condition('uid', $users_delete_ids, 'IN')
        ->execute();
    }

    // 3) Entries - judging table
    /*
    DELETE rd
    FROM competition_entry_report_judging_data rd
    LEFT JOIN competition_entry ce
    ON ce.ceid = rd.ceid
    WHERE ce.ceid IS NULL
     */
    $select_judging_deleted = $this->dbConnection->select($this::REPORT_JUDGING_TABLE, 'rd')
      ->fields('rd', ['ceid'])
      ->distinct();
    $select_judging_deleted->leftJoin('competition_entry', 'ce', 'ce.ceid = rd.ceid');
    $select_judging_deleted->isNull('ce.ceid');

    $judging_deleted_ids = $select_judging_deleted->execute()->fetchCol();
    $log_args['@count_deleted_judging_ids'] = count($judging_deleted_ids);

    $log_args['@count_deleted_judging_records'] = 0;
    if (!empty($judging_deleted_ids)) {
      $log_args['@count_deleted_judging_records'] = $this->dbConnection->delete($this::REPORT_JUDGING_TABLE)
        ->condition('ceid', $judging_deleted_ids, 'IN')
        ->execute();
    }

    // --- Collect IDs of all entities to be added/updated in report tables.
    // -- Entity report data table --.
    // 1) User accounts - add:
    // - no entry
    // - not in report table
    /*
    SELECT u.uid
    FROM users u
    LEFT JOIN competition_entry ce
    ON ce.uid = u.uid
    LEFT JOIN $this::REPORT_TABLE rd
    ON rd.uid = u.uid
    WHERE u.uid > 0
    AND ce.ceid IS NULL
    AND rd.uid IS NULL
     */

    $select_users_add = $this->dbConnection->select('users', 'u')
      ->fields('u', ['uid']);
    $select_users_add->condition('u.uid', 0, '>');

    // No entry.
    $select_users_add->leftJoin('competition_entry', 'ce', 'ce.uid = u.uid');
    $select_users_add->isNull('ce.ceid');

    // Not in report table.
    $select_users_add->leftJoin($this::REPORT_TABLE, 'rd', 'rd.uid = u.uid');
    $select_users_add->isNull('rd.uid');

    $users_add_ids = $select_users_add->execute()->fetchCol();
    $log_args['@count_add_user'] = count($users_add_ids);

    // 2) User accounts - update:
    // - in report table
    // - no entry
    // - user entity changed after report row exported
    /*
    SELECT rd.uid
    FROM $this::REPORT_TABLE rd
    LEFT JOIN competition_entry ce
    ON ce.uid = rd.uid
    INNER JOIN users_field_data ud
    ON ud.uid = rd.uid
    WHERE ce.ceid IS NULL
    AND ud.changed >= rd.exported
     */

    $select_users_update = $this->dbConnection->select($this::REPORT_TABLE, 'rd')
      ->fields('rd', ['uid']);

    // No entry.
    $select_users_update->leftJoin('competition_entry', 'ce', 'ce.uid = rd.uid');
    $select_users_update->isNull('ce.ceid');

    // User entity changed after report row exported.
    $select_users_update->innerJoin('users_field_data', 'ud', 'ud.uid = rd.uid');
    $select_users_update->where('ud.changed >= rd.exported');

    $users_update_ids = $select_users_update->execute()->fetchCol();
    $log_args['@count_update_user'] = count($users_update_ids);

    // 3) Entries - add:
    // - not in report table
    /*
    SELECT ce.ceid, ce.uid
    FROM competition_entry ce
    LEFT JOIN $this::REPORT_TABLE rd
    ON rd.ceid = ce.ceid
    WHERE rd.ceid IS NULL
     */
    $select_entries_add = $this->dbConnection->select('competition_entry', 'ce')
      ->fields('ce', ['ceid', 'uid']);

    // Not in report table.
    $select_entries_add->leftJoin($this::REPORT_TABLE, 'rd', 'rd.ceid = ce.ceid');
    $select_entries_add->isNull('rd.ceid');

    // Retrieve result as ceid => uid.
    $entries_add_result = $select_entries_add->execute()->fetchAllKeyed();
    $entries_add_ids = array_keys($entries_add_result);
    $log_args['@count_add_competition_entry'] = count($entries_add_ids);

    $entries_add_uids = array_unique(array_values($entries_add_result));

    // 3b) User accounts - delete:
    // (For entries newly added to the report table, remove user-only rows
    // for those uids - as they are no longer users without entries.)
    // - previously did not have an entry (in report table)
    // - now have an entry
    /*
    DELETE
    FROM $this::REPORT_TABLE
    WHERE ceid IS NULL
    AND uid IN (:entries_add_uids)
     */
    $num_user_rows_deleted = 0;
    if (!empty($entries_add_uids)) {
      $num_user_rows_deleted = $this->dbConnection->delete($this::REPORT_TABLE)
        // ->isNull('ceid')
        ->condition('ceid', 0, '=')
        ->condition('uid', $entries_add_uids, 'IN')
        ->execute();
    }
    $log_args['@count_deleted_user_now_entry'] = $num_user_rows_deleted;

    // 4) Entries - update:
    // - in report table
    // - entry OR user entity changed after report row exported
    /*
    SELECT rd.ceid
    FROM $this::REPORT_TABLE rd
    INNER JOIN competition_entry ce
    ON ce.ceid = rd.ceid
    WHERE ce.changed >= rd.exported
     */
    $select_entries_update = $this->dbConnection->select($this::REPORT_TABLE, 'rd')
      ->fields('rd', ['ceid']);

    // Entry or user changed after report row exported.
    $select_entries_update->innerJoin('competition_entry', 'ce', 'ce.ceid = rd.ceid');
    $select_entries_update->innerJoin('users_field_data', 'ud', 'ud.uid = rd.uid');
    $select_entries_update->where('ce.changed >= rd.exported OR ud.changed >= rd.exported');

    $entries_update_ids = $select_entries_update->execute()->fetchCol();
    $log_args['@count_update_competition_entry'] = count($entries_update_ids);

    // -- Judging reporting table --.
    // Retrieve all entries that are all involved in judging - any competition,
    // any cycle, round or queue.
    $judging_ids = [];

    $judging_avail_stmt = $this->dbConnection
      ->select('competition_entry', 'ce')
      ->fields('ce', [
        'ceid',
        'data',
      ])
      ->condition('ce.status', CompetitionEntryInterface::STATUS_FINALIZED)
      ->execute();

    while ($row = $judging_avail_stmt->fetchObject()) {
      $data = unserialize($row->data);
      if (!empty($data['judging']['rounds']) || !empty($data['judging']['queues'])) {
        $judging_ids[] = $row->ceid;
      }
    }

    $select_report_judging = $this->dbConnection->select($this::REPORT_JUDGING_TABLE, 'rd')
      ->fields('rd', ['ceid'])
      ->distinct();
    $report_judging_ids = $select_report_judging->execute()->fetchCol();

    // If any entries are no longer in a judging round/queue, remove from
    // judging report table.
    // This needs to run first so these records don't end up in the set of
    // records to be updated.
    $judging_remove_ids = array_diff($report_judging_ids, $judging_ids);
    $log_args['@count_removed_judging_ids'] = count($judging_remove_ids);

    $log_args['@count_removed_judging_records'] = 0;
    if (!empty($judging_remove_ids)) {
      $log_args['@count_removed_judging_records'] = $this->dbConnection->delete($this::REPORT_JUDGING_TABLE)
        ->condition('ceid', $judging_remove_ids, 'IN')
        ->execute();
    }

    // 5) Entries - judging - add:
    $judging_add_ids = array_diff($judging_ids, $report_judging_ids);
    $log_args['@count_add_judging_ids'] = count($judging_add_ids);

    // 6) Entries - judging - update:
    // There are multiple rows per entry. For simplicity, if ANY row for an
    // entry is out of date, we will update all its rows.
    $select_judging_update = $this->dbConnection->select($this::REPORT_JUDGING_TABLE, 'rd')
      ->fields('rd', ['ceid'])
      ->distinct();

    // Entry changed after report row exported
    // (Note: unfortunately no easy way to distinguish whether entry.changed
    // was due to judging data change or any other edit to field values.)
    $select_judging_update->innerJoin('competition_entry', 'ce', 'ce.ceid = rd.ceid');
    $select_judging_update->where('ce.changed >= rd.exported');

    $judging_update_ids = $select_judging_update->execute()->fetchCol();
    $log_args['@count_update_judging_ids'] = count($judging_update_ids);

    // --- Set up for adds/updates.
    // Mark all records with the same timestamp.
    $export_timestamp = REQUEST_TIME;

    // Record timestamp. (This is displayed on admin reports page.)
    $this->setReportDataLastUpdated($export_timestamp);

    // Update rows - delete + insert instead.
    // (Updating different values for a number of rows is tedious - must be done
    // as a separate update query per row or as a complicated CASE statement.
    // Instead, delete all rows we want to update and re-insert them, along with
    // the new inserts.)
    if (!empty($users_update_ids)) {
      $this->dbConnection->delete($this::REPORT_TABLE)
        ->condition('uid', $users_update_ids, 'IN')
        // ->isNull('ceid')
        ->condition('ceid', 0, '=')
        ->execute();
    }

    if (!empty($entries_update_ids)) {
      $this->dbConnection->delete($this::REPORT_TABLE)
        ->condition('ceid', $entries_update_ids, 'IN')
        ->execute();
    }

    if (!empty($judging_update_ids)) {
      $this->dbConnection->delete($this::REPORT_JUDGING_TABLE)
        ->condition('ceid', $judging_update_ids, 'IN')
        ->execute();
    }

    if (empty($entries_add_ids) && empty($entries_update_ids)
      && empty($users_add_ids) && empty($users_update_ids)
      && empty($judging_add_ids) && empty($judging_update_ids)) {

      // Nothing to add/update.
      // We can therefore bypass the batch entirely...
      if ($use_batch) {
        // ...but if any records were deleted, we still want to display that
        // summary as a Drupal message for the user,
        // since the batch-finished function will not be called to do so.
        // Full log message is still handled later in this function.
        if ($log_args['@count_deleted_user'] > 0
          || $log_args['@count_deleted_competition_entry'] > 0
          || $log_args['@count_deleted_judging_ids'] > 0
          || $log_args['@count_removed_judging_ids'] > 0) {

          $this->updateAllReportDataLog($log_args, [
            'drupal_message' => [
              'details' => FALSE,
            ],
          ]);

        }
        else {
          // TODO: fold this case into log method, so above if() is unneeded.
          drupal_set_message($this->t('There is no new reporting data to update since the last run.'), 'warning');
        }
      }
    }
    else {

      // -----------------------------------------------------------------------
      // --- BATCH VERSION: split off now to process the collected IDs.
      if ($use_batch) {
        $batch = [
          'title' => $this->t('Updating report data...'),
          'operations' => [
            // Operation 1: users.
            [
              // Callback.
              [static::class, 'updateAllReportDataBatchProcess'],
              // Arguments to pass to callback.
              [
                'user',
                $users_add_ids,
                $users_update_ids,
                $export_timestamp,
                $log_args,
              ],
            ],
            // Operation 2: entries.
            [
              // Callback.
              [static::class, 'updateAllReportDataBatchProcess'],
              // Arguments to pass to callback.
              [
                'competition_entry',
                $entries_add_ids,
                $entries_update_ids,
                $export_timestamp,
                $log_args,
              ],
            ],
            // Operation 3: judging.
            [
              // Callback.
              [static::class, 'updateAllReportDataBatchProcess'],
              // Arguments to pass to callback.
              [
                'judging',
                $judging_add_ids,
                $judging_update_ids,
                $export_timestamp,
                $log_args,
              ],
            ],
          ],
          'finished' => [static::class, 'updateAllReportDataBatchFinished'],
          // 'file' =>.
        ];

        batch_set($batch);

        // Pass the destination to which to redirect after completion.
        return batch_process(Url::fromRoute('entity.competition_entry.reports'));
      }
      // --- If using batch, processing ends here ------------------------------
      // -----------------------------------------------------------------------
      // --- NON-BATCH VERSION -------------------------------------------------
      // --- Flatten entities and insert/update data in report table.
      // Users:
      if (!empty($users_add_ids) || !empty($users_update_ids)) {
        $user_rows_all = $this->flattenEntities('user', array_merge($users_add_ids, $users_update_ids));

        // Now insert all user rows.
        $insert_users = $this->dbConnection->insert($this::REPORT_TABLE);

        $insert_users->fields($this->getReportTableColumns());

        foreach ($user_rows_all as $row) {
          $row['exported'] = $export_timestamp;
          $insert_users->values($row);
        }

        // 'If the query was given multiple sets of values to insert,
        // the return value is undefined.'.
        $insert_users->execute();
      }

      // Entries:
      if (!empty($entries_add_ids) || !empty($entries_update_ids)) {
        $entry_rows_all = $this->flattenEntities('competition_entry', array_merge($entries_add_ids, $entries_update_ids));

        // Now insert all entry rows.
        $insert_entries = $this->dbConnection->insert($this::REPORT_TABLE);

        $insert_entries->fields($this->getReportTableColumns());

        foreach ($entry_rows_all as $row) {
          $row['exported'] = $export_timestamp;
          $insert_entries->values($row);
        }

        $insert_entries->execute();
      }

      // Judging:
      if (!empty($judging_add_ids) || !empty($judging_update_ids)) {
        $judging_rows_all = $this->flattenEntitiesJudging(array_merge($judging_add_ids, $judging_update_ids));

        // Now insert all judging rows.
        $insert_judging = $this->dbConnection->insert($this::REPORT_JUDGING_TABLE);

        $insert_judging->fields($this->getReportJudgingTableColumns());

        foreach ($judging_rows_all as $row) {
          $row['exported'] = $export_timestamp;
          $insert_judging->values($row);
        }

        $insert_judging->execute();
      }
    }

    // Finally, log actual numbers.
    // Entries:
    $query_count_entries = $this->dbConnection->select($this::REPORT_TABLE, 'rd')
      ->fields('rd', ['ceid'])
      // ->isNotNull('rd.ceid')
      ->condition('rd.ceid', 0, '<>');

    $query_count_entries_this = clone $query_count_entries;
    $query_count_entries_this->condition('rd.exported', $export_timestamp);

    $log_args['@count_total_competition_entry'] = $query_count_entries
      ->countQuery()->execute()->fetchField();

    $log_args['@count_this_run_competition_entry'] = $query_count_entries_this
      ->countQuery()->execute()->fetchField();

    // Users:
    $query_count_users = $this->dbConnection->select($this::REPORT_TABLE, 'rd')
      ->fields('rd', ['uid'])
      // ->isNull('rd.ceid')
      ->condition('rd.ceid', 0, '=');

    $query_count_users_this = clone $query_count_users;
    $query_count_users_this->condition('rd.exported', $export_timestamp);

    $log_args['@count_total_user'] = $query_count_users
      ->countQuery()->execute()->fetchField();

    $log_args['@count_this_run_user'] = $query_count_users_this
      ->countQuery()->execute()->fetchField();

    // Judging:
    // Note: DISTINCT gives us the number of entries for which there are
    // records, not the total number of records.
    $query_count_judging = $this->dbConnection->select($this::REPORT_JUDGING_TABLE, 'rd')
      ->fields('rd', ['ceid'])
      ->distinct();

    $query_count_judging_this = clone $query_count_judging;
    $query_count_judging_this->condition('rd.exported', $export_timestamp);

    $log_args['@count_total_judging_ids'] = $query_count_judging
      ->countQuery()->execute()->fetchField();

    $log_args['@count_this_run_judging_ids'] = $query_count_judging_this
      ->countQuery()->execute()->fetchField();

    // Log - full details to logger only, since we expect the non-batch version
    // to run only from Drush cli call.
    $this->updateAllReportDataLog($log_args, [
      'log' => [
        'details' => TRUE,
        'always' => FALSE,
      ],
    ]);

    return $log_args;
  }

  /**
   * Update all report data.
   *
   * Batch operation function - flatten entities and add/update records to/in
   * `competition_entry_report_data` table.
   *
   * @param string $report_type
   *   Report type with possible values 'user', 'competition_entry', 'judging'.
   * @param array $add_ids
   *   IDs of entities to be added to the report table.
   * @param array $update_ids
   *   IDs of entities to be updated in the report table.
   * @param int $export_timestamp
   *   The timestamp at which to mark all records as exported.
   * @param array $log_args
   *   Any values to be used in the final log message passed on from preparation
   *   before the batch began.
   * @param array $context
   *   Batch context with possible keys:
   *   'sandbox' - contains values that persist through all calls to this op
   *   'results' - contains values to pass to the batch-finished function.
   */
  public static function updateAllReportDataBatchProcess($report_type, array $add_ids, array $update_ids, $export_timestamp, array $log_args, array &$context) {

    // We're in a static function, so grab the service.
    /* @var \Drupal\competition\CompetitionReporter $reporter */
    $reporter = \Drupal::service('competition.reporter');

    if (empty($context['sandbox'])) {

      // Set up log args - taking care not to overwrite them if already in
      // collection from a previous op.
      if (!isset($context['results']['log_args'])) {
        $context['results']['log_args'] = $log_args;
      }

      // Pass along for use in post-run queries.
      $context['results']['export_timestamp'] = $export_timestamp;

      // Total count to be processed.
      $context['sandbox']['total'] = count($add_ids) + count($update_ids);

      // If no IDs - we're done.
      if ($context['sandbox']['total'] == 0) {
        $context['finished'] = 1;
        return;
      }
      // -----------------------------------------------------------------------.
      // Progress counter (not used in results)
      $context['sandbox']['count'] = 0;

      // Number to process per batch.
      $context['sandbox']['per'] = \Drupal::config('competition.settings')->get('batch_size');

      // All IDs to be processed.
      $context['sandbox']['ids_all'] = array_merge($add_ids, $update_ids);

      // Stash report table name and columns for the insert queries.
      if ($report_type == 'judging') {
        $context['sandbox']['report_table'] = static::REPORT_JUDGING_TABLE;
        $context['sandbox']['report_table_cols'] = $reporter->getReportJudgingTableColumns();
      }
      else {
        $context['sandbox']['report_table'] = static::REPORT_TABLE;
        $context['sandbox']['report_table_cols'] = $reporter->getReportTableColumns();
      }
    }

    $ids = array_slice($context['sandbox']['ids_all'], $context['sandbox']['count'], $context['sandbox']['per']);

    // The real work!
    if ($report_type == 'judging') {
      $rows = $reporter->flattenEntitiesJudging($ids);
    }
    else {
      $rows = $reporter->flattenEntities($report_type, $ids);
    }

    // Now insert to the report data table.
    // Note: rows for IDs to be updated have already been deleted, so we can
    // use one insert query for all, regardless.
    $insert = $reporter->dbConnection->insert($context['sandbox']['report_table']);

    $insert->fields($context['sandbox']['report_table_cols']);

    foreach ($rows as $row) {
      $row['exported'] = $export_timestamp;
      $insert->values($row);
    }

    // "If the query was given multiple sets of values to insert,
    // the return value is undefined.".
    $insert->execute();

    // Increment count by number of IDs processed, not number of rows -
    // for judging data, there are likely multiple rows per entry ID.
    $context['sandbox']['count'] += count($ids);

    // Update progress.
    $context['finished'] = $context['sandbox']['count'] / $context['sandbox']['total'];
  }

  /**
   * Batch completion handler for adding/updating report data records.
   *
   * @param bool $success
   *   TRUE if no PHP fatals.
   * @param array $results
   *   The $context['results'] array built during operation callbacks.
   * @param array $operations
   *   Batch operations.
   */
  public static function updateAllReportDataBatchFinished($success, array $results, array $operations) {
    if ($success) {

      $log_args = $results['log_args'];

      /* @var \Drupal\competition\CompetitionReporter $reporter */
      $reporter = \Drupal::service('competition.reporter');

      // Collect post-run numbers.
      // Entries:
      $query_count_entries = $reporter->dbConnection->select($reporter::REPORT_TABLE, 'rd')
        ->fields('rd', ['ceid'])
        // ->isNotNull('rd.ceid')
        ->condition('rd.ceid', 0, '<>');

      $query_count_entries_this = clone $query_count_entries;
      $query_count_entries_this->condition('rd.exported', $results['export_timestamp']);

      $log_args['@count_total_competition_entry'] = $query_count_entries
        ->countQuery()->execute()->fetchField();

      $log_args['@count_this_run_competition_entry'] = $query_count_entries_this
        ->countQuery()->execute()->fetchField();

      // Users:
      $query_count_users = $reporter->dbConnection->select($reporter::REPORT_TABLE, 'rd')
        ->fields('rd', ['uid'])
        // ->isNull('rd.ceid')
        ->condition('rd.ceid', 0, '=');

      $query_count_users_this = clone $query_count_users;
      $query_count_users_this->condition('rd.exported', $results['export_timestamp']);

      $log_args['@count_total_user'] = $query_count_users
        ->countQuery()->execute()->fetchField();

      $log_args['@count_this_run_user'] = $query_count_users_this
        ->countQuery()->execute()->fetchField();

      // Judging:
      // Note: DISTINCT gives us the number of entries for which there are
      // records, not the total number of records.
      $query_count_judging = $reporter->dbConnection->select($reporter::REPORT_JUDGING_TABLE, 'rd')
        ->fields('rd', ['ceid'])
        ->distinct();

      $query_count_judging_this = clone $query_count_judging;
      $query_count_judging_this->condition('rd.exported', $results['export_timestamp']);

      $log_args['@count_total_judging_ids'] = $query_count_judging
        ->countQuery()->execute()->fetchField();

      $log_args['@count_this_run_judging_ids'] = $query_count_judging_this
        ->countQuery()->execute()->fetchField();

      // Log - full details to logger, summary as Drupal message.
      $reporter->updateAllReportDataLog($log_args, [
        'log' => [
          'details' => TRUE,
          'always' => FALSE,
        ],
        'drupal_message' => [
          'details' => FALSE,
          'always' => TRUE,
        ],
      ]);

    }
    else {
      drupal_set_message($this->t('Error updating report data.'), 'error');
    }
  }

  /**
   * Update all report data.
   *
   * Helper to log and/or output messages for the results of updating all
   * report data.
   *
   * @param array $log_args
   *   Array of counts of records/entities affected by the update, formatted
   *   with '@'-prefixed keys for usage as $this->t() args.
   * @param array $instances
   *   Array defining where and how to log.
   *   Top-level keys define where (each is optional):
   *     'log' - dblog logger
   *     'drupal_message' - Drupal status message
   *     'return' - return the untranslated message(s) generated in this method
   *   Each top-level key points to a sub-array with options:
   *     'details' - boolean, TRUE for full detailed message, FALSE for summary
   *     'html' - boolean, TRUE to include HTML in message string, FALSE for
   *     plain text. Defaults TRUE.
   *     'always' - boolean, TRUE to log regardless of whether all counts of
   *     affected report data are zero; FALSE to log only of some count is
   *     more than zero. Defaults FALSE.
   *
   * @return array|null
   *   The message(s), if requested by $instances['return'], otherwise nothing.
   *
   * @see CompetitionReporter::updateAllReportData()
   */
  public function updateAllReportDataLog(array $log_args, array $instances = []) {

    $detailed = NULL;
    $summary = NULL;

    // If only deletions were performed (via direct queries), we may have
    // bypassed batch add/update. In that case, a Drupal message is requested
    // but these add/update totals have not been set.
    $log_args = $log_args + [
      '@count_total_competition_entry' => 0,
      '@count_this_run_competition_entry' => 0,
      '@count_total_user' => 0,
      '@count_this_run_user' => 0,
      '@count_total_judging_ids' => 0,
      '@count_this_run_judging_ids' => 0,
    ];

    $affected = (
         $log_args['@count_this_run_competition_entry'] > 0
      || $log_args['@count_this_run_user'] > 0
      || $log_args['@count_this_run_judging_ids'] > 0
      || $log_args['@count_deleted_competition_entry'] > 0
      || $log_args['@count_deleted_user'] > 0
      || $log_args['@count_deleted_judging_ids'] > 0
      || $log_args['@count_removed_judging_ids'] > 0
    );

    $return = NULL;

    foreach ($instances as $where => $options) {

      if (!isset($options['details'])) {
        continue;
      }

      if (!isset($options['html'])) {
        $options['html'] = TRUE;
      }

      if (!isset($options['always'])) {
        $options['always'] = FALSE;
      }

      // Bypass if nothing was affected and we're not forcing always logging.
      if (!$options['always'] && !$affected) {
        continue;
      }

      $messages = NULL;

      if ($options['details']) {

        if (empty($detailed)) {

          $detailed = [];

          $detailed[] =
          '<strong>Updated all competition reporting data.</strong><br/><br/>

--- Entities in report data, before this run: ---<br/>
Registered users (without entries yet): <strong>@count_init_user</strong><br/>
Entries: <strong>@count_init_competition_entry</strong><br/>
Entries - judging data: <strong>@count_init_judging_ids</strong><br/><br/>

--- Entities deleted from report data, because the entities have been deleted: ---<br/>
Registered users (without entries yet): <strong>@count_deleted_user</strong><br/>
Entries: <strong>@count_deleted_competition_entry</strong><br/>
Entries - judging data: <strong>@count_deleted_judging_ids</strong><br/><br/>

--- To be added/updated: ---<br/>
Registered users (without entries yet) to add: <strong>@count_add_user</strong><br/>
Registered users (without entries yet) to update: <strong>@count_update_user</strong><br/>
Entries to add: <strong>@count_add_competition_entry</strong><br/>
Entries to update: <strong>@count_update_competition_entry</strong><br/>
Entries to add - judging data: <strong>@count_add_judging_ids</strong><br/>
Entries to update - judging data: <strong>@count_update_judging_ids</strong><br/><br/>

--- Entities successfully added/updated/deleted: ---<br/>
Registered users (without entries yet) - records added/updated: <strong>@count_this_run_user</strong><br/>
Entries - records added/updated: <strong>@count_this_run_competition_entry</strong><br/>
Entries - judging data added/updated: <strong>@count_this_run_judging_ids</strong><br/><br/>

Registered users who previously did not have an entry, now have entry - user-only records deleted: <strong>@count_deleted_user_now_entry</strong><br/><br/>

--- Entities in report data, after this run: ---<br/>
Registered users (without entries yet): <strong>@count_total_user</strong><br/>
Entries: <strong>@count_total_competition_entry</strong><br/>
Entries - judging data: <strong>@count_total_judging_ids</strong>';

        }

        $messages = $detailed;

      }
      else {

        if (empty($summary)) {

          // To allow effective translation, each message is kept separate
          // rather than concatenating all into one long string which could be
          // any of multitudinous variants, depending on which counts were
          // nonzero.
          $summary = [];

          $summary[] = '<strong>Updated all competition reporting data:</strong>';

          if ($log_args['@count_this_run_user'] > 0) {
            $summary[] = 'Registered users (without entries yet) - added/updated: <strong>@count_this_run_user</strong>';
          }

          if ($log_args['@count_this_run_competition_entry'] > 0) {
            $summary[] = 'Entries - added/updated: <strong>@count_this_run_competition_entry</strong>';
          }

          if ($log_args['@count_this_run_judging_ids'] > 0) {
            $summary[] = 'Entries - judging data - added/updated: <strong>@count_this_run_judging_ids</strong>';
          }

          if ($log_args['@count_removed_judging_ids'] > 0) {
            $summary[] = 'Entries - judging data - removed entries that are no longer in any judging round: <strong>@count_removed_judging_ids</strong>';
          }

          if ($log_args['@count_deleted_user_now_entry'] > 0) {
            $summary[] = 'Registered users who previously did not have an entry, and now have entry - user-only records deleted: <strong>@count_deleted_user</strong>';
          }

          if ($log_args['@count_deleted_user'] > 0) {
            $summary[] = 'Registered users (without entries yet) - records deleted because entities have been deleted: <strong>@count_deleted_user</strong>';
          }

          if ($log_args['@count_deleted_competition_entry'] > 0) {
            $summary[] = 'Entries - records deleted because entities have been deleted: <strong>@count_deleted_competition_entry</strong>';
          }

          if ($log_args['@count_deleted_judging_ids'] > 0) {
            $summary[] = 'Entries - judging data deleted because entities have been deleted: <strong>@count_deleted_judging_ids</strong>';
          }

        }

        $messages = $summary;

      }

      // Convert to plain text if requested.
      if (!$options['html']) {
        foreach ($messages as &$message) {
          // Remove tags we use.
          // Note that all <br/> tags are followed by plaintext line breaks
          // already, so we can remove them without replacing.
          $message = str_replace(['<strong>', '</strong>', '<br/>'], '', $message);
        }
      }

      switch ($where) {

        case 'log':
          // For the sake of translation (see above), we may create several
          // messages in the log.
          foreach ($messages as $message) {
            $this->logger->notice($message, $log_args);
          }

          break;

        case 'drupal_message':
          foreach ($messages as $message) {
            drupal_set_message(FormattableMarkup::placeholderFormat($message, $log_args));
          }

          break;

        case 'return':
          $return = $messages;

          break;

      }

    }

    return $return;

  }

  /**
   * Generate report set batch.
   *
   * Sets up batch process to pull records from `competition_entry_report_data`
   * database table and output to CSV report file.
   *
   * @param array $params
   *   Various parameters by which to filter the report data.
   *   'entity' (optional) - if provided and value is 'user',
   *     retrieve all users-without-entries;
   *     otherwise assumes competition entries.
   *   'type' (required) - competition_entry type/bundle - report must include
   *     only one type, because each type likely has different fields, and
   *     fields are the CSV columns.
   *   'cycle' (optional) - competition_entry.cycle
   *   'status' (optional) - competition_entry.status
   *   'ceid' (optional) - a single competition_entry ID.
   *
   * @throws \InvalidArgumentException
   *   Thrown if any required parameters are missing or given parameter values
   *   are invalid.
   *
   * @throws \Exception
   *   Thrown if some error occurs preparing the file directory in which the
   *   CSV file would be saved.
   *
   * @see CompetitionEntryController::generateReport()
   */
  public function generateReportSetBatch(array $params) {

    $report_type = (!empty($params['report_type']) ? $params['report_type'] : 'competition_entry');

    $entity_type = ($report_type == 'user' ? 'user' : 'competition_entry');

    if (!in_array($report_type, [
      'competition_entry',
      'user',
      'judging',
      'combined',
    ])) {

      drupal_set_message($this->t('Cannot generate report - report type must be competition entries, registered users who don\'t yet have entries, judging data, or a combination of competition entries and their judging data.'), 'error');

      throw new \InvalidArgumentException('Query parameter "report_type" was given as "' . $report_type . '"; must be one of "competition_entry", "user", "judging", or "combined".');

    }

    // Validate other parameters according to report type.
    $required_params = [];
    $valid_params = [];

    if ($entity_type == 'competition_entry') {

      $required_params = [
        'type',
      ];

      $valid_params = array_merge($required_params, [
        'cycle',
        'status',
        'ceid',
      ]);

      // Judging reports are also pulling (only) entries, so all entry filters
      // apply. Add in judging-specific filters.
      if ($report_type == 'judging' || $report_type == 'combined') {

        // Note: most likely it makes sense to filter judging data to a single
        // cycle... but it's not technically required.
        $required_params = array_merge($required_params, [
          'judging_report',
          'round_id',
        ]);

        $valid_params = array_merge($valid_params, $required_params, []);

      }

    }
    elseif ($entity_type == 'user') {

      // Currently we don't support any filters on users-without-entries report.
    }

    // Filter params to valid keys.
    $params = array_intersect_key($params, array_flip($valid_params));

    // Check that all required params are present.
    $missing = array_diff_key(array_flip($required_params), $params);
    if (!empty($missing)) {

      drupal_set_message($this->t('Cannot generate report - one or more required filter value(s) were not provided: %missing', [
        '%missing' => implode(', ', array_keys($missing)),
      ]), 'error');

      throw new \InvalidArgumentException('Missing required filter parameters: ' . implode(', ', $missing));

    }

    // Validate (and tweak) param values
    // TODO? drupal_set_message() calls might belong better in controller
    // catch blocks - but we don't have distinguishing exceptions currently.
    foreach ($params as $k => $v) {
      if ($entity_type == 'competition_entry') {
        switch ($k) {

          // Basic entry filters.
          case 'type':
            // Type should be the id of a competition entity.
            $competition = $this->entityTypeManager->getStorage('competition')->load($v);
            if (empty($competition)) {

              drupal_set_message($this->t('Cannot generate report - filter value %v is not a configured competition.', [
                '%v' => $v,
              ]), 'error');

              throw new \InvalidArgumentException('The given value for "type" parameter, "' . $v . '", is not a competition entry type (competition ID).');

            }
            break;

          case 'cycle':
            $cycle_keys = array_keys($this->competitionSettings->get('cycles'));
            if (!in_array($v, $cycle_keys)) {

              drupal_set_message($this->t('Cannot generate report - filter value %v is not one of the configured competition cycles.', [
                '%v' => $v,
              ]), 'error');

              throw new \InvalidArgumentException('The given value for "cycle" parameter, "' . $v . '", is not one of the defined competition cycles.');

            }
            break;

          case 'status':

            // Status, as stored on entry, is an integer, but has corresponding
            // human-readable string label. Allow the param to be either an
            // integer key or string label.
            // Note that the report query condition will be the string label, as
            // that's what is stored in the report data table.
            $statuses = $this->competitionSettings->get('statuses');

            // Cast numeric string to int, in case it's meant to be status key.
            if (is_string($v) && is_numeric($v)) {
              $v = (int) $v;
            }

            // Convert a valid integer status key to corresponding label.
            if (!empty($statuses[$v])) {
              $v = $statuses[$v];
            }

            // Now check if it's one of the labels. Note strict type check.
            if (!in_array($v, $statuses, TRUE)) {

              drupal_set_message($this->t('Cannot generate report - filter value %v is not one of the configured competition entry statuses.', [
                '%v' => $v,
              ]), 'error');

              throw new \InvalidArgumentException('The given value for "status" parameter, "' . $v . '", is not one of the defined competition entry statuses.');

            }

            // If valid, store back into $params.
            $params['status'] = $v;

            break;

          case 'ceid':
            // Validate just for a positive integer which could be a legitimate
            // entry ID. If it's not, the query will simply come back with no
            // results and early-return.
            // Cast to int - non-numeric strings cast to 0.
            $params['ceid'] = (int) $params['ceid'];
            if ($params['ceid'] < 1) {

              drupal_set_message($this->t("Cannot generate report - %v is not a valid competition entry ID by which to filter.", [
                '%v' => $v,
              ]), 'error');

              throw new \InvalidArgumentException("The given value for 'ceid' parameter, '" . $v . "', cannot be a valid competition entry ID.");

            }
            break;

          // Judging filters.
          case 'judging_report':
            if (!in_array($v, $this->getAllJudgingReportKeys())) {

              drupal_set_message($this->t("Cannot generate report - %v is not an available judging report.", [
                '%v' => $v,
              ]), 'error');

              throw new \InvalidArgumentException("The given value for 'judging_report' parameter, " . $v . "', is not a valid judging report key.");

            }

            break;

          case 'round_id':
            // All round IDs are positive integers.
            // Cast to int - non-numeric strings cast to 0.
            $params['round_id'] = (int) $params['round_id'];

            $competition = (!empty($params['type']) ? $this->entityTypeManager->getStorage('competition')->load($params['type']) : NULL);

            // Check that configured round exists.
            $round_exists = NULL;
            if (!empty($competition)) {
              $round_exists = FALSE;
              $judging = $competition->getJudging();
              foreach (array_keys($judging->rounds) as $round_id) {
                if ((int) $round_id === $params['round_id']) {
                  $round_exists = TRUE;
                  break;
                }
              }
            }

            if ($params['round_id'] < 1 || $round_exists === FALSE) {

              drupal_set_message($this->t("Cannot generate report - there is no configured judging round %v in this competition.", [
                '%v' => $v,
              ]), 'error');

              throw new \InvalidArgumentException("The given value for 'round_id' parameter, '" . $v . "', is not a valid round ID in this competition.");

            }

            // Check that report is applicable to the round.
            if (!empty($competition)) {
              if (!$this->judgingReportAppliesToRound($competition->id(), $params['round_id'], $params['judging_report'])) {

                drupal_set_message($this->t("Cannot generate report - %report does not apply to Round @round_id, because of its round type.", [
                  '%report' => $this->getAllJudgingReports()[$params['judging_report']],
                  '@round_id' => $params['round_id'],
                ]), 'error');

                throw new \InvalidArgumentException("The given values for parameters are incompatible: 'judging_report' value '" . $params['judging_report'] . "' and 'round_id' value '" . $params['round_id'] . "'. This report does not apply to the round due to round type.");

              }
            }

            break;

        }
      }
      elseif ($entity_type == 'user') {

      }
    }

    // Prepare the output directory and file.
    $dirpath = 'private://competition/reports';
    if (!file_prepare_directory($dirpath, FILE_CREATE_DIRECTORY)) {

      drupal_set_message($this->t("Error preparing file directory %dirpath to store CSV report file. This is likely a permissions issue.", [
        '%dirpath' => $dirpath,
      ]), 'error');

      // TODO: custom exception class? Not sure what might be appropriate.
      throw new \Exception("Error creating or setting permissions on file directory in which to save report file - " . $dirpath);

    }

    $report_name = '';
    switch ($report_type) {
      case 'user':
        $report_name = 'users-without-entries';
        break;

      case 'competition_entry':
        $report_name = 'entries';
        break;

      case 'judging':
      case 'combined':
        $report_name .= 'judging-' . str_replace('_', '-', $params['judging_report']);
        break;
    }
    $filename = 'report-' . $report_name . '-' . REQUEST_TIME . '.csv';
    $filepath = $dirpath . '/' . $filename;

    // Reorganize params.
    $params_judging = [
      'judging_report',
      'round_id',
    ];
    $params_batch = [
      'filters' => array_diff_key($params, array_flip($params_judging)),
    ];
    if ($report_type == 'judging' || $report_type == 'combined') {
      $params_batch['judging'] = array_intersect_key($params, array_flip($params_judging));
      $params_batch['judging']['report'] = $params_batch['judging']['judging_report'];
      unset($params_batch['judging']['judging_report']);
    }

    // Define the batch.
    $batch = [
      'title' => $this->t("Generating report..."),
      'operations' => [
        [
          // Callback.
          [static::class, 'generateReportBatchWriteCsv'],
          // Arguments to pass to callback.
          [
            $report_type,
            $params_batch,
            $filepath,
          ],
        ],
      ],
      'finished' => [static::class, 'generateReportBatchFinished'],
      // 'file' =>.
    ];

    batch_set($batch);

    // Pass the destination to which to redirect after completion.
    // This does not accept a route name, apparently. :(.
    return batch_process(Url::fromRoute('entity.competition_entry.reports'));

  }

  /**
   * Batch operation function.
   *
   * Filtering by given params, pull records from
   * `competition_entry_report_data` and write to CSV file.
   *
   * @param string $report_type
   *   Specifies base kind of report; one of:
   *   'competition_entry' - general report of competition_entry entities
   *   'user' - general report of user accounts without associated entries
   *   'judging' - report of some specific judging data for competition entries
   *   'combined' - full competition entry report plus some judging data. This
   *     is mainly defined/handled by judging report methods here.
   * @param array $params
   *   Various parameters to define the desired report. All should be validated
   *   by the calling code which sets up the batch.
   *   $params['filters']:
   *     Validated parameters by which to filter records of that entity type:
   *     'type' (present if $entity_type == competition_entry) - type/bundle
   *       of competition_entry
   *     'cycle' (optional, if $entity_type == competition_entry)
   *     'status' (optional, if $entity_type == competition_entry)
   *     'ceid' (optional, if $entity_type == competition_entry) - a single
   *       entry ID
   *   $params['judging']:
   *     If $report_type == 'judging', some metadata to define the particular
   *     judging report.
   *     'report' - custom key to designate desired judging report
   *     'round_id' - round for which to pull judging data.
   * @param string $filepath
   *   Path to CSV file to which to write.
   * @param array $context
   *   Batch context with possible keys:
   *   'sandbox' - contains values that persist through all calls to this op
   *   'results' - contains values to pass to the batch-finished function.
   */
  public static function generateReportBatchWriteCsv($report_type, array $params, $filepath, array &$context) {

    // Set entity type based on report type.
    $entity_type = ($report_type == 'user' ? 'user' : 'competition_entry');

    // We're in a static method - get the container's instance of
    // CompetitionReporter.
    /* @var \Drupal\competition\CompetitionReporter $reporter */
    $reporter = \Drupal::service('competition.reporter');

    $init = empty($context['sandbox']);

    $select = NULL;
    if ($report_type == 'judging' || $report_type == 'combined') {

      // Get query on judging report table via helper method.
      $judging_filters = $params['filters'];

      if (isset($judging_filters['status'])) {
        unset($judging_filters['status']);
      }

      $judging_filters['round_id'] = $params['judging']['round_id'];

      $select = $reporter->getJudgingReportQuery($params['judging']['report'], $judging_filters);

      // If doing a combined report, join the non-judging reporting data table
      // and add all its fields.
      if ($report_type == 'combined') {
        $select->innerJoin(static::REPORT_TABLE, 'erd', 'erd.ceid = rd.ceid');
        $select->fields('erd');
      }

    }
    else {

      // Set up query on report data table.
      $select = Database::getConnection()->select(static::REPORT_TABLE, 'rd')
        ->fields('rd');

      // Apply entity type condition and sort by ceid or uid, accordingly.
      if ($entity_type == 'competition_entry') {

        if (!empty($params['filters']['ceid'])) {
          $select->condition('rd.ceid', $params['filters']['ceid']);
          unset($params['filters']['ceid']);
        }
        else {
          // $select->isNotNull('rd.ceid');.
          $select->condition('rd.ceid', 0, '<>');
        }

        $select->orderBy('rd.ceid', 'ASC');

      }
      elseif ($entity_type == 'user') {

        // $select->isNull('rd.ceid');.
        $select->condition('rd.ceid', 0, '=');

        $select->orderBy('rd.uid', 'ASC');

      }

      // Apply all other filter conditions.
      if (!empty($params['filters'])) {
        foreach ($params['filters'] as $k => $v) {
          $select->condition('rd.' . $k, $v);
        }
      }

    }

    // Initialization - end batch / early return if no records found.
    if ($init) {

      $context['results']['report_type'] = $report_type;

      if ($report_type == 'judging' || $report_type == 'combined') {
        $context['results']['judging']['report'] = $params['judging']['report'];
        $context['results']['judging']['report_label'] = (string) $reporter->getAllJudgingReports()[$params['judging']['report']];
      }

      $context['results']['entity_type'] = $entity_type;

      $context['results']['filepath'] = $filepath;

      // Track number of records processed.
      $context['results']['count'] = 0;

      // Track entries/judges processed.
      if ($report_type == 'judging' || $report_type == 'combined') {
        $context['results']['judging']['entries_seen'] = [];
        $context['results']['judging']['judges_seen'] = [];
      }

      // Get total number of records to process.
      $select_count = clone $select;
      $select_count = $select_count->countQuery();
      $context['sandbox']['total'] = (int) $select_count->execute()->fetchField();

      // If no records found with the given filters, end the batch now.
      if ($context['sandbox']['total'] === 0) {
        $context['finished'] = 1;
        return;
      }
      // -----------------------------------------------------------------------.
      // Retrieve columns/labels for this report.
      $cols_judging = NULL;
      $cols_entity = NULL;

      if ($report_type == 'judging' || $report_type == 'combined') {
        $cols_judging = $reporter->getJudgingReportColumns($params['judging']['report'], $params['judging']['round_id']);
      }

      if ($report_type == 'competition_entry' || $report_type == 'user' || $report_type == 'combined') {
        $bundle = ($entity_type == 'competition_entry' ? $params['filters']['type'] : NULL);
        $cols_entity = $reporter->getEntityReportFieldsMeta($entity_type, $bundle)['field_labels_all'];

        // Don't include `data` serialized blob as its own column.
        if ($entity_type == 'competition_entry' && isset($cols_entity['data'])) {
          unset($cols_entity['data']);
        }
      }

      switch ($report_type) {
        case 'competition_entry':
        case 'user':
          $context['sandbox']['columns'] = $cols_entity;
          break;

        case 'judging':
          $context['sandbox']['columns'] = $cols_judging;
          break;

        case 'combined':
          // This should be in all 'combined' reports; kind of what designates
          // them as combined...
          if (isset($cols_judging['entities'])) {
            unset($cols_judging['entities']);
          }

          $context['sandbox']['columns'] = array_merge($cols_entity, $cols_judging);
          break;
      }

      // Get number of records to process per run.
      $context['sandbox']['per'] = \Drupal::config('competition.settings')->get('batch_size');

      // Load stuff needed by judging reports.
      if ($report_type == 'judging' || $report_type == 'combined') {

        $context['sandbox']['judging'] = [];

        $competition = $reporter->entityTypeManager->getStorage('competition')->load($params['filters']['type']);
        $rounds_config = $competition->getJudging()->rounds;
        $round_config = $rounds_config[$params['judging']['round_id']];

        // Get round type.
        $context['sandbox']['judging']['round_type'] = $round_config['round_type'];

        // Get/format criteria labels for this round.
        $context['sandbox']['judging']['criteria_labels'] = [];
        $i = 0;
        foreach ($round_config['weighted_criteria'] as $label => $weight) {
          $context['sandbox']['judging']['criteria_labels']['c' . $i] = $label . ' (' . $weight . '%)';
          $i++;
        }

        // Stash all round config, for any reports with multiple rounds' data.
        $context['sandbox']['judging']['rounds_config'] = $rounds_config;

      }

    }

    // Open file handle.
    $handle = fopen($filepath, 'a+');

    // Attempt to get exclusive file-writing lock - early-return on failure.
    if (!flock($handle, LOCK_EX)) {
      fclose($handle);

      // (No DI...unfortunately we have to get an instance of this class.)
      /* @var \Drupal\competition\CompetitionReporter $competitionReporter */
      $competitionReporter = \Drupal::service('competition.reporter');

      $competitionReporter->logger->error("Error generating report - unable to lock file %filepath for exclusive writing.", [
        '%filepath' => $filepath,
      ]);

      drupal_set_message($this->t("Error generating report - unable to write to CSV file at %filepath.", [
        '%filepath' => $filepath,
      ]), 'error');

      $context['finished'] = 1;
      return;
    }
    // ---------------------------------------------------------------.
    // On first run, output column labels as first row.
    if ($init) {
      fputcsv($handle, array_values($context['sandbox']['columns']));
    }

    // Retrieve and process next batch of records.
    $select->range($context['results']['count'], $context['sandbox']['per']);

    $result = $select->execute();

    while (($row = $result->fetchAssoc()) !== FALSE) {

      if ($report_type != 'judging' && isset($row['data'])) {

        // Unpack `data` column JSON blob; merge into row so we have one array
        // of all entity field values.
        // NOTE: this restores $row['data'] as unserialized $entry->data.
        $json = $row['data'];
        $json_data = Json::decode($json);

        if ($json_data !== NULL) {
          $row = array_merge($row, $json_data);
        }
        else {
          // Json::decode() just wraps PHP's json_decode() - returns NULL if the
          // string cannot be decoded.
          $reporter->logger->error("Error retrieving report data for %entity_type %entity_id - could not decode JSON string containing field values.<br/><br/>JSON error message:<br/>%json_error<br/><br/>JSON string:<br/><pre>%json</pre>", [
            '%entity_type' => str_replace("_", " ", $entity_type),
            '%entity_id' => ($entity_type == 'competition_entry' ? $row['ceid'] : $row['uid']),
            '%json_error' => json_last_error_msg(),
            '%json' => $json,
          ]);
        }

      }

      $csv_rows = [];

      $entity_row = NULL;
      $judging_rows = NULL;

      // Get judging row(s).
      if ($report_type == 'judging' || $report_type == 'combined') {

        $judging_rows = static::generateReportRowsJudging($params['judging'], $row, $context);

      }

      // Entity field values row.
      if ($report_type == 'competition_entry' || $report_type == 'user' || $report_type == 'combined') {

        // Initialize row by setting column keys and defaulting all values to
        // empty strings.
        $entity_row = array_combine(
          array_keys($context['sandbox']['columns']),
          array_fill(0, count($context['sandbox']['columns']), '')
        );

        // Populate csv row with all values that exist.
        foreach ($entity_row as $field_key => $value_output) {
          if (isset($row[$field_key])) {
            $entity_row[$field_key] = $row[$field_key];
          }
        }

      }

      switch ($report_type) {
        case 'competition_entry':
        case 'user':
          $csv_rows[] = $entity_row;
          break;

        case 'judging':
          $csv_rows = $judging_rows;
          break;

        case 'combined':
          // For combined report - only output the row if a single, non-empty
          // judging row was generated.
          // (The 'master' report uses joins that generate multiple rows per
          // entry; we only want one row, to merge with the row of entity field
          // values. Judging row generation handles filtering out extraneous
          // rows.)
          // @see generateReportRowsJudging()
          if (count($judging_rows) == 1) {
            $csv_rows[] = array_merge($entity_row, $judging_rows[0]);
          }

          break;
      }

      // Write row(s) to file.
      if (!empty($csv_rows)) {
        foreach ($csv_rows as $csv_row) {
          fputcsv($handle, array_values($csv_row));
        }
      }

      $context['results']['count']++;

    }

    flock($handle, LOCK_UN);
    fclose($handle);

    // Update progress.
    $context['finished'] = $context['results']['count'] / $context['sandbox']['total'];

  }

  /**
   * Generate report rows.
   *
   * Given a row from the report table, generate rows for a particular judging
   * report to be output to CSV.
   *
   * @param array $params
   *   Possible keys:
   *   'report' - custom key to designate desired judging report
   *   'round_id' - round for which to pull judging data.
   * @param array $data_row
   *   A single record from the judging report data table.
   * @param array $context
   *   The context array from the batch process, by reference.
   *   $context['sandbox']['judging'] contains:
   *   'round_type' - 'pass_fail', 'criteria'
   *   'criteria_labels' - array of criteria key (e.g. 'c0') => label for
   *     output
   *   $context['results']['judging'] contains:
   *   'count_entries' - track number of entries processed for this report,
   *   if relevant
   *   'count_judges' - track number of judges processed for this report,
   *   if relevant.
   *
   * @return array
   *   An array of arrays, each to be output as a row to the CSV.
   */
  protected static function generateReportRowsJudging(array $params, array $data_row, array &$context) {

    /* @var \Drupal\competition\CompetitionReporter $reporter */
    $reporter = \Drupal::service('competition.reporter');

    $report = $params['report'];
    $round_id = $params['round_id'];

    $criteria_labels = $context['sandbox']['judging']['criteria_labels'];
    $round_type = $context['sandbox']['judging']['round_type'];
    $rounds_config = $context['sandbox']['judging']['rounds_config'];

    // Decode col values stored as JSON.
    $json_cols = [
      'score',
      'log',
    ];

    if (in_array($report, ['master'])) {
      for ($r = 0; $r <= $round_id; $r++) {
        $json_cols[] = 'round_' . $r . '_score';
      }
    }

    foreach ($json_cols as $col) {
      if (!empty($data_row[$col])) {
        $data_row[$col] = Json::decode($data_row[$col]);

        // Error decoding JSON.
        if ($data_row[$col] === NULL) {
          // TODO: logger.
          // TODO: adjust message by report....ugh.
          drupal_set_message($this->t("Entry @ceid: unable to parse %col value in judging report data table. All data values therein have been left blank in the report.", [
            '@ceid' => $data_row['ceid'],
            '%col' => $col,
          ]));
        }
      }
    }

    // Get the column keys for this report.
    $cols = array_keys($reporter->getJudgingReportColumns($report, $round_id));

    // Template to indicate missing data.
    $empty_row = array_combine(
      $cols,
      array_fill(0, count($cols), '')
    );

    // Template to indicate that some data is not yet present, but that that is
    // accurate, not an error - e.g. judge has not submitted a score yet.
    // ("na" = not applicable)
    $value_na = "-";
    $na_row = array_combine(
      $cols,
      array_fill(0, count($cols), $value_na)
    );

    // Generate one or more rows for this entry.
    $rows = [];

    // Populate "master" data - these values are the same throughout all rows
    // for this entry.
    $cols_master = [
      'ceid',
      'user_name',
      'judge_name',
      'score.weighted_points',
      'round.display_average',
      'round.display_total_weighted_points',
      'score_finalized',
      'votes',
    ];

    $row_master = [];

    foreach (array_intersect($cols, $cols_master) as $key) {
      $row_master[$key] = '';

      if (array_key_exists($key, $data_row)) {

        switch ($key) {
          case 'score_finalized':
            // Translate here during report generation, to potentially output
            // in current user's language.
            $row_master['score_finalized'] = $data_row['score_finalized'] ? $this->t("Yes") : $this->t("No");
            break;

          case 'judge_name':
            if (!empty($data_row['judge_name'])) {
              $row_master[$key] = $data_row['judge_name'];
            }
            elseif (!empty($data_row['score']['uid'])) {
              // If uid is non-zero but user was not loaded, output note that
              // (we assume) user was deleted.
              $row_master[$key] = $this->t("Deleted user (user ID: @uid)", ['@uid' => $data_row['score']['uid']]);
            }
            break;

          default:
            $row_master[$key] = $data_row[$key];
            break;
        }

      }
      elseif (strpos($key, 'score.') === 0) {

        // Per-score values.
        if (!empty($data_row['score'])) {
          $subkey = substr($key, 6);
          if (isset($data_row['score'][$subkey])) {
            switch ($subkey) {

              case 'weighted_points':
                // TODO: use a different key for pass/fail round?
                if ($round_type == 'pass_fail') {
                  // Translate here during report generation, to potentially
                  // output in current user's language.
                  $row_master[$key] = ($data_row['score']['pass'] ? $this->t("Pass") : $this->t("Fail"));
                }
                else {
                  $row_master[$key] = $data_row['score'][$subkey];
                }

                break;

              default:
                $row_master[$key] = $data_row['score'][$subkey];
                break;
            }
          }
          else {
            $row_master[$key] = $value_na;
          }
        }

      }
      elseif (strpos($key, 'round.') === 0) {

        // Per-round values
        // These are stored in each row's 'score' col too, because flattened
        // rows don't provide a place for per-entry per-round (but not
        // per-score) values.
        if (!empty($data_row['score'])) {
          $subkey = substr($key, 6);
          if (!empty($data_row['score']['round'][$subkey])) {
            $row_master[$key] = $data_row['score']['round'][$subkey];
          }
          else {
            $row_master[$key] = $value_na;
          }
        }

      }
    }

    // Generate row(s), populating remaining columns according to the specific
    // report.
    switch ($report) {

      case 'master':

        // The query joins the judging report table to itself, to accumulate
        // a 'score' column for each round. Since there are multiple score
        // rows per entry, the joins generate multiple result rows per entry.
        // Each result row is passed to this function, but one row for an entry
        // contains all the data we need. Thus, if we've already seen a row
        // for this entry, SKIP additional rows.
        if (!in_array($data_row['ceid'], $context['results']['judging']['entries_seen'])) {

          // Compile one row for this entry.
          $row = [];

          foreach ($cols as $key) {
            $row[$key] = '';

            if (isset($row_master[$key])) {
              $row[$key] = $row_master[$key];
            }
            else {

              // Extract overall average score for the round.
              // Key contains the round id, e.g. 'round.1.display_average'.
              if (strpos($key, '.') !== FALSE) {
                $subkeys = explode('.', $key);
                if (count($subkeys) == 3 && $subkeys[0] == 'round' && $subkeys[2] == 'display_average') {

                  $r = $subkeys[1];
                  $r_type = $rounds_config[$r]['round_type'];

                  // Alias of the column as selected in the query.
                  // @see getJudgingReportQuery()
                  $query_col = 'round_' . $r . '_score';

                  if (isset($data_row[$query_col]['round']['display_average'])) {
                    // (The 'pass' value should be within 'round', but to handle
                    // report table data before this code change, check for it.
                    if ($r_type == 'pass_fail' && isset($data_row[$query_col]['round']['pass'])) {
                      // Translate here during report generation, to potentially
                      // output in current user's language.
                      $row[$key] = ($data_row[$query_col]['round']['pass'] ? $this->t("Pass") : $this->t("Fail"));
                    }
                    else {
                      $row[$key] = $data_row[$query_col]['round']['display_average'];
                    }
                  }

                }
              }

            }

          }

          $rows[] = $row;

        }

        break;

      case 'scores':

        // Judging report table row == single judge's score.
        // Output row per criterion.
        if (!empty($data_row['score'])) {
          foreach ($data_row['score']['criteria'] as $ckey => $cvalues) {

            $row = [];

            foreach ($cols as $key) {
              $row[$key] = '';

              if (isset($row_master[$key])) {
                $row[$key] = $row_master[$key];
              }
              else {
                switch ($key) {

                  case 'round.criterion_label':
                    $row[$key] = $criteria_labels[$ckey];

                    break;

                  // Per-criterion values.
                  case 'criterion.display_points':
                    $subkey = substr($key, 10);
                    if (isset($cvalues[$subkey])) {

                      // TODO: use a different key for pass/fail round?
                      if ($round_type == 'pass_fail') {
                        // Translate here during report generation, to
                        // potentially output in current user's language.
                        $row[$key] = $cvalues['pass'] ? $this->t("Pass") : $this->t("Fail");
                      }
                      else {
                        $row[$key] = $cvalues[$subkey];
                      }

                    }
                    else {
                      $row[$key] = $value_na;
                    }

                    break;

                  case 'criterion.display_weighted_points':
                  case 'criterion.display_percent':
                    $subkey = substr($key, 10);
                    if (isset($cvalues[$subkey])) {
                      $row[$key] = $cvalues[$subkey];
                    }
                    else {
                      $row[$key] = $value_na;
                    }

                    break;

                }
              }

            }

            $rows[] = $row;
          }
        }
        else {
          // If 'score' is empty, there was a JSON decode error. Output one row,
          // populated with only what data we do have for this entry.
          // (The '+' operator maintains the order of keys in the first array,
          // which uses the column order.)
          $rows[] = $empty_row + $row_master;
        }

        break;

      case 'judge_scores':
        // Report has one row per judge, per entry - ordered primarily by judge,
        // not entry. The query handles that ordering.
        // All columns are handled by master row; just copy over.
        $row = [];
        foreach ($cols as $key) {
          // Note that master row contains something for all keys; it defaults
          // to empty string if a value could not be retrieved due to JSON
          // decode error.
          $row[$key] = $row_master[$key];
        }

        $rows[] = $row;

        break;

      case 'scores_completed':
        $row = [];

        foreach ($cols as $key) {

          if (array_key_exists($key, $row_master)) {
            $row[$key] = $row_master[$key];
          }
          elseif (array_key_exists($key, $data_row)) {
            // Pull in aggregated fields which are added specifically in this
            // report's query.
            // - count_assigned
            // - count_finalized.
            $row[$key] = $data_row[$key];
          }

        }

        $rows[] = $row;

        break;

      case 'votes':
        // One row per entry.
        // All columns are handled by master row; just copy over.
        $row = [];
        foreach ($cols as $key) {
          // Note that master row contains something for all keys; it defaults
          // to empty string if a value could not be retrieved due to JSON
          // decode error.
          $row[$key] = $row_master[$key];
        }

        $rows[] = $row;

        break;

      case 'log':

        if (!empty($data_row['log'])) {
          $date_formatter = \Drupal::service('date.formatter');

          // Output one row per log message.
          // (Order has already been reversed, to most recent first, when
          // storing into judging report table.)
          // @see flattenEntitiesJudging()
          foreach ($data_row['log'] as $log) {

            $row = [];

            foreach ($cols as $key) {

              if (isset($row_master[$key])) {
                $row[$key] = $row_master[$key];
              }
              else {
                switch ($key) {

                  case 'log.user_name':
                    $name = '';
                    if (!empty($log['user_name'])) {
                      $name = $log['user_name'];
                    }
                    elseif (!empty($log['uid'])) {
                      // If user could not be loaded, output note that (we
                      // assume) user was deleted.
                      $name = $this->t("Unknown user (user ID: @uid)", ['@uid' => $log['uid']]);
                    }
                    else {
                      $name = $this->t("System");
                    }

                    $row[$key] = $name;

                    break;

                  case 'log.timestamp':
                    // Convert timestamp to date string here during report
                    // generation, to potentially output in current user's
                    // time zone.
                    // TODO: define a date format for this case, so it may
                    // be customized via admin UI.
                    $row[$key] = $date_formatter->format($log['timestamp'], 'custom', 'm/d/Y H:i');
                    break;

                  case 'log.message':
                    // Translate here during report generation, to potentially
                    // output in current user's language.
                    $row[$key] = (!empty($log['message_args']) ? FormattableMarkup::placeholderFormat($log['message'], $log['message_args']) : $log['message']);
                    break;

                }
              }
            }

            $rows[] = $row;

          }

        }
        else {
          // This case should be impossible - if entry is in a round, then we
          // expect it to have log messages at least of movement into that
          // round. However, for future-proofing in case of disabling or
          // altering of logging, just add a mostly empty row to at least
          // acknowledge that this entry is present in the round.
          $rows[] = $na_row + $row_master;
        }

        break;

    }

    // Update lists of entries/judges processed.
    // Batch-finished handler uses these as applicable per report.
    // (They're also used above to deal with excess rows from joins in
    // 'master' report.)
    if (!empty($data_row['ceid'])) {
      $context['results']['judging']['entries_seen'][] = $data_row['ceid'];
    }
    if (!empty($data_row['judge_name'])) {
      $context['results']['judging']['judges_seen'][] = $data_row['judge_name'];
    }

    return $rows;
  }

  /**
   * Report export batch completion handler.
   *
   * @param bool $success
   *   TRUE if no PHP fatals.
   * @param array $results
   *   The $context['results'] array built during operation callbacks.
   * @param array $operations
   *   Batch operations.
   */
  public static function generateReportBatchFinished($success, array $results, array $operations) {
    if ($success) {
      if (isset($results['count'])) {

        if ($results['count'] == 0) {

          switch ($results['report_type']) {
            case 'competition_entry':
              drupal_set_message($this->t("No competition entries were found with the selected filters."));
              break;

            case 'user':
              drupal_set_message($this->t("No registered users without a corresponding competition entry were found."));
              break;

            case 'judging':
              drupal_set_message($this->t("No competition entries with judging data were found with the selected filters."));
              break;
          }

        }
        else {

          $filename = str_replace('private://competition/reports/', '', $results['filepath']);
          $t_args = [
            '@count' => $results['count'],
            ':file_url' => Url::fromRoute('entity.competition_entry.report_download', [
              'filename' => $filename,
            ])->toString(),
            '@filename' => $filename,
          ];

          $download_text = "Your report can be downloaded here: <a href=\":file_url\">@filename</a>";

          switch ($results['report_type']) {

            case 'competition_entry':
              drupal_set_message(\Drupal::translation()->formatPlural(
                $results['count'],
                "Exported <strong>1</strong> competition entry. " . $download_text,
                "Exported <strong>@count</strong> competition entries. " . $download_text,
                $t_args
              ));
              break;

            case 'user':
              drupal_set_message(\Drupal::translation()->formatPlural(
                $results['count'],
                "Exported <strong>1</strong> user account. " . $download_text,
                "Exported <strong>@count</strong> user accounts. " . $download_text,
                $t_args
              ));
              break;

            case 'judging':
            case 'combined':
              $t_args['@report_label'] = $results['judging']['report_label'];

              switch ($results['judging']['report']) {

                case 'scores':
                case 'log':
                case 'votes':
                case 'master':
                  // Entry count message.
                  $count_entries = count(array_unique($results['judging']['entries_seen']));
                  drupal_set_message(\Drupal::translation()->formatPlural(
                    $count_entries,
                    "Exported <strong>@report_label</strong> for <strong>1</strong> competition entry. " . $download_text,
                    "Exported <strong>@report_label</strong> for <strong>@count</strong> competition entries. " . $download_text,
                    $t_args
                  ));
                  break;

                case 'judge_scores':
                case 'scores_completed':
                  // Judge count message.
                  $count_judges = count(array_unique($results['judging']['judges_seen']));
                  drupal_set_message(\Drupal::translation()->formatPlural(
                    $count_judges,
                    "Exported <strong>@report_label</strong> for <strong>1</strong> judge. " . $download_text,
                    "Exported <strong>@report_label</strong> for <strong>@count</strong> judges. " . $download_text,
                    $t_args
                  ));
                  break;

              }

              break;

          }

        }

      }
    }
    else {
      drupal_set_message($this->t('Error generating report.'), 'error');
    }
  }

}
