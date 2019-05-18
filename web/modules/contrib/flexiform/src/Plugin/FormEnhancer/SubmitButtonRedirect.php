<?php

namespace Drupal\flexiform\Plugin\FormEnhancer;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\flexiform\FormEnhancer\ConfigurableFormEnhancerBase;
use Drupal\flexiform\FormEnhancer\SubmitButtonFormEnhancerTrait;
use Drupal\flexiform\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * FormEnhancer for altering the redirects of submit buttons.
 *
 * @FormEnhancer(
 *   id = "submit_button_redirect",
 *   label = @Translation("Button Redirects"),
 * );
 */
class SubmitButtonRedirect extends ConfigurableFormEnhancerBase implements ContainerFactoryPluginInterface {
  use SubmitButtonFormEnhancerTrait;
  use StringTranslationTrait;

  /**
   * Token Service.
   *
   * @var \Drupal\flexiform\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  protected $supportedEvents = [
    'process_form',
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('flexiform.token')
    );
  }

  /**
   * Construct a new SubmitButtonRedirect object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm(array $form, FormStateInterface $form_state) {
    foreach ($this->locateSubmitButtons() as $path => $label) {
      $original_path = $path;
      $path = str_replace('][', '::', $path);
      $form['redirect'][$path] = [
        '#type' => 'textfield',
        '#title' => $this->t('@label Button Redirect Path', ['@label' => $label]),
        '#description' => 'Array Parents: ' . $original_path,
        '#default_value' => !empty($this->configuration[$path]) ? $this->configuration[$path] : '',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configurationFormSubmit(array $form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValue($form['#parents']);
  }

  /**
   * Process Form Enhancer.
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    foreach (array_filter($this->configuration) as $key => $redirect) {
      $array_parents = explode('::', $key);
      $button = NestedArray::getValue($element, $array_parents, $exists);
      if ($exists) {
        if (empty($button['#submit'])) {
          $button['#submit'] = !empty($form['#submit']) ? $form['#submit'] : [];
        }
        $button['#submit'][] = [$this, 'formSubmitRedirect'];
        $button['#submit_redirect'] = $redirect;
        NestedArray::setValue($element, $array_parents, $button);
      }
    }
    return $element;
  }

  /**
   * Redirection submit handler.
   */
  public function formSubmitRedirect($form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();

    $token_data = $token_options = [];
    $token_info = $this->token->getInfo();
    foreach ($this->formDisplay->getFormEntityManager()->getFormEntities() as $namespace => $form_entity) {
      $entity = $form_entity->getFormEntityContext()->getContextValue();
      if ($namespace == '') {
        $namespace = 'base_entity';
      }

      $token_type = $entity->getEntityType()->get('token_type') ?: (!empty($token_info['types'][$entity->getEntityTypeId()]) ? $entity->getEntityTypeId() : FALSE);
      if ($token_type) {
        $token_data[$namespace] = $form_entity->getFormEntityContext()->getContextValue();
        $token_options['alias'][$namespace] = $token_type;
      }
    }

    if (!empty($element['#submit_redirect'])) {
      // @todo: Support tokens.
      // @todo: Support all the different schemes.
      $path = $element['#submit_redirect'];
      if (!in_array($path[0], ['/', '?', '#'])) {
        $path = '/' . $path;
      }
      $path = $this->token->replace($path, $token_data, $token_options);

      $form_state->setRedirectUrl(Url::fromUserInput($path));
    }
  }

}
