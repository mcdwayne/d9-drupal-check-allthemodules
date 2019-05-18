<?php

namespace Drupal\contacts\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form that displays duplicates for a single contact and allows exclusions.
 *
 * @package Drupal\contacts\Form
 */
class ContactsDuplicateForm extends FormBase {

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current contact.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $contact;

  /**
   * ContactsDuplicateForm constructor.
   *
   * @param \Drupal\Core\Database\Connection $db
   *   Database.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(Connection $db, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->db = $db;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * Sets the contact used by this form.
   *
   * @param \Drupal\user\UserInterface $contact
   *   The contact.
   */
  public function setContact(UserInterface $contact) {
    $this->contact = $contact;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contacts_duplicates';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $show_excluded = $form_state->getValue(['filters', 'excluded']);
    $exclusions = $this->getExclusions();
    $possible_duplicates = $this->findPossibleDuplicates($exclusions, $show_excluded);

    $form['filters'] = [
      '#tree' => TRUE,
      '#type' => 'container',
    ];
    $form['filters']['excluded'] = [
      '#type' => 'select',
      '#title' => $this->t('Show Excluded'),
      '#options' => [$this->t('No'), $this->t('Yes')],
      '#default_value' => $show_excluded,
      '#description' => $this->t("Matches can be excluded from the results where staff don't think that this match is a real duplicate."),
    ];
    $form['filters']['apply'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
      '#submit' => ['::applyFilters'],
    ];

    $form['table'] = [
      '#type' => 'table',
      '#attributes' => ['class' => ['table', 'table-hover', 'table-striped']],
      '#header' => [
        $this->t('Name'),
        $this->t('Email'),
        $this->t('Address'),
        $this->t('Has login?'),
        $this->t('Excluded'),
        '',
      ],
      '#empty' => $this->t('No duplicates found'),
    ];

    foreach ($possible_duplicates as $duplicate) {
      $merge_link = '';

      // Only generate the merge link if entity_merge is installed.
      if ($this->moduleHandler->moduleExists('entity_merge')) {
        $merge_link = Link::createFromRoute($this->t('Merge'), 'entity.entity_merge_request.verify', [
          'entity_type' => 'user',
          'primary_id' => $this->contact->id(),
          'secondary_id' => $duplicate->id(),
        ])->toString();
      }

      $form['table'][$duplicate->id()]['name'] = [
        '#markup' => $duplicate->toLink()->toString(),
      ];
      $form['table'][$duplicate->id()]['email'] = [
        '#plain_text' => $duplicate->getEmail(),
      ];

      $form['table'][$duplicate->id()]['address'] = [
        '#plain_text' => $this->formatAddress($duplicate->profile_crm_indiv->entity->crm_address),
      ];

      $form['table'][$duplicate->id()]['has_login'] = [
        '#markup' => $duplicate->getUsername() ? $this->t('Yes') : $this->t('No'),
      ];

      $form['table'][$duplicate->id()]['excluded'] = [
        '#type' => 'checkbox',
        // Blank label hack. Needs a label to be aligned properly.
        '#title' => '',
        '#default_value' => in_array($duplicate->id(), $exclusions),
      ];

      $form['table'][$duplicate->id()]['merge'] = [
        '#markup' => $merge_link,
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save Exclusions'),
        '#button_type' => 'primary',
        '#submit' => ['::submitForm'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $exclusions = [];

    foreach ($form_state->getValue('table') as $user_id => $row) {
      if ($row['excluded']) {
        $exclusions[] = $user_id;
      }
    }

    $this->saveExclusions($exclusions);
    $this->messenger()->addMessage($this->t('Exclusions saved.'));
  }

  /**
   * Run when the apply filter button is clicked.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function applyFilters(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Finds possible duplicates.
   *
   * @param array $exclusions
   *   Array of IDs excluded from deduplication.
   * @param bool $show_excluded
   *   Whether the excluded users should be included in the results.
   *
   * @return \Drupal\user\Entity\User[]
   *   Array of users.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function findPossibleDuplicates(array $exclusions, $show_excluded) {
    $profile = $this->contact->profile_crm_indiv->entity;

    if (!$profile || !$profile->crm_name || !$profile->crm_address) {
      return [];
    }

    $first_name = $profile->crm_name->given;
    $last_name = $profile->crm_name->family;
    $postcode = $profile->crm_address->postal_code;

    // Find any with same last name, same post code and same first 2
    // letters of first name. Don't use full first name, as this won't catch
    // Sam vs Samantha etc on the duplicate.
    if ($first_name && $last_name && $postcode) {
      $query = $this->db->select('profile', 'p');
      $query->join('profile__crm_name', 'n', 'n.entity_id = p.profile_id');
      $query->join('profile__crm_address', 'a', 'a.entity_id = p.profile_id');
      $query->leftJoin('contacts_duplicate_exclusions', 'ex', 'p.uid = ex.uid2 and ex.uid1 = :primary_id', [':primary_id' => $this->contact->id()]);
      $query->condition('p.uid', $this->contact->id(), '<>');
      $query->condition('p.type', 'crm_indiv');
      $query->condition('n.crm_name_family', $last_name);

      // Match on postcode, but replace spaces and trim in case there are
      // formatting differences between the 2 records.
      $query->where("TRIM(REPLACE(a.crm_address_postal_code, ' ', '')) = :postcode", [
        ':postcode' => trim(str_replace(' ', '', $postcode)),
      ]);

      // Match on only first 2 chars of name. Handles differences eg Samantha
      // vs Sam.
      $query->where('LEFT(crm_name_given, 2) = :first', [
        ':first' => substr($first_name, 0, 2),
      ]);

      if (!$show_excluded && count($exclusions)) {
        $query->condition('p.uid', $exclusions, 'NOT IN');
      }

      $query->fields('p', ['uid']);

      $user_ids = $query->execute()->fetchCol();
      $users = $this->entityTypeManager->getStorage('user')
        ->loadMultiple($user_ids);

      return $users;
    }

    return [];
  }

  /**
   * Format address for display.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $address
   *   Address.
   *
   * @return string
   *   Formatted address.
   */
  private function formatAddress(FieldItemListInterface $address) {
    $to_format = array_filter([
      $address->address_line1,
      $address->address_line2,
      $address->locality,
      $address->administrative_area,
      $address->postal_code,
    ]);

    return implode(', ', $to_format);
  }

  /**
   * Gets the IDs to exclude from the duplicate list.
   *
   * @return array
   *   Entity IDs to exclude.
   */
  private function getExclusions() {
    $q = $this->db->select('contacts_duplicate_exclusions', 'e');
    $q->addField('e', 'uid2');
    $q->condition('uid1', $this->contact->id());
    return $q->execute()->fetchCol();
  }

  /**
   * Saves the exclusions.
   *
   * @param array $exclusions
   *   Array of user IDs to exclude.
   */
  private function saveExclusions(array $exclusions) {
    $q = $this->db->delete('contacts_duplicate_exclusions');
    $q->condition('uid1', $this->contact->id());
    $q->execute();

    foreach ($exclusions as $exclusion) {
      $this->db->insert('contacts_duplicate_exclusions')
        ->fields(['uid1' => $this->contact->id(), 'uid2' => $exclusion])
        ->execute();
    }
  }

}
