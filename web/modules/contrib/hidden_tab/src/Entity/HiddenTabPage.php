<?php

namespace Drupal\hidden_tab\Entity;

use Drupal\Core\Annotation\PluralTranslation;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\ConfigEntityType;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hidden_tab\Entity\Helper\DescribedEntityTrait;
use Drupal\hidden_tab\Entity\Helper\RefrencerEntityTrait;
use Drupal\hidden_tab\Entity\Helper\StatusedEntityTrait;
use Drupal\hidden_tab\Entity\Helper\TimestampedEntityTrait;
use Drupal\hidden_tab\Form\HiddenTabPageForm;
use Drupal\hidden_tab\Service\CreditCharging;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Defines the hidden_tab_page entity type.
 *
 * @ConfigEntityType(
 *   id = "hidden_tab_page",
 *   label = @Translation("Hidden Tab Page"),
 *   label_collection = @Translation("Hidden Tab Pages"),
 *   label_singular = @Translation("Hidden Tab Page"),
 *   label_plural = @Translation("Hidden Tab Pages"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Hidden Tab Page",
 *     plural = "@count Hidden Tab Pages",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\hidden_tab\Entity\HiddenTabPageListBuilder",
 *     "access" = "Drupal\hidden_tab\Entity\HiddenTabPageAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\hidden_tab\Form\HiddenTabPageForm",
 *       "edit" = "Drupal\hidden_tab\Form\HiddenTabPageForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "default" = "Drupal\hidden_tab\Form\HiddenTabPageForm",
 *       "layout" = "Drupal\hidden_tab\Form\LayoutForm"
 *     }
 *   },
 *   config_prefix = "hidden_tab_page",
 *   admin_permission = "administer hidden tab",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/hidden-tab-page/add",
 *     "edit-form" = "/admin/structure/hidden-tab-page/{hidden_tab_page}",
 *     "delete-form" = "/admin/structure/hidden-tab-page/{hidden_tab_page}/delete",
 *     "canonical" = "/admin/structure/hidden-tab-page/{hidden_tab_page}",
 *     "collection" = "/admin/structure/hidden-tab-page"
 *   }
 * )
 */
class HiddenTabPage extends ConfigEntityBase implements HiddenTabPageInterface {

  use RefrencerEntityTrait;
  use StatusedEntityTrait;
  use DescribedEntityTrait;
  use TimestampedEntityTrait;
  use EntityChangedTrait;

  /**
   * See tabUri() for more information.
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPageInterface::tabUri()
   */
  protected $tab_uri;

  /**
   * See secretUri().
   *
   * @var bool|null
   *   See secretUri().
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPageInterface::secretUri()
   */
  protected $secret_uri;

  /**
   * See isAccessDenied().
   *
   * @var bool|null
   *   See isAccessDenied().
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPageInterface::isAccessDenied()
   */
  protected $is_access_denied;

  /**
   * See tabPermission() for more information.
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPageInterface::tabPermission()
   */
  protected $tab_view_permission;

  /**
   * See secretUriPermission() for more information.
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPageInterface::secretUriPermission()
   */
  protected $secret_uri_view_permission;

  /**
   * See creditCheckOrder().
   *
   * @var string|null
   */
  protected $credit_check_order;

  /**
   * See template() for more information.
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPageInterface::template()
   */
  protected $template;

  /**
   * See inlineTemplateRegionCount() for more information.
   *
   * @var int|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPageInterface::inlineTemplateRegionCount()
   */
  protected $inline_template_region_count;

  /**
   * See inlineTemplate() for more information.
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPageInterface::inlineTemplate()
   */
  protected $inline_template;

  /**
   * {@inheritdoc}
   */
  public function tabUri(): ?string {
    return $this->tab_uri;
  }

  /**
   * {@inheritdoc}
   */
  public function secretUri(): ?string {
    return $this->secret_uri;
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessDenied(): bool {
    return !!$this->is_access_denied;
  }

  /**
   * {@inheritdoc}
   */
  public function tabViewPermission(): ?string {
    return $this->tab_view_permission;
  }

  /**
   * {@inheritdoc}
   */
  public function secretUriViewPermission(): ?string {
    return $this->secret_uri_view_permission;
  }

  /**
   * {@inheritdoc}
   */
  public function creditCheckOrder(): array {
    return !$this->credit_check_order
      ? CreditCharging::instance()
        ->fixCreditCheckOrder(HiddenTabPageInterface::DEFAULT_CREDIT_CHECK_ORDER)
      : CreditCharging::instance()
        ->fixCreditCheckOrder($this->credit_check_order);
  }

  /**
   * {@inheritdoc}
   */
  public function template(): ?string {
    return $this->template;
  }

  /**
   * {@inheritdoc}
   */
  public function inlineTemplateRegionCount(): int {
    return (int) $this->inline_template_region_count;
  }

  /**
   * {@inheritdoc}
   */
  public function inlineTemplate(): ?string {
    return $this->inline_template;
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation,
                         AccountInterface $account = NULL,
                         $return_as_object = FALSE,
                         ?EntityInterface $context_entity = NULL,
                         ?ParameterBag $bag = NULL) {
    /** @var \Drupal\hidden_tab\Entity\HiddenTabPageAccessControlHandler $am */
    $am = $this->entityTypeManager()
      ->getAccessControlHandler($this->entityTypeId);
    if ($operation == 'create') {
      return $am->createAccess(
        $this->bundle(), $account, [], $return_as_object);
    }
    else {
      return $am->access(
        $this, $operation, $account, $return_as_object, $context_entity, $bag);
    }
  }

}
