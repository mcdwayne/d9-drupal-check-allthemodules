<?php

namespace Drupal\backup_permissions\Form;

use Drupal\backup_permissions\BackupPermissionsStorageTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to create Permission backups.
 */
class BackupPermissionsCreateForm extends FormBase {

  use BackupPermissionsStorageTrait;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a BackupPermissionsCreateForm object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'backup_permissions_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $roles = user_role_names();

    $form['backup_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Backup Name'),
      '#description' => $this->t("Provide a label to save the current permissions and restore it in the future."),
      '#size' => 20,
      '#maxlength' => 20,
    );

    $form['roles'] = array(
      '#type' => 'checkboxes',
      '#options' => $roles,
      '#required' => TRUE,
      '#title' => $this->t('Roles To Backup'),
      '#description' => $this->t('Select roles for which you want to Backup permissions.'),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );
    $url = Url::fromRoute('backup_permissions.settings');

    $form['cancel'] = array(
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $url,
      '#attributes' => ['class' => ['button']],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_roles = array();
    // Adding default title.
    $date = $this->dateFormatter->format(time(), 'custom', 'M-d');
    $title = 'Backup-' . $date;

    // Getting selected roles.
    $roles = $form_state->getValue('roles');
    foreach ($roles as $rid => $value) {
      if ($value) {
        $selected_roles[$rid] = $rid;
      }
    }

    // Replacing default title with user defined title, if provided.
    if (!empty($form_state->getValue('backup_title'))) {
      $title = $form_state->getValue('backup_title');
    }

    // Getting permissions of selected roles.
    $backup = backup_permissions_get_data($selected_roles);
    // Serialising permissions state and storing in database.
    $backup = serialize($backup);
    // Save the submitted entry.
    $entry = array(
      'title' => $title,
      'created' => time(),
      'backup' => $backup,
    );
    $return = $this->insert($entry);
    if ($return) {
      $url = Url::fromRoute('backup_permissions.settings');
      $link = Link::fromTextAndUrl($title, $url);

      drupal_set_message($this->t('A backup of the permissions has been created successfully.
     Click @backup to view previous backups.', array('@backup' => $link->toString())));
    }
  }

}
