<?php

namespace Drupal\transcoding\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\transcoding\Entity\TranscodingService;
use Drupal\transcoding\Plugin\TranscoderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TranscodingServiceForm.
 *
 * @package Drupal\transcoding\Form
 */
class TranscodingServiceForm extends EntityForm {

  protected $pluginManager;

  /**
   * @inheritDoc
   */
  public function __construct(TranscoderManager $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.transcoder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var TranscodingService $transcoding_service */
    $transcoding_service = $this->entity;
    $plugins = array_map(function ($definition) {
      return $definition['label'];
    }, $this->pluginManager->getDefinitions());
    $form['#tree'] = TRUE;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $transcoding_service->label(),
      '#description' => $this->t("Label for the Transcoding service."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $transcoding_service->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\transcoding\Entity\TranscodingService::load',
      ),
      '#disabled' => !$transcoding_service->isNew(),
    );
    $form['plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Plugin'),
      '#options' => $plugins,
      '#default_value' => $transcoding_service->getPluginId(),
      '#required' => TRUE,
      '#disabled' => !$transcoding_service->isNew(),
    ];
    if (!$transcoding_service->isNew()) {
      $form['configuration'] = [];
      $subformState = SubformState::createForSubform($form['configuration'], $form, $form_state);
      $form['configuration'] = $transcoding_service->getPlugin()->buildConfigurationForm($form['configuration'], $subformState);
    }
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $transcoding_service->status(),
    ];

    return $this->protectPluginIdElement($form);
  }

  /**
   * Protects the plugin's ID property's form element against changes.
   *
   * This method is assumed to be called on a completely built entity form,
   * including a form element for the plugin config entity's ID property.
   *
   * @see Drupal Commerce, CommercePluginEntityFormBase
   *
   * @param array $form
   *   The completely built plugin entity form array.
   *
   * @return array
   *   The updated plugin entity form array.
   */
  protected function protectPluginIdElement(array $form) {
    $entity = $this->getEntity();
    $id_key = $entity->getEntityType()->getKey('id');
    assert(isset($form[$id_key]));
    $element = &$form[$id_key];

    // Make sure the element is not accidentally re-enabled if it has already
    // been disabled.
    if (empty($element['#disabled'])) {
      $element['#disabled'] = !$entity->isNew();
    }
    return $form;
  }

  public function buildEntity(array $form, FormStateInterface $form_state) {
    $this->entity->setPluginId($form_state->getValue('plugin'));
    return parent::buildEntity($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var TranscodingService $transcodingService */
    $transcodingService = $this->entity;
    if (!$transcodingService->isNew()) {
      $subformState = SubformState::createForSubform($form['configuration'], $form, $form_state);
      $transcodingService->getPlugin()->validateConfigurationForm($form['configuration'], $subformState);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    /** @var TranscodingService $transCodingService */
    $transCodingService = $this->entity;
    if (!$transCodingService->isNew()) {
      $subformState = SubformState::createForSubform($form['configuration'], $form, $form_state);
      $transCodingService->getPlugin()->submitConfigurationForm($form['configuration'], $subformState);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $transcoding_service = $this->entity;
    $status = $transcoding_service->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Transcoding service.', [
          '%label' => $transcoding_service->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Transcoding service.', [
          '%label' => $transcoding_service->label(),
        ]));
    }
    $form_state->setRedirectUrl($transcoding_service->toUrl('collection'));
  }

}
