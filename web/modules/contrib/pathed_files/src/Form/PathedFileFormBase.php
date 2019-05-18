<?php

namespace Drupal\pathed_file\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Class PathedFileFormBase.
 *
 * The add and edit forms extend this.
 *
 * @package Drupal\pathed_file\Form
 *
 * @ingroup pathed_file
 */
class PathedFileFormBase extends EntityForm {

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(QueryFactory $query_factory) {
    $this->entityQueryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.query'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $pathed_file = $this->entity;

    // Build form elements specific to pathed files.
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $pathed_file->label(),
      '#required' => TRUE,
    );
    $form['path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URL path'),
      '#maxlength' => 255,
      '#default_value' => $pathed_file->path,
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $pathed_file->id(),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ),
      '#disabled' => !$pathed_file->isNew(),
    );
    $form['content'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('File content'),
      '#default_value' => $pathed_file->content,
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    // Use the query factory to build a new pathed_file entity query.
    $query = $this->entityQueryFactory->get('pathed_file');

    // Query the entity ID to see if its in use.
    return (bool) $query->condition('id', $element['#field_prefix'] . $entity_id)->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // Change the submit button text.
    $actions['submit']['#value'] = $this->t('Save');

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $v = $form_state->getValues();

    // Ensure the path doesn't conflict with actual file.
    if (file_exists($v['path'])) {
      $form_state->setErrorByName('path', $this->t('Another file at this path exists. Please choose another path.'));
    }

    // Nor does it conflict with an existing Drupal alias. Pass the third
    // parameter to aliasExists to only match against aliases NOT for this
    // entity.
    $pathed_file = $this->getEntity();
    $source = !empty($pathed_file->id) ? 'pathed-files/' . $pathed_file->id : NULL;
    if (\Drupal::service('path.alias_storage')->aliasExists('/'. $v['path'], LanguageInterface::LANGCODE_NOT_SPECIFIED, $source)) {
      $form_state->setErrorByName('path', $this->t('Another alias at this path exists. Please choose another path.'));
    }

    // @TODO How to check against all Drupal paths (not just aliases)?
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $pathed_file = $this->getEntity();
    $status = $pathed_file->save();

    // Grab the URL of the new entity. We'll use it in the message.
    $url = $pathed_file->urlInfo();

    $edit_link = $this->l(t('Edit'), $url);

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('Pathed file %label has been updated.', array('%label' => $pathed_file->label())));
      $this->logger('pathed_file')->notice('Pathed file %label has been updated.', ['%label' => $pathed_file->label(), 'link' => $edit_link]);
    }
    else {
      drupal_set_message($this->t('Pathed file %label has been added.', array('%label' => $pathed_file->label())));
      $this->logger('pathed_file')->notice('Pathed file %label has been added.', ['%label' => $pathed_file->label(), 'link' => $edit_link]);
    }

    $form_state->setRedirect('pathed_file.list');
  }
}
