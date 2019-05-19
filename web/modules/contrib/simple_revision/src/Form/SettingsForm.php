<?php

namespace Drupal\simple_revision\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\simple_revision\Form
 *
 * @ingroup simple_revision
 */
class SettingsForm extends ConfigFormBase {

  protected $database;

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'simple_revision_settings';
  }

  /**
   * Controller.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('database')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_revision.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('simple_revision.settings');

    $init_on = $config->get('init_on');

    if ($init_on == TRUE) {
      drupal_set_message('Already initialized');
    }

    $form['initialize'] = [
      '#type' => 'details',
      '#title' => t('Initialize'),
      '#open' => TRUE,
      '#description' => $this->t('Click to set all Taxonomy terms initial revision'),
    ];

    $form['initialize']['initial'] = [
      '#type' => 'submit',
      '#value' => t('Initialize all Taxonomy terms'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('simple_revision.settings');

    $init_on = $config->get('init_on');

    if ($init_on == TRUE) {
      drupal_set_message('Taxonomy terms revision have been initialized already');
    }
    else {

      $termstorage = \Drupal::entityManager()->getStorage('taxonomy_term');

      $terms = $termstorage->loadMultiple();

      // Database Schema.
      $schema = $this->database->schema();

      foreach ($terms as $term) {

        // entity_id , tid.
        $entity_id = $term->id();

        // Changed.
        $changed = $term->getChangedTime();

        // Langcode.
        $langcode = $term->get('langcode')->value;

        // Fields.
        $fields = $term->getFields();

        // Serialized data.
        $serialized_data = serialize($term->getFields());

        if ($schema->tableExists('simple_revision')) {
          $values = [$entity_id, $serialized_data, $changed, $langcode];

          $resultafterinsert = $this->database->insert('simple_revision')
            ->fields(['entity_id', 'revision_data', 'changed', 'langcode'], $values)
            ->execute();

        }

      }

      $this->config('simple_revision.settings')
        ->set('init_on', TRUE)
        ->save();

      drupal_set_message(t('All Terms initialized for Revisioning'));
    }

  }

}
