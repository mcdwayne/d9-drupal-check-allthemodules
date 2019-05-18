<?php

/**
 * @file
 * Contains \Drupal\accessibility\Form\testDeleteForm.
 */

namespace Drupal\accessibility\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\accessibility\VocabularyStorageControllerInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a deletion confirmation form for accessibility test.
 */
class AccessibilityTestDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * The accessibility test storage controller.
   *
   * @var \Drupal\accessibility\AccessibilityTestStorageControllerInterface
   */
  protected $testStorageController;

  /**
   * Constructs a new testDelete object.
   *
   * @param \Drupal\accessibility\AccessibilityTestStorageControllerInterface $storage_controller
   *   The Entity manager.
   */
  public function __construct(AccessibilityTestStorageControllerInterface $storage_controller) {
    $this->testStorageController = $storage_controller;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorageController('accessibility_test')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'accessibility_test_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the accessibility test %title?', array('%title' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'accessibility_test.view',
      'route_parameters' => array(
          'accessibility_test' => $this->entity->id(),
        ),
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting a test will prevent users from seeing its accessibility results. You can also disable tests instead of deleting them.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('Deleted test %name.', array('%name' => $this->entity->label())));
    watchdog('accessibility', 'Deleted test %name.', array('%name' => $this->entity->label()), WATCHDOG_NOTICE);
    $form_state['redirect'] = 'admin/config/accessibility/tests';
  }

}
