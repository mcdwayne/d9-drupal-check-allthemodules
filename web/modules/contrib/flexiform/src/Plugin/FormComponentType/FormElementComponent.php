<?php

namespace Drupal\flexiform\Plugin\FormComponentType;

use Drupal\flexiform\FormElementPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flexiform\FlexiformEntityFormDisplay;
use Drupal\flexiform\FormComponent\FormComponentBase;
use Drupal\flexiform\FormComponent\FormComponentWithValidateInterface;
use Drupal\flexiform\FormComponent\FormComponentWithSubmitInterface;
use Drupal\flexiform\FormComponent\ContainerFactoryFormComponentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Component class for field widgets.
 */
class FormElementComponent extends FormComponentBase implements ContainerFactoryFormComponentInterface, FormComponentWithSubmitInterface, FormComponentWithValidateInterface {
  use ContextAwarePluginAssignmentTrait;
  use StringTranslationTrait;

  /**
   * Element plugin manager service.
   *
   * @var \Drupal\flexiform\FormElementPluginManager
   */
  protected $pluginManager;

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The element plugin associated with this componenet.
   *
   * @var \Drupal\flexiform\FormElement\FormElementInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $name, array $options, FlexiformEntityFormDisplay $form_display) {
    return new static(
      $name,
      $options,
      $form_display,
      $container->get('plugin.manager.flexiform.form_element'),
      $container->get('context.handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($name, $options, FlexiformEntityFormDisplay $form_display, FormElementPluginManager $plugin_manager, ContextHandlerInterface $context_handler) {
    parent::__construct($name, $options, $form_display);

    $this->pluginManager = $plugin_manager;
    $this->contextHandler = $context_handler;
  }

  /**
   * Get the form element plugin.
   */
  protected function getPlugin() {
    if (empty($this->plugin)) {
      $this->plugin = $this->pluginManager->createInstance($this->options['form_element'], $this->options['settings']);
      if ($this->plugin instanceof ContextAwarePluginInterface) {
        $this->contextHandler->applyContextMapping($this->plugin, $this->getFormEntityManager()->getContexts());
      }
    }

    return $this->plugin;
  }

  /**
   * Render the component in the form.
   */
  public function render(array &$form, FormStateInterface $form_state, RendererInterface $renderer) {
    $element = [
      '#parents' => $form['#parents'],
      '#array_parents' => !empty($form['#array_parents']) ? $form['#array_parents'] : [],
    ];
    $element['#parents'][] = $this->name;
    $element['#array_parents'][] = $this->name;
    $element += $this->getPlugin()->form($element, $form_state);
    $form[$this->name] = $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(array $form, FormStateInterface $form_state) {
    $this->getPlugin()->buildEntities($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function formValidate(array $form, FormStateInterface $form_state) {
    $this->getPlugin()->formValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function formSubmit(array $form, FormStateInterface $form_state) {
    $this->getPlugin()->formSubmit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminLabel() {
    return $this->options['admin_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $sform = [];

    $plugin = $this->getPlugin();
    if ($plugin instanceof ContextAwarePluginInterface) {
      $contexts = $this->getFormEntityManager()->getContexts();
      $sform['context_mapping'] = [
        '#parents' => array_merge($form['#parents'], ['settings', 'context_mapping']),
      ] + $this->addContextAssignmentElement($plugin, $contexts);
    }

    $sform += $this->getPlugin()->settingsForm($sform, $form_state);
    return $sform;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary += $this->getPlugin()->settingsSummary();
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormSubmit($values, array $form, FormStateInterface $form_state) {
    $options = [];
    $options += $this->getPlugin()->settingsFormSubmit($values, $form, $form_state);
    return $options;
  }

}
