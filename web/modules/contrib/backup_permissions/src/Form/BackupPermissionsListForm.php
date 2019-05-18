<?php

namespace Drupal\backup_permissions\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\backup_permissions\BackupPermissionsStorageTrait;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Session\AccountInterface;

/**
 * Form to list out available backups.
 */
class BackupPermissionsListForm extends FormBase {

  use BackupPermissionsStorageTrait;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The tempstore object.
   *
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * Constructs a BackupPermissionsCreateForm object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The temp factory service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user service.
   */
  public function __construct(DateFormatterInterface $date_formatter, PrivateTempStoreFactory $temp_store_factory, AccountInterface $current_user) {
    $this->date_formatter = $date_formatter;
    $this->tempStore = $temp_store_factory->get('backup_permissions');
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('date.formatter'),
      $container->get('user.private_tempstore'),
      $container->get('current_user'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'backup_permissions_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $header = array(
      'Title',
      'Created',
      '',
    );
    $rows = array();

    $results = $this->getBackupList();
    foreach ($results as $result) {
      $drop_button = array(
        '#type' => 'dropbutton',
        '#links' => array(
          'reset' => array(
            'title' => $this->t('reset'),
            'url' => Url::fromRoute('backup_permissions.reset', array('bid' => $result->id)),
          ),
          'download' => array(
            'title' => $this->t('download'),
            'url' => Url::fromRoute('backup_permissions.download', array('bid' => $result->id)),
          ),
          'delete' => array(
            'title' => $this->t('delete'),
            'url' => Url::fromRoute('backup_permissions.delete', array('bid' => $result->id)),
          ),
        ),
      );
      $rows[$result->id] = array(
        $result->title,
        $this->date_formatter->format($result->created, 'long'),
        array(
          '#markup' => render($drop_button),
        ),
      );
    }
    $form['automatic_backup'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Automatic backup'),
    );
    $form['automatic_backup']['backup_permissions_auto_backup_config'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically backup permission every-time permissions are updated.'),
      '#default_value' => $this->configFactory()
        ->get('backup_permissions.settings')
        ->get('auto_backup_config'),
    );
    $form['automatic_backup']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save Configuration'),
      '#name' => 'save_configuration',
    );
    $form['options'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Update options'),
      '#attributes' => array('class' => array('container-inline')),
    );
    $options = array('delete' => 'Delete the selected backups');

    $form['options']['operation'] = array(
      '#type' => 'select',
      '#title' => $this->t('Operation'),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#default_value' => 'delete',
    );

    $form['options']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#name' => 'update',
    );

    $form['backups'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $rows,
      '#empty' => $this->t('No backups found'),
    );
    $form['pager'] = array(
      '#type' => 'pager',
      '#weight' => 10,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#name'] == 'update') {
      $backups = array_filter($form_state->getValue('backups'));
      if (count($backups) == 0) {
        $form_state->setErrorByName('', $this->t('No backups selected.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#name'] == 'update') {
      $this->tempStore->set($this->currentUser->id(), array_filter($form_state->getValue('backups')));
      $form_state->setRedirect('backup_permissions.multiple_delete_confirm');
    }
    else {
      $config = $this->configFactory()
        ->getEditable('backup_permissions.settings');

      $config->set('auto_backup_config', $form_state->getValue('backup_permissions_auto_backup_config'))
        ->save();
    }
  }

}
