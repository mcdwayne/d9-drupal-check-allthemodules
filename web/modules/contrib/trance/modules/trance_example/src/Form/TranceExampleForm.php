<?php

namespace Drupal\trance_example\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\trance\Form\TranceForm;

/**
 * Form controller for the trance_example edit forms.
 */
class TranceExampleForm extends TranceForm {

  public static $entityType = 'trance_example';

  public static $bundleEntityType = 'trance_example_type';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return parent::create($container, self::$entityType, self::$bundleEntityType);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $account = $this->currentUser();

    $form = parent::buildForm($form, $form_state);

    if (isset($form['revision_information'])) {
      $form['revision_information']['#access'] = $entity->isNewRevision() || $account->hasPermission('administer trance example');
    }

    if (isset($form['revision'])) {
      $form['revision']['#access'] = $account->hasPermission('administer trance example');
    }

    return $form;
  }

}
