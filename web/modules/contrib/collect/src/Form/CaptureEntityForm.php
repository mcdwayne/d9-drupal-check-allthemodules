<?php
/**
 * @file
 * Contains \Drupal\collect\Form\CaptureEntityForm.
 */

namespace Drupal\collect\Form;

use Drupal\collect\CaptureEntity;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for capturing all entities of an entity type.
 */
class CaptureEntityForm extends FormBase {

  /**
   * The injected entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The entity capture service.
   *
   * @var \Drupal\collect\CaptureEntity
   */
  protected $entityCapturer;

  /**
   * Creates a new CaptureEntityForm object.
   */
  public function __construct(EntityManagerInterface $entity_manager, CaptureEntity $entity_capturer) {
    $this->entityManager = $entity_manager;
    $this->entityCapturer = $entity_capturer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('collect.capture_entity')
    );
  }

  /**
   * The mime type of the submitted data.
   */
  const MIMETYPE = 'application/json';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_types = $this->entityManager->getEntityTypeLabels(TRUE);
    $options = $entity_types['Content'];
    unset($options['collect_container']);

    // Create the "Choose entity type" dropdown.
    // It allows the base table of the view to be selected.
    $form['entity_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#options' => $options,
      '#default_value' => $form_state->getValue('entity_type'),
      '#required' => TRUE,
      '#executes_submit_callback' => TRUE,
      '#limit_validation_errors' => array(array('entity_type')),
      '#submit' => array('::submitSelectEntityType'),
      '#ajax' => array(
        'callback' => '::updateForm',
        'wrapper' => 'collect_capture_selection',
        'method' => 'replace',
      ),
    );
    $form['entity_type_select'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Select entity type'),
      '#limit_validation_errors' => array(array('entity_type')),
      '#submit' => array('::submitSelectEntityType'),
      '#attributes' => array('class' => array('js-hide')),
    );
    $form['container'] = array(
      '#type' => 'container',
      '#prefix' => '<div id="collect_capture_selection">',
      '#suffix' => '</div>',
    );
    $operation = array(
      'single' => $this->t('Single'),
      'multiple' => $this->t('Multiple'),
    );
    $form['container']['operation'] = array(
      '#type' => 'radios',
      '#title' => t('Operation'),
      '#required' => TRUE,
      '#options' => $operation,
    );
    if ($entity_type = $form_state->getValue('entity_type')) {
      $form['container']['bundle'] = array(
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#options' => collect_common_get_bundles($this->entityManager, $entity_type),
        '#empty_option' => $this->t('- Any -'),
        '#states' => array(
          'visible' => array(
            ':input[name="operation"]' => array('value' => 'multiple'),
          ),
        ),
        '#description' => $this->t('For multiple entities you can select a bundle.'),
        '#access' => $this->entityManager->getDefinition($entity_type)->hasKey('bundle'),
      );
      $form['container']['entity'] = array(
        '#type' => 'entity_autocomplete',
        '#target_type' => $entity_type,
        '#title' => $this->t('Entity'),
        '#maxlength' => 255,
        '#states' => array(
          'visible' => array(
            ':input[name="operation"]' => array('value' => 'single'),
          ),
          'required' => array(
            ':input[name="operation"]' => array('value' => 'single'),
          ),
        ),
        '#description' => $this->t('For single entity enter title and id. E.g. "Anonymous (0)".'),
      );
    }
    $form['container']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Capture'),
    );
    return $form;
  }

  /**
   * Handles submit call when entity type is selected.
   */
  public function submitSelectEntityType(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Ajax callback returning the container part of the form.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The container form part.
   */
  public function updateForm(array $form, FormStateInterface $form_state) {
    return $form['container'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'collect_entity_capture';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ((string) $form_state->getTriggeringElement()['#value'] == $this->t('Capture')) {

      if ($form_state->getValue('operation') == 'single' && !$form_state->getValue('entity')) {
        $form_state->setErrorByName('entity', $this->t('You need to enter entity title and id.'));
      }
    }
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type = $form_state->getValue('entity_type');
    $message_context = array('@entity_type' => \Drupal::entityManager()->getStorage($entity_type)->getEntityType()->getLabel());
    // Capture an entity.
    if ($form_state->getValue('operation') == 'single' && $form_state->getValue('entity')) {
      $entity = entity_load($entity_type, $form_state->getValue('entity'));
      $container = $this->entityCapturer->capture($entity);
      drupal_set_message($this->t('The @entity_type entity has been captured.', $message_context));
      $form_state->setRedirect('entity.collect_container.canonical', ['collect_container' => $container->id()]);
    }
    // Capture all entites from chosen entity type.
    if ($form_state->getValue('operation') == 'multiple') {
      if ($form_state->getValue('bundle') && $bundle_key = $this->entityManager->getDefinition($entity_type)->getKey('bundle')) {
        $entities = entity_load_multiple_by_properties($entity_type, array($bundle_key => $form_state->getValue('bundle')));
      }
      else {
        $entities = entity_load_multiple($entity_type);
      }
      if (!empty($entities)) {
        $operations = array();
        foreach ($entities as $entity) {
          $operations[] = array(
            'collect_capture_entity_batch',
            array($entity)
          );
        }
        $batch = array(
          'title' => t('Capturing entities'),
          'operations' => $operations,
        );
        batch_set($batch);

        if ($form_state->getValue('bundle')) {
          drupal_set_message($this->t('All @entity_type entites from the bundle @bundle have been captured.', $message_context + array('@bundle' => $form_state->getValue('bundle'))));
        }
        else {
          drupal_set_message($this->t('All @entity_type entites have been captured.', $message_context));
        }
        $form_state->setRedirect('entity.collect_container.collection');
      }
      else {
        if ($form_state->getValue('bundle')) {
          drupal_set_message($this->t('There are no @entity_type entities from the bundle @bundle.', $message_context + array('@bundle' => $form_state->getValue('bundle'))), 'warning');
        }
        else {
          drupal_set_message($this->t('There are no @entity_type entities.', $message_context), 'warning');
        }
      }
    }
  }

}
