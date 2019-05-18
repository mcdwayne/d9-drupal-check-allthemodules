<?php

namespace Drupal\hidden_tab\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hidden_tab\Entity\Helper\DescribedEntityTrait;
use Drupal\hidden_tab\Entity\Helper\RefrencerEntityTrait;
use Drupal\hidden_tab\Entity\Helper\StatusedEntityTrait;
use Drupal\hidden_tab\Entity\Helper\TimestampedEntityTrait;
use Drupal\hidden_tab\FUtility;
use Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager;
use Drupal\hidden_tab\Service\HiddenTabEntityHelper;
use Drupal\hidden_tab\Utility;

/**
 * Defines the hidden tab credit entity class.
 *
 * @ContentEntityType(
 *   id = "hidden_tab_credit",
 *   label = @Translation("Hidden Tab Credit"),
 *   label_collection = @Translation("Hidden Tab Credits"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\hidden_tab\Entity\HiddenTabCreditListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\hidden_tab\Entity\HiddenTabCreditAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\hidden_tab\Form\HiddenTabCreditForm",
 *       "edit" = "Drupal\hidden_tab\Form\HiddenTabCreditForm",
 *       "delete" = "Drupal\hidden_tab\Form\Base\PageBasedRedirectedDeleteFormBase",
 *       "default" = "Drupal\hidden_tab\Form\HiddenTabCreditForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "hidden_tab_credit",
 *   data_table = "hidden_tab_credit_field_data",
 *   admin_permission = "administer hidden tab",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/hidden-tab-credit/add",
 *     "edit-form" =
 *   "/admin/content/hidden-tab-credit/{hidden_tab_credit}/edit",
 *     "delete-form" =
 *   "/admin/content/hidden-tab-credit/{hidden_tab_credit}/delete",
 *     "canonical" = "/hidden_tab_credit/{hidden_tab_credit}",
 *     "collection" = "/admin/content/hidden-tab-credit"
 *   },
 *   field_ui_base_route = "entity.hidden_tab_credit.settings"
 * )
 */
class HiddenTabCredit extends ContentEntityBase implements HiddenTabCreditInterface {

  use RefrencerEntityTrait;
  use StatusedEntityTrait;
  use DescribedEntityTrait;
  use TimestampedEntityTrait;
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += FUtility::defaultField();
    $fields += FUtility::refrencerEntityFields('node', t('Node'),
      HiddenTabEntityHelper::nodeBundlesSelectList());

    $fields['secret_key'] = FUtility::string(t('Secret Key'))
      ->setDefaultValue('')
      ->setRequired(TRUE);

    // TODO constraint.
    $fields['credit'] =
      FUtility::int('Credit', NULL, -3, NULL)
        ->setDefaultValue(HiddenTabCreditInterface::DEFAULT_CREDIT);
    $fields['credit_span'] =
      FUtility::int('credit span', 0)
        ->setDefaultValue(HiddenTabCreditInterface::DEFAULT_CREDIT_SPAN_SECONDS);
    $fields['low_credit_template'] =
      FUtility::list('Low Credit Twig Template', NULL,
        HiddenTabTemplatePluginManager::instance()
          ->pluginsForSelectElement('credit'));
    $fields['low_credit_inline_template'] =
      FUtility::bigString('Low Credit Inline Twig Template');
    $fields['is_per_ip'] =
      FUtility::boolOn(t('Is Per Ip'));
    $fields['ip_accounting'] =
      FUtility::jsonEncodedField('ip_accounting');

