<?php

/**
 * @file
 * Contains \Drupal\devel\Form\ConfigEditor.
 */

namespace Drupal\field_help_helper\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\field\FieldConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Edit config variable form.
 */
class ConfigFieldHelp extends FormBase {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $fieldConfigStorage;

  /**
   * Constructs a new FieldConfigDeleteForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\field\FieldConfigInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entityManager, EntityStorageInterface $fieldConfigStorage) {
    $this->entityManager = $entityManager;
    $this->fieldConfigStorage = $fieldConfigStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity.manager')->getStorage('field_config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_help_helper_config_field_help';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $field_id = '') {
    $form = array();

    $field_config = $this->fieldConfigStorage->load($field_id);
    if (empty($field_config)) {
      throw new NotFoundHttpException();
    }

    $form['#entity'] =  $field_config;
    $bundles = $this->entityManager->getBundleInfo($field_config->getTargetEntityTypeId());

    $form_title = $this->t('%field help text for %bundle', array(
      '%field' => $field_config->getLabel(),
      '%bundle' => $bundles[$field_config->getTargetBundle()]['label'],
    ));
    $form['#title'] = $form_title;

    if ($field_config->getFieldStorageDefinition()->isLocked()) {
      $form['locked'] = array(
        '#markup' => $this->t('The field %field is locked and cannot be edited.', array('%field' => $this->entity->getLabel())),
      );

      return $form;
    }

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $field_config->getLabel() ?: $field_config->getName(),
      '#required' => TRUE,
    );

    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Help text'),
      '#default_value' => $field_config->getDescription(),
      '#rows' => 5,
      '#description' => $this->t('Instructions to present to the user below this field on the editing form.<br />Allowed HTML tags: @tags', array('@tags' => FieldFilteredMarkup::displayAllowedTags())) . '<br />' . $this->t('This field supports tokens.'),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );
    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $this->getCancelLinkUrl(),
    );

    return $form;
  }

  /**
   * Builds the cancel link url for the form.
   *
   * @return Url
   *   Cancel url
   */
  private function getCancelLinkUrl() {
    $query = $this->getRequest()->query;

    if ($query->has('destination')) {
      $url = Url::fromUserInput($query->get('destination'));
    }
    else {
      $url = Url::fromUserInput($this->getRequest()->getPathInfo(), ['query' => UrlHelper::filterQueryParameters($this->getRequest()->query->all(), ['q'])]);
    }

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($field_config = $this->fieldConfigStorage->load($form['#entity']->id())) {
      try {
        $field_config->setLabel($form_state->getValue('label'));
        $field_config->setDescription($form_state->getValue('description'));

        $field_config->save();
        drupal_set_message($this->t('Saved %label configuration.', array('%label' => $field_config->getLabel())));
      }
      catch (EntityStorageException $e) {
        drupal_set_message($this->t('Unable save %label configuration.', array('%label' => $field_config->getLabel())), 'error');
      }
    }
    else {
      drupal_set_message($this->t('Unable to load %label configuration.', array('%label' => $field_config->getLabel())), 'error');
    }
  }

}
