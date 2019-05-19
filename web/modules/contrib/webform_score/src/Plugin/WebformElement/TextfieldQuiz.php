<?php

namespace Drupal\webform_score\Plugin\WebformElement;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\webform\Plugin\WebformElement\TextField;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformLibrariesManagerInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\webform_score\Plugin\WebformScoreManagerInterface;
use Drupal\webform_score\QuizInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'textfield' quiz element.
 *
 * @WebformElement(
 *   id = "webform_score_textfield",
 *   label = @Translation("Textfield"),
 *   description = @Translation("Provides a text field element with pre-defined correct answer."),
 *   category = @Translation("Quiz"),
 * )
 */
class TextfieldQuiz extends TextField implements QuizInterface {

  use QuizTrait;

  /**
   * TextfieldQuiz constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager
   *   Typed data manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   * @param \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager
   *   The webform libraries manager.
   * @param \Drupal\webform_score\Plugin\WebformScoreManagerInterface $webform_score_manager
   *   Webform score plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ConfigFactoryInterface $config_factory, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ElementInfoManagerInterface $element_info, TypedDataManagerInterface $typed_data_manager, WebformElementManagerInterface $element_manager, WebformTokenManagerInterface $token_manager, WebformLibrariesManagerInterface $libraries_manager, WebformScoreManagerInterface $webform_score_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $config_factory, $current_user, $entity_type_manager, $element_info, $element_manager, $token_manager, $libraries_manager);

    $this->typedDataManager = $typed_data_manager;
    $this->webformScoreManager = $webform_score_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('webform'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.element_info'),
      $container->get('typed_data_manager'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.token_manager'),
      $container->get('webform.libraries_manager'),
      $container->get('plugin.manager.webform_score')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getAnswerDataTypeId() {
    return 'string';
  }

}
