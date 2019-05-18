<?php

namespace Drupal\production_checklist\Form;

use Drupal\checklistapi\ChecklistapiChecklist;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\production_checklist\ProductionChecklistInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SectionsForms.
 */
class SectionsForm extends ConfigFormBase {

  /**
   * Constructs a new SettingsForm object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory
    ) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'production_checklist.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'production_checklist__settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('production_checklist.settings');
    $sections = $config->get('sections');
    $form['sections'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Sections to enable'),
      '#description' => $this->t('Sections that will be part of your production checklist. Disabling sections will clear previously checked items.'),
      // @todo generate options from checklist definition
      '#options' => \Drupal::service('production_checklist')->getAvailableSections(),
      '#default_value' => $sections,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Compare sections with the new desired configuration.
    // If there are less sections and if there are checked items for them
    // confirm section items deletion,
    // otherwise save the configuration and go back to the checklist.
    $config = $this->config('production_checklist.settings');
    $currentSections = $config->get('sections');
    $newSections = $form_state->getValue('sections');
    $currentActiveSections = array_filter($currentSections, function ($value) {
      return $value !== 0;
    });
    $newActiveSections = array_filter($newSections, function ($value) {
      return $value !== 0;
    });
    $sectionsDiff = array_diff_key($currentActiveSections, $newActiveSections);
    // Check if there are saved items first.
    $checklistConfig = $this->config('checklistapi.progress.' . ProductionChecklistInterface::CHECKLIST_ID);
    $savedProgress = $checklistConfig->get(ChecklistapiChecklist::PROGRESS_CONFIG_KEY);
    if (isset($savedProgress['#items'])
      // @todo $sectionsDiff > 0
      && count($currentActiveSections) > count($newActiveSections)) {
      $sectionsToRemove = implode(',', $sectionsDiff);
      // @todo check if there are checked items for the sections to be removed.
      $form_state->setRedirect('production_checklist.sections.confirm', ['sections' => $sectionsToRemove]);
    }
    else {
      parent::submitForm($form, $form_state);
      $config->set('sections', $form_state->getValue('sections'))->save();
      /** @var ProductionChecklistInterface $productionChecklist */
      $productionChecklist = \Drupal::service('production_checklist');
      $productionChecklist->clearItems($form_state->getValue('sections'));
      $form_state->setRedirect('production_checklist.admin_config');
    }
  }

}
