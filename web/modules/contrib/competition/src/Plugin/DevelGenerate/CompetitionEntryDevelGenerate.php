<?php

namespace Drupal\competition\Plugin\DevelGenerate;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\competition\CompetitionEntryStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\devel_generate\DevelGenerateBase;

/**
 * Provides a CompetitionEntryDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "competition_entry",
 *   label = @Translation("competition entries"),
 *   description = @Translation("Generate a given number of ompetition entries. Optionally delete current ompetition entries."),
 *   url = "competition_entry",
 *   permission = "administer competition entries",
 *   settings = {
 *     "num" = 50,
 *     "kill" = FALSE,
 *     "pass" = ""
 *   }
 * )
 */
class CompetitionEntryDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * The competition entry storage.
   *
   * @var \Drupal\competition\CompetitionEntryStorageInterface
   */
  protected $competitionEntryStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Tracks UIDs get get used when the option is selected.
   *
   * @var array
   */
  protected $multipleUIDs;

  /**
   * Provide access to competition cycles from entry generation.
   *
   * @var array
   */
  protected $competitionCycles;

  /**
   * Provide access to competition statuses for entry generation.
   *
   * @var array
   */
  protected $competitionStatuses;

  /**
   * Constructs a new UserDevelGenerate object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\competition\CompetitionEntryStorageInterface $entity_storage
   *   The user storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CompetitionEntryStorageInterface $entity_storage, DateFormatterInterface $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->competitionEntryStorage = $entity_storage;
    $this->dateFormatter = $date_formatter;
    $this->multipleUIDs = \Drupal::entityQuery('user')->execute();

    // Load all cycle types used across all competitions.
    $this->competitionCycles = \Drupal::configFactory()
      ->get('competition.settings')
      ->get('cycles');

    // Get all status types used across all competitions.
    $this->competitionStatuses = \Drupal::configFactory()
      ->get('competition.settings')
      ->get('statuses');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity.manager')->getStorage('competition_entry'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   *
   * TODO: Allow age options for competition entries be selectable
   * from a calendar.
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Get all competitions.
    $competitions = \Drupal::entityTypeManager()->getStorage('competition')->loadMultiple();
    $competition_entries = FALSE;
    $competition_options = [];
    $status_options = [];
    $cycle_options = [];
    $type = '';
    $name = '';
    $cycle = '';
    $cycle_key = '';
    $status = '';
    $status_key = '';
    $competition = NULL;
    $cycles = $this->competitionCycles;
    $statuses = $this->competitionStatuses;

    // Store competition types in an #options-friendly format.
    foreach ($competitions as $competition) {
      $machine_name = $competition->id();
      $name = $competition->getLabel();
      if (!empty($machine_name) && !empty($name)) {
        $competition_options[$machine_name] = $name;
      }
    }

    // Store cycles in an #options-friendly format.
    foreach ($cycles as $cycle_key => $cycle) {
      $machine_name = $cycle_key;
      $name = $cycle;
      if (!empty($machine_name) && !empty($name)) {
        $cycle_options[$cycle_key] = $cycle;
      }
    }

    // Store statuses in an #options-friendly format.
    foreach ($statuses as $status_key => $status) {
      $machine_name = $status_key;
      $name = $status;
      if (!empty($machine_name) && !empty($name)) {
        $status_options[$status_key] = $status;
      }
    }

    // Set up #options array for age of competition entries.
    $time_options = [
      1 => $this->t('Now'),
    ];
    foreach ([3600, 86400, 604800, 2592000, 31536000] as $interval) {
      $time_options[$interval] = $this->dateFormatter->formatInterval($interval, 1) . ' ' . $this->t('ago');
    }

    // Set Up form API elements.
    $form['num'] = [
      '#type' => 'number',
      '#title' => $this->t('How many competition entries would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#min' => 0,
    ];

    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete all competition entries before generating new ones.'),
      '#default_value' => $this->getSetting('kill'),
    ];

    $form['competitions'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Which competition(s) should we generate entries for?'),
      '#required' => TRUE,
      '#options' => $competition_options,
    ];

    $form['cycles'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Which cycles should be utilized in each competition?'),
      '#description' => $this->t('No choice will default to a random selection.'),
      '#required' => FALSE,
      '#options' => $cycle_options,
    ];

    $form['statuses'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Which statuses should be utilized in each competition?'),
      '#description' => $this->t('No choice will default to a random selection.'),
      '#required' => FALSE,
      '#options' => $status_options,
    ];

    $form['user_select_method'] = [
      '#type' => 'select',
      '#title' => $this->t('How would you like the associated user for created entries to be selected?'),
      '#options' => [
        'single' => 'Single User',
        'multiple' => 'Multiple Users',
        'anonymous' => 'Assign all entries to Anonymous',
      ],
    ];

    $form['single_user_uid'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#selection_settings' => ['include_anonymous' => FALSE],
      '#title' => $this->t('Single User Selection'),
      '#description' => $this->t('Select a user if you have chosen the "Single User" option.'),
    ];

    $form['time_range_start'] = [
      '#type' => 'select',
      '#title' => $this->t('How old should competition entries be at their oldest?'),
      '#description' => $this->t('Competition entries will span from this selection to the next selection.'),
      '#options' => $time_options,
      '#default_value' => 604800,
    ];

    $form['time_range_end'] = [
      '#type' => 'select',
      '#title' => $this->t('How old should competition entries be at their newest?'),
      '#description' => $this->t('Competition entries will span from the previous selection to this selection.'),
      '#options' => $time_options,
      '#default_value' => 1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * TODO: Need to respect/validate against competition entry limit if there
   * is one. Right now we are only allowing one entry per person.
   * TODO: Implement ceid_referrer.
   */
  protected function generateElements(array $values) {
    $num = $values['num'];
    $kill = $values['kill'];
    $age_start = $values['time_range_start'];
    $age_end = $values['time_range_end'];
    $user_selection_method = $values['user_select_method'];
    $competitions = $values['competitions'];
    $single_user_uid = !empty($values['single_user_uid']) ? $values['single_user_uid'] : FALSE;
    $ids = FALSE;
    $statuses = !empty($values['statuses']) ? $values['statuses'] : $this->competitionStatuses;
    $cycles = !empty($values['cycles']) ? $values['cycles'] : $this->competitionCycles;

    // Delete all competition entries if user chose the option.
    if ($kill) {
      $ids = $this->competitionEntryStorage->getQuery()->execute();
      $competition_entries = $this->competitionEntryStorage->loadMultiple($ids);
      $this->competitionEntryStorage->delete($competition_entries);
      $this->setMessage($this->formatPlural(count($ids), '1 competition entry deleted', '@count competition entries deleted.'));
    }

    if ($num > 0) {
      foreach ($competitions as $competition) {
        $i = 0;
        while ($i < $num) {
          $uid = $this->getUserId($user_selection_method, $single_user_uid);
          $cycle = array_rand($cycles);
          $status = array_rand($statuses);
          $created = rand($age_start, $age_end);
          $updated = rand($created, $age_end);
          $competitionEntry = $this->competitionEntryStorage->create([
            'type' => $competition,
            'cycle' => $cycle,
            'status' => $status,
            'uid' => $uid,
            'weight' => rand(1, 100),
            'created' => $created,
            'changed' => $updated,
            // A flag to let hooks know that this is a generated entity.
            'devel_generate' => TRUE,
          ]);

          // Populate all fields with sample values.
          $this->populateFields($competitionEntry);
          $competitionEntry->save();

          unset($uid, $status, $cycle, $created, $updated);
          unset($competitionEntry);
          $i++;
        }
        unset($i);
      }
    }

    $this->setMessage(
      $this->t('@num_competition_entries created.', [
        '@num_competition_entries' => $this->formatPlural($num, '1 competition entry', '@count competition entries'),
      ])
    );
  }

  /**
   * This helper function tracks and returns the proper UID for entry.
   *
   * @param string $method
   *   The method in which the user id should be selected.
   * @param int $single_uid
   *   The uid of the single user.
   *
   * @return int
   *   The user's uid
   */
  private function getUserId($method = 'anonymous', $single_uid = 0) {
    // Depending on what the user chose in the form decide how to serve a uid.
    switch ($method) {
      case 'single':
        return $single_uid;

      case 'multiple':
        // We need to check to see if there are any UIDs left,
        // if not, return anonymous user ID.
        if (!empty($this->multipleUIDs)) {
          $uid = array_rand($this->multipleUIDs);
          unset($this->multipleUIDs[$uid]);
        }
        else {
          $uid = 0;
        }
        return $uid;

      default:
        return 0;

    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams($args) {
    return [
      'num' => array_shift($args),
      'roles' => drush_get_option('roles') ? explode(',', drush_get_option('roles')) : [],
      'kill' => drush_get_option('kill'),
      'pass' => drush_get_option('pass', NULL),
      'time_range' => 0,
    ];
  }

}
