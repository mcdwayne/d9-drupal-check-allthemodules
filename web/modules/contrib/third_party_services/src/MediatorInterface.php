<?php

namespace Drupal\third_party_services;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\user\UserDataInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\third_party_services\Form\ConfigurationForm;

/**
 * Mediator for collecting vocabularies and 3rd party services patterns.
 */
interface MediatorInterface extends ContainerInjectionInterface {

  /**
   * Implementation of the configuration form.
   */
  const CONFIGURATION_FORM = ConfigurationForm::class;

  /**
   * Mediator constructor.
   *
   * @param \Drupal\user\UserDataInterface $user_data
   *   Instance of the "user.data" service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Instance of the "current_user" service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   Instance of the "form_builder" service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Instance of the "entity_type.manager" service.
   * @param RawHtmlRenderer $raw_html_renderer
   *   Instance of the "MODULE.raw_html_renderer" service.
   * @param string $default_vocabulary
   *   Default entity of "taxonomy_vocabulary" type with list of services.
   * @param string $vocabulary_required_field
   *   Machine-readable name of field which vocabulary must have.
   */
  public function __construct(
    UserDataInterface $user_data,
    AccountInterface $account,
    FormBuilderInterface $form_builder,
    EntityTypeManagerInterface $entity_type_manager,
    RawHtmlRenderer $raw_html_renderer,
    string $default_vocabulary,
    string $vocabulary_required_field
  );

  /**
   * Returns list of patterns for third-party services in given vocabulary.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An associative arrays where keys are patterns for third-party service
   *   recognition and values - are instances of еру "taxonomy_term" entity.
   */
  public function getServicesPatterns(): array;

  /**
   * Check whether third-party service allowed for rendering.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   Taxonomy term, representing particular service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   An account to check configuration in. Current user by default.
   *
   * @return bool
   *   A state of check.
   */
  public function isServiceAllowed(TermInterface $term, AccountInterface $account = NULL): bool;

  /**
   * Set UUIDs of services markup of which are allowed for rendering.
   *
   * @param string[] $term_uuids
   *   Indexed array of UUIDs of "taxonomy_term" entities.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   An account to save configuration in. Current user by default.
   */
  public function setAllowedServices(array $term_uuids, AccountInterface $account = NULL);

  /**
   * Returns instances of "taxonomy_term" entity.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   Indexed array of instances of "taxonomy_term" entity.
   */
  public function loadTerms(): array;

  /**
   * Return cache tags.
   *
   * @return string[]
   *   List of cache tags.
   */
  public function getCacheTags(): array;

  /**
   * Returns configuration form.
   *
   * @codingStandardsIgnoreStart
   * @param mixed ...$arguments
   * @codingStandardsIgnoreEnd
   *   Any set of arguments to pass to form builder.
   *
   * @return array
   *   Complete form which is ready for rendering.
   *
   * @see \Drupal\third_party_services\Controller\ConfigurationController::form()
   */
  public function getConfigurationForm(...$arguments): array;

  /**
   * Decide whether placeholdering is needed and spoof the renderable markup.
   *
   * @param array|string|\Drupal\Component\Render\MarkupInterface $markup
   *   Markup to scan for third-party service.
   * @param array $build
   *   Renderable array which is assumed to be rendered if no placeholders.
   * @param string $type
   *   Type of processing element. Could be any.
   * @param int $delta
   *   Number of processing element. Must differ when the same "$type" is used.
   *
   * @return \Drupal\Core\Render\HtmlResponse|null
   *   HTML response of rendered third-party service or NULL when placeholder
   *   not needed.
   */
  public function placeholder($markup, array &$build, string $type, int $delta);

}
