<?php

namespace Drupal\production_checklist\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\production_checklist\ProductionChecklistInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to delete section items.
 *
 * @internal
 */
class DeleteSectionItemsForm extends ConfirmFormBase {

  /**
   * List of sections to remove.
   *
   * @var array
   */
  private $sections;

  /**
   * The production checklist manager.
   *
   * @var ProductionChecklistInterface
   */
  protected $productionChecklist;

  /**
   * Constructs a new DeleteSectionItemsForm object.
   *
   * @param \Drupal\production_checklist\ProductionChecklistInterface $production_checklist
   *   The production checklist manager.
   */
  public function __construct(ProductionChecklistInterface $production_checklist) {
    $this->productionChecklist = $production_checklist;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('production_checklist')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'production_checklist_delete_items';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    // @todo get section titles
    $sectionTitles = $this->productionChecklist->getSectionTitles($this->sections);
    return $this->t('Do you want to clear the items from the following sections: %sections?',
      ['%sections' => implode(', ', $sectionTitles)]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Clear section items');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('production_checklist.sections');
  }

  /**
   * {@inheritdoc}
   *
   * @param string $sections
   *   The sections to disable.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $sections = '') {
    $this->sections = explode(',', $sections);
    // @todo check sections from existing ones
    if (empty($sections)) {
      throw new \Exception(t('No sections were found.'));
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('production_checklist.settings');
    $sections = $config->get('sections');
    foreach ($this->sections as $sectionKey) {
      $sections[$sectionKey] = 0;
    }
    $config->set('sections', $sections)->save();

    $clearedItems = $this->productionChecklist->clearItems($sections);
    if (!empty($clearedItems)) {
      $this->logger('user')->notice('Cleared sections items: %items',
        ['%items' => implode(', ', $clearedItems)]
      );
      // @todo messenger
      drupal_set_message(t('Cleared sections items: @items.', ['@items' => implode(', ', $clearedItems)]));
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
