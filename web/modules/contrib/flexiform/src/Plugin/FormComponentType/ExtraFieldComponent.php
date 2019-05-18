<?php

namespace Drupal\flexiform\Plugin\FormComponentType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\flexiform\FormComponent\FormComponentBase;

/**
 * Component class for extra fields.
 */
class ExtraFieldComponent extends FormComponentBase {

  /**
   * The extra field definition.
   *
   * @var array
   */
  protected $extraField;

  /**
   * Set the extra field definition.
   */
  public function setExtraField(array $extra_field) {
    $this->extraField = $extra_field;
  }

  /**
   * Render the component in the form.
   */
  public function render(array &$form, FormStateInterface $form_state, RendererInterface $renderer) {
    // Do nothing; extra fields are provided by the entity form handler.
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(array $form, FormStateInterface $form_state) {
    // Do nothing; extra fields are handled by the entity form handler.
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Do nothing; extra fields do not have settings.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormSubmit($values, array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminLabel() {
    if (count($this->getFormEntityManager()->getFormEntities()) > 1) {
      return $this->extraField['label'] . ' [' . $this->getFormEntityManager()->getFormEntity('')->getFormEntityContextDefinition()->getLabel() . ']';
    }
    return $this->extraField['label'];
  }

}