    return $fields;
  }

  /**
   * {@inheritdoc}
   *
   * When a new hidden tab credit entity is created, set the uid entity
   * reference to the current user as the creator of the entity.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['uid' => \Drupal::currentUser()->id()];
  }

  /**
   * Small amount of elements to create a new entity.
   *
   * Those values who usually have sane defaults are omitted. So is the
   * target page as it is usually known.
   *
   * @param string $prefix
   *   The namespace to prefix form elements with.
   * @param bool $add_targets
   *   Add refrencer fields or not.
   *
   * @return array
   *   The form elements.
   */
  public static function littleForm(string $prefix = '', bool $add_targets = TRUE): array {
    $form[$prefix . 'secret_key'] = [
      '#type' => 'textfield',
      '#title' => t('Secret key'),
      '#default_value' => \Drupal::service('uuid')->generate(),
    ];

    $form[$prefix . 'is_per_ip'] = [
      '#type' => 'checkbox',
      '#title' => t('Is per ip'),
      '#default_value' => TRUE,
    ];

    $form[$prefix . 'credit'] = [
      '#type' => 'number',
      '#title' => t('Credit'),
      '#default_value' => HiddenTabCreditInterface::DEFAULT_CREDIT,
      '#min' => -3,
    ];

    $form[$prefix . 'credit_span'] = [
      '#type' => 'number',
      '#title' => t('Credit Span (seconds)'),
      '#default_value' => HiddenTabCreditInterface::DEFAULT_CREDIT_SPAN_SECONDS,
      '#min' => 0,
    ];

    $form[$prefix . 'low_credit_template'] = [
      '#type' => 'select',
      '#title' => t('Low Credit Template'),
      '#description' => t("Template used to display the you're credit is low page."),
      '#options' => HiddenTabTemplatePluginManager::instance()
        ->pluginsForSelectElement('credit'),
    ];
    foreach ($form[$prefix . 'low_credit_template']['#options'] as $key => $label) {
      $form[$prefix . 'low_credit_template']['#default_value'] = $key;
      break;
    }

    $d = t('The inline twig template used to render the message. Overrides template property.');
    $form[$prefix . 'inline_template'] = [
      '#title' => t('Inline Template'),
      '#type' => 'textarea',
      '#description' => $d,
    ];

    if ($add_targets) {
      return $form + FUtility::refrencerEntityFormElements($prefix);
    }
    else {
      return $form;
    }
  }

  /**
   * Extract values of a submitted form for entity creation.
   *
   * @param string $prefix
   *   Namespace prefix of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted form.
   * @param bool $extractRefs
   *   Extract refrencer fields or not.
   *
   * @return array
   *   Extracted values, or sane defaults.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public static function extractFormValues(string $prefix,
                                           FormStateInterface $form_state,
                                           bool $extractRefs): array {
    /** @var \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplateInterface $plugin */
    $v = [
        'description' => $form_state->getValue($prefix . 'description'),
        'secret_key' => $form_state->getValue($prefix . 'secret_key')
          ?: \Drupal::service('uuid')->generate(),
        'credit' => $form_state->getValue($prefix . 'credit'),
        'credit_span' => $form_state->getValue($prefix . 'credit_span'),
        'low_credit_template' => $form_state->getValue($prefix . 'low_credit_template'),
        'low_credit_inline_template' => $form_state->getValue($prefix . 'low_credit_inline_template'),
        'is_per_ip' => $form_state->getValue($prefix . 'is_per_ip'),
        'ip_accounting' => json_encode([]),
      ] + FUtility::defaultFieldsValues();
    if (!$v['low_credit_template']) {
      foreach (HiddenTabTemplatePluginManager::instance()
                 ->plugins('credit') as $plugin) {
        $v['low_credit_template'] = $plugin->id();
        break;
      }
    }
    if ($extractRefs) {
      $v += FUtility::extractRefrencerValues($form_state, $prefix);
    }
    return $v;
  }

  public static function validateForm(FormStateInterface $form_state,
                                      string $prefix,
                                      bool $validate_targets,
                                      string $target_entity_type,
                                      ?string $current_editing_entity_id_if_any): bool {
    $ok = TRUE;
    try {
      $cc = \Drupal::service('hidden_tab.credit_service');
      $credit = NULL;
      $credit_ = $form_state->getValue($prefix . 'credit');
      if (is_array($credit_)) {
        if (isset($credit_[0]['value'])) {
          $credit = $credit_[0]['value'];
        }
        else {
          $credit = NULL;
        }
      }
      if ($credit_ !== NULL && is_string($credit_)) {
        if ((((int) $credit_) . '') === $credit_) {
          $credit = (int) $credit_;
        }
        else {
          $credit = NULL;
        }
      }
      if ($credit === NULL || !$cc->isValid($credit)) {
        $form_state->setErrorByName($prefix . 'credit', t('Credit is not valid'));
        $ok = FALSE;
      }

      $credit_span = NULL;
      $credit_span_ = $form_state->getValue($prefix . 'credit_span');
      if (is_array($credit_span_)) {
        if (isset($credit_span_[0]['value'])) {
          $credit_span = $credit_span_[0]['value'];
        }
        else {
          $credit_span = NULL;
        }
      }
      if ($credit_span_ !== NULL && is_string($credit_span_)) {
        if ((((int) $credit_span_) . '') === $credit_span_) {
          $credit_span = (int) $credit_span_;
        }
        else {
          $credit_span = NULL;
        }
      }
      if ($credit_span === NULL || !$cc->isValid($credit_span)) {
        $form_state->setErrorByName($prefix . 'credit_span', t('Credit span is not valid'));
        $ok = FALSE;
      }

      if ($form_state->getValue($prefix . 'credit_span') < 0) {
        $form_state->setErrorByName($prefix . 'credit_span', t('Credit span is not valid'));
        $ok = FALSE;
      }

      if (!$validate_targets) {
        return $ok;
      }
      return FUtility::entityCreationValidateTargets(
        $form_state,
        $prefix,
        $target_entity_type,
        $current_editing_entity_id_if_any,
        function (?HiddenTabPageInterface $page, ?EntityInterface $entity, ?AccountInterface $user) use ($cc): array {
          return $cc->he($page, $entity, $user);
        }
      );
    }
    catch (\Throwable $error) {
      $ok = FALSE;
      Utility::error($error, 'validating hidden tab creation');
      // Do not show exception's message.
      $form_state->setErrorByName('', t('Error'));
    }
    return $ok;
  }

  /**
   * See secretKey().
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabCreditInterface::secretKey()
   */
  protected $secret_key;

  /**
   * See credit().
   *
   * @var int|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabCreditInterface::credit()
   */
  protected $credit;

  /**
   * @see creditSpan()
   *
   * @var int|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabCreditInterface::creditSpan()
   */
  protected $credit_span;

  /**
   * See isPerIp().
   *
   * @var boolean|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabCreditInterface::isPerIp()
   */
  protected $is_per_ip;

  /**
   * See ipAccounting().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabCreditInterface::ipAccounting().
   */
  protected $ip_accounting;

  /**
   * See lowCreditTemplate().
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabCreditInterface::lowCreditTemplate()
   */
  protected $low_credit_template;

  /**
   * See lowCreditTemplate().
   *
   * @var string|null
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabCreditInterface::lowCreditTemplate()
   */
  protected $low_credit_inline_template;

  /**
   * {@inheritdoc}
   */
  public function secretKey(): ?string {
    return $this->secret_key;
  }

  /**
   * {@inheritdoc}
   */
  public function credit(): ?int {
    return $this->credit;
  }

  /**
   * {@inheritdoc}
   */
  public function creditSpan(): ?int {
    return $this->credit_span;
  }

  /**
   * {@inheritdoc}
   */
  public function isPerIp(): bool {
    return !!$this->is_per_ip;
  }

  /**
   * {@inheritdoc}
   */
  public function lowCreditTemplate(): ?string {
    return $this->low_credit_template;
  }

  /**
   * {@inheritdoc}
   */
  public function lowCreditInlineTemplate(): ?string {
    return $this->low_credit_inline_template;
  }

  /**
   * {@inheritdoc}
   */
  public function ipAccounting(?string $key = NULL) {
    $decode = json_decode($this->ip_accounting, TRUE);
    if (!is_array($decode)) {
      return [];
    }
    if ($key === NULL) {
      return $decode;
    }
    if (!$key) {
      throw new \InvalidArgumentException();
    }
    return isset($decode[$key]) ? $decode[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setIpAccounting(string $key, $accounting): HiddenTabCredit {
    if (!$key) {
      throw new \InvalidArgumentException();
    }
    $decode = $this->ipAccounting();
    $decode[$key] = $accounting;
    $this->set('ip_accounting', json_encode($decode));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isInfiniteCredit(): bool {
    if ($this->credit() == -1 || $this->credit() == -2) {
      throw new \LogicException('illegal state');
    }
    return $this->credit() < -2;
  }

}
