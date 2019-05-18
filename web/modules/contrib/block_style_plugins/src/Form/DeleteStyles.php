<?php

namespace Drupal\block_style_plugins\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenOffCanvasDialogCommand;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to delete a block style.
 *
 * @internal
 */
class DeleteStyles extends ConfirmFormBase {

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The section storage.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * The layout section delta.
   *
   * @var int
   */
  protected $delta;

  /**
   * The uuid of the block component.
   *
   * @var string
   */
  protected $uuid;


  /**
   * The plugin id of the style to be removed.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * Constructs a DeleteStyles object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   */
  public function __construct(FormBuilderInterface $form_builder, LayoutTempstoreRepositoryInterface $layout_tempstore_repository) {
    $this->formBuilder = $form_builder;
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('layout_builder.tempstore_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $uuid = NULL, $plugin_id = NULL) {
    $this->sectionStorage = $section_storage;
    $this->delta = $delta;
    $this->uuid = $uuid;
    $this->pluginId = $plugin_id;
    $form = parent::buildForm($form, $form_state);
    $form['actions']['cancel'] = $this->buildCancelLink();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this style?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $parameters = $this->getParameters();
    return new Url('block_style_plugins.layout_builder.styles', $parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_style_plugins_layout_builder_delete_styles';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $component = $this->sectionStorage->getSection($this->delta)->getComponent($this->uuid);
    $component->unsetThirdPartySetting('block_style_plugins', $this->pluginId);

    $this->layoutTempstoreRepository->set($this->sectionStorage);
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl());
  }

  /**
   * Build a cancel button for the confirm form.
   */
  protected function buildCancelLink() {
    return [
      '#type' => 'button',
      '#value' => $this->getCancelText(),
      '#ajax' => [
        'callback' => '::ajaxCancel',
      ],
    ];
  }

  /**
   * Provides an ajax callback for the cancel button.
   */
  public function ajaxCancel(array &$form, FormStateInterface $form_state) {
    $parameters = $this->getParameters();
    $new_form = $this->formBuilder->getForm('\Drupal\block_style_plugins\Form\BlockStyleForm', $this->sectionStorage, $parameters['delta'], $parameters['uuid']);
    $new_form['#action'] = $this->getCancelUrl()->toString();
    $response = new AjaxResponse();
    $response->addCommand(new OpenOffCanvasDialogCommand($this->t('Configure Styles'), $new_form));
    return $response;
  }

  /**
   * Gets the parameters needed for the various Url() and form invocations.
   *
   * @return array
   *   List of Url parameters.
   */
  protected function getParameters() {
    return [
      'section_storage_type' => $this->sectionStorage->getStorageType(),
      'section_storage' => $this->sectionStorage->getStorageId(),
      'delta' => $this->delta,
      'uuid' => $this->uuid,
    ];
  }

}
