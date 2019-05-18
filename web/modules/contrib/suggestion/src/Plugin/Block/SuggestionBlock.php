<?php

namespace Drupal\suggestion\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\suggestion\Form\SuggestionBlockForm;
use Drupal\suggestion\SuggestionHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a suggestion search block.
 *
 * @Block(
 *   id = "suggestion_block",
 *   admin_label = @Translation("Suggestion Search")
 * )
 */
class SuggestionBlock extends BlockBase implements ContainerFactoryPluginInterface {
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $formBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('form_builder'));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['action' => SuggestionHelper::getConfig('action')];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['action'] = [
      '#title'         => $this->t('Action'),
      '#description'   => $this->t('The form action.'),
      '#type'          => 'textfield',
      '#default_value' => SuggestionHelper::getConfig('action'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    SuggestionHelper::setConfig('action', $form_state->getValue('action'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->formBuilder->getForm(SuggestionBlockForm::class);
  }

}
