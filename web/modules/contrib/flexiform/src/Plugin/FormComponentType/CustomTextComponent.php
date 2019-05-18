<?php

namespace Drupal\flexiform\Plugin\FormComponentType;

use Drupal\flexiform\Utility\Token;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\flexiform\FlexiformEntityFormDisplay;
use Drupal\flexiform\FormComponent\FormComponentBase;
use Drupal\flexiform\FormComponent\ContainerFactoryFormComponentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Component class for field widgets.
 */
class CustomTextComponent extends FormComponentBase implements ContainerFactoryFormComponentInterface {

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $name, array $options, FlexiformEntityFormDisplay $form_display) {
    return new static(
      $name,
      $options,
      $form_display,
      $container->get('flexiform.token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($name, $options, FlexiformEntityFormDisplay $form_display, Token $token) {
    parent::__construct($name, $options, $form_display);

    $this->token = $token;
  }

  /**
   * Render the component in the form.
   */
  public function render(array &$form, FormStateInterface $form_state, RendererInterface $renderer) {
    $token_data = $token_options = [];
    $token_info = $this->token->getInfo();
    foreach ($this->getFormEntityManager()->getContexts() as $namespace => $context) {
      /* @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $context->getContextValue();
      if (!$entity) {
        continue;
      }

      if ($namespace == '') {
        $namespace = 'base_entity';
      }

      $token_type = $entity->getEntityType()->get('token_type') ?: (!empty($token_info['types'][$entity->getEntityTypeId()]) ? $entity->getEntityTypeId() : FALSE);
      if ($token_type) {
        $token_data[$namespace] = $entity;
        $token_options['alias'][$namespace] = $token_type;
      }
    }

    $element = [
      '#type' => 'processed_text',
      '#text' => $this->token->replace($this->options['content'], $token_data, $token_options),
      '#format' => $this->options['format'],
      '#weight' => $this->options['weight'],
    ];
    $form[$this->name] = $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(array $form, FormStateInterface $form_state) {
    // No form values to extract.
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
    $sform['admin_label'] = [
      '#title' => t('Admin Label'),
      '#description' => t('Only shown on administrative pages'),
      '#type' => 'textfield',
      '#default_value' => $this->options['admin_label'],
      '#required' => TRUE,
    ];
    $sform['content'] = [
      '#title' => t('Content'),
      '#type' => 'text_format',
      '#default_value' => $this->options['content'],
      '#format' => !empty($this->options['format']) ? $this->options['format'] : NULL,
      '#required' => TRUE,
    ];
    return $sform;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormSubmit($values, array $form, FormStateInterface $form_state) {
    $options['admin_label'] = $values['admin_label'];
    $options['content'] = $values['content']['value'];
    $options['format'] = $values['content']['format'];
    return $options;
  }

}
