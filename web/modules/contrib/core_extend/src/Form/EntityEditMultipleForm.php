<?php

namespace Drupal\core_extend\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\WidgetPluginManager;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\ConfirmFormInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a multi-entity edit form.
 */
class EntityEditMultipleForm extends ConfirmFormBase implements BaseFormIdInterface, ConfirmFormInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field widget plugin manager.
   *
   * @var \Drupal\Core\Field\WidgetPluginManager
   */
  protected $fieldWidgetPluginManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The array of data to load entities.
   *
   * @var string[][]
   */
  protected $tempStoreData;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'entity_edit_multiple';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->getBaseFormId();
  }

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Field\WidgetPluginManager $field_widget_manager
   *   The field widget plugin manager.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WidgetPluginManager $field_widget_manager, PrivateTempStoreFactory $temp_store_factory, RouteMatchInterface $route_match) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldWidgetPluginManager = $field_widget_manager;
    $this->tempStoreFactory = $temp_store_factory;
    $this->routeMatch = $route_match;

    // Copy data from tempstore.
    $this->tempStoreData = $this->tempStoreFactory->get($this->getFormId())->get(\Drupal::currentUser()->id());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field.widget'),
      $container->get('user.private_tempstore'),
      $container->get('current_route_match')
    );
  }

  /**
   * Gets the entity type id.
   *
   * @return int|string
   *   The entity-type ID.
   */
  protected function getEntityTypeId() {
    return $this->tempStoreData['entity_type_id'];
  }

  /**
   * Gets the entity type definition.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity-type definition.
   */
  protected function getEntityType() {
    return $this->entityTypeManager->getDefinition($this->getEntityTypeId());
  }

  /**
   * Gets the entity ids from the user tempstore.
   *
   * @return int[]|string[]
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    return $this->tempStoreData['entity_ids'];
  }

  /**
   * Gets the entities to mass-edit.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The loaded entities.
   */
  protected function getEntities() {
    return $this->entityTypeManager->getStorage($this->getEntityTypeId())->loadMultiple($this->getEntityIds());
  }

  /**
   * The form mode to use for the inline-entity form.
   *
   * @return string
   *   The form-mode ID.
   */
  protected function getInlineEntityFormMode() {
    return 'default';
  }

  /**
   * The 'list' field field definition object.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   *   The list field definition object.
   */
  protected function getListFieldDefinition() {
    return BaseFieldDefinition::create('entity_reference')
      ->setName('list')
      ->setCardinality(-1)
      ->setSetting('target_type', $this->getEntityTypeId())
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', [])
      ->setDisplayOptions('form', [
        'type' => 'inline_entity_form_hybrid',
        'settings' => [
          'allow_new' => FALSE,
          'needs_save' => FALSE,
          'form_mode' => $this->getInlineEntityFormMode(),
        ],
        'third_party_settings' => [],
      ]);
  }

  /**
   * The parent entity to use for the 'list' field.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities to add to the form.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The faux parent entity.
   */
  protected function getListFieldParentEntity(array $entities) {
    return current($entities);
  }

  /**
   * Gets the 'ist field form element.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The entities to add to the form.
   * @param array $form
   *   An array representing the form that the editing element will be attached
   *   to.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The faux list field form element.
   */
  protected function getListField(array $entities, array &$form, FormStateInterface $form_state) {
    $field_definition = $this->getListFieldDefinition();
    $field_options = $field_definition->getDisplayOptions('form') + ['field_definition' => $field_definition];

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $list_class */
    $list_class = $field_definition->getClass();
    $list = $list_class::createInstance($field_definition, $field_definition->getName(), $this->getListFieldParentEntity($entities)->getTypedData());
    $list->setValue($entities);

    /** @var \Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex $field_instance */
    $field_instance = $this->fieldWidgetPluginManager->createInstance($field_options['type'], $field_options);
    return $field_instance->form($list, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromUserInput(\Drupal::destination()->get());
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('@action @entity_label', ['@action' => 'Update', '@entity_label' => $this->getEntityType()->getCountLabel(count($this->getEntityIds()))]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('The following @entity_type will be updated:', ['@entity_type' => $this->getEntityType()->getPluralLabel()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Update');
  }

  /**
   * The submit confirmation message.
   *
   * @param int $successful_count
   *   The amount of entities successfully updated.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The translatable confirm message.
   */
  protected function getSubmitMessage($successful_count) {
    return $this->t('Updated @entity_label entities.', ['@entity_label' => $this->getEntityType()->getCountLabel($successful_count)]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Add configurable inventory item entities.
    $form = parent::buildForm($form, $form_state);
    $form['#parents'] = [];
    $form['list'] = $this->getListField($this->getEntities(), $form, $form_state);
    $form['list']['#tree'] = TRUE;

    foreach (Element::children($form['list']['widget']['entities']) as $delta) {
      $form['list']['widget']['entities'][$delta]['form']['inline_entity_form']['#process'][] = [$this, 'processInlineEntityConfirmRemove'];
    }

    return $form;
  }

  /**
   * Process the inline entity form item.
   *
   * @param array $form
   *   The inline entity form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The modified form element.
   */
  public function processInlineEntityConfirmRemove(array $form, FormStateInterface $form_state) {
    $form['actions']['ief_remove_confirm']['#ajax']['callback'] = [$this, 'ajaxInlineEntityConfirmRemove'];
    $form['actions']['ief_remove_confirm']['#submit'][] = [$this, 'submitInlineEntityConfirmRemove'];
    return $form;
  }

  /**
   * Submit handler for inline entity form element remove.
   *
   * @param array $form
   *   The inline entity form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitInlineEntityConfirmRemove(array $form, FormStateInterface $form_state) {
    // Redirect if no entities are in the list.
    if (empty($form_state->get(['inline_entity_form', $form['list']['widget']['#ief_id'], 'entities']))) {
      $form_state->disableRedirect(FALSE);
      $form_state->setRedirectUrl($this->getCancelUrl());
      $form_state->setRebuild(TRUE);
      $form_state->setResponse(RedirectResponse::create($this->getCancelUrl()->setAbsolute()->toString()));
    }
  }

  /**
   * Remove entity form submit callback.
   *
   * The row is identified by #ief_row_delta stored on the triggering
   * element.
   * This isn't an #element_validate callback to avoid processing the
   * remove form when the main form is submitted.
   *
   * @param array $form
   *   The complete parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response object.
   */
  public function ajaxInlineEntityConfirmRemove($form, FormStateInterface $form_state) {
    // Redirect if no entities are in the list.
    if (empty($form_state->get(['inline_entity_form', $form['list']['widget']['#ief_id'], 'entities']))) {
      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new RedirectCommand($this->getCancelUrl()->setAbsolute()->toString()));
      return $ajax_response;
    }

    // Otherwise return the modified element.
    return inline_entity_form_get_element($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && $form_state->getTriggeringElement()['#id'] == 'edit-submit') {
      // Remove tempstore.
      $this->tempStoreFactory->get($this->getFormId())->delete(\Drupal::currentUser()->id());
      // Get number of items added.
      $completed_count = count(Element::children($form['list']['widget']['entities']));
      // Report inventory item creation.
      $message = $this->getSubmitMessage($completed_count);
      $this->logger($this->getEntityTypeId())->notice($message->getUntranslatedString(), $message->getArguments());
      drupal_set_message($message);
      // Redirect back to referring url.
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
  }

}
