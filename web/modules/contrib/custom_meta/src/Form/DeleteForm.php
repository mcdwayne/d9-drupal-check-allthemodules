<?php

/**
 * @file
 * Contains \Drupal\custom_meta\Form\DeleteForm.
 */

namespace Drupal\custom_meta\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\custom_meta\CustomMetaStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete a custom meta tag.
 */
class DeleteForm extends ConfirmFormBase {

  /**
   * The custom meta tags storage.
   *
   * @var \Drupal\custom_meta\CustomMetaStorageInterface
   */
  protected $metaStorage;

  /**
   * The custom meta tag being deleted.
   *
   * @var array $customMeta
   */
  protected $customMeta;

  /**
   * Constructs a new CustomMetaController.
   *
   * @param \Drupal\custom_meta\CustomMetaStorageInterface $meta_storage
   *   The custom meta tags storage.
   */
  public function __construct(CustomMetaStorageInterface $meta_storage) {
    $this->metaStorage = $meta_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('custom_meta.meta_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_meta_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete custom meta tag %title?', array('%title' => $this->customMeta['meta_attr_value']));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('custom_meta.admin_overview');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $meta_uid = NULL) {
    $this->customMeta = $this->metaStorage->load(array('meta_uid' => $meta_uid));

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->metaStorage->delete(array('meta_uid' => $this->customMeta['meta_uid']));
    $form_state->setRedirect('custom_meta.admin_overview');
  }

}
