<?php

namespace Drupal\backup_permissions\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\backup_permissions\BackupPermissionsStorageTrait;

/**
 * Provides a deletion confirmation form for bulk delete backups.
 */
class BackupPermissionsDeleteMultipleForm extends ConfirmFormBase {

  use BackupPermissionsStorageTrait;

  /**
   * The array of backups to delete.
   *
   * @var string[][]
   */
  protected $backupinfo = array();

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'backup_permissions_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->backupinfo), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('backup_permissions.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->backupinfo = $this->tempStoreFactory->get('backup_permissions')
      ->get(\Drupal::currentUser()->id());
    if (empty($this->backupinfo)) {
      return new RedirectResponse($this->getCancelUrl()
        ->setAbsolute()
        ->toString());
    }

    $items = [];
    foreach ($this->backupinfo as $id => $value) {
      $name = $this->load(array('id' => $id));
      foreach ($name as $backup) {
        $items[$id] = [
          'label' => [
            '#markup' => $backup->title,
          ],
        ];
      }
    }

    $form['backups'] = array(
      '#theme' => 'item_list',
      '#items' => $items,
    );
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->backupinfo)) {
      foreach ($this->backupinfo as $id => $value) {
        $this->delete($id);
      }
      $this->tempStoreFactory->get('backup_permissions')
        ->delete(\Drupal::currentUser()->id());
    }
    drupal_set_message($this->t('Selected backups has been deleted.'));
    $form_state->setRedirect('backup_permissions.settings');
  }

}
