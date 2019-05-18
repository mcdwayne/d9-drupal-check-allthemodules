<?php

namespace Drupal\hidden_tab\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\hidden_tab\Entity\Base\DescribedEntityInterface;
use Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface;
use Drupal\hidden_tab\Entity\Base\StatusedEntityInterface;
use Drupal\hidden_tab\Entity\Base\TimestampedEntityInterface;
use Drupal\hidden_tab\Utility;

/**
 * Provides an interface defining a hidden tab credit entity type.
 */
interface HiddenTabCreditInterface extends
  ContentEntityInterface,
  RefrencerEntityInterface,
  StatusedEntityInterface,
  DescribedEntityInterface,
  TimestampedEntityInterface {

  /**
   * Default amount of credit when new entity is created.
   */
  public const DEFAULT_CREDIT = -3;

  /**
   * Default amount of credit span when new entity is created (5 minutes).
   */
  public const DEFAULT_CREDIT_SPAN_SECONDS = 300;

  /**
   * Permission constant.
   */
  public const PERMISSION_ADMINISTER = Utility::ADMIN_PERMISSION;

  /**
   * Permission constant.
   */
  public const PERMISSION_CREATE = 'create hidden tab credit';

  /**
   * Permission constant.
   */
  public const PERMISSION_VIEW = 'view hidden tab credit';

  /**
   * Permission constant.
   */
  public const PERMISSION_UPDATE = 'update hidden tab credit';

  /**
   * Permission constant.
   */
  public const PERMISSION_DELETE = 'delete hidden tab credit';

  /**
   * Permission / Operation constant.
   */
  public const OP_ADMINISTER = HiddenTabCreditInterface::PERMISSION_ADMINISTER;

  /**
   * Permission / Operation constant.
   */
  public const OP_VIEW = 'view';

  /**
   * Permission / Operation constant.
   */
  public const OP_UPDATE = 'update';

  /**
   * Permission / Operation constant.
   */
  public const OP_DELETE = 'delete';

  /**
   * The key used to generate secret URI.
   *
   * @return string|null
   *   The key used to generate secret URI.
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPageInterface::secretUri()
   */
  public function secretKey(): ?string;

  /**
   * How much credit user has to access the secret URI.
   *
   * @return int|null
   *   How much credit user has to access the secret URI.
   */
  public function credit(): ?int;

  /**
   * How much time must pass before user is charged new credit on access.
   *
   * @return int|null
   *   How much time must pass before user is charged new credit on access.
   */
  public function creditSpan(): ?int;

  /**
   *  Whether if accounting is per IP address, while credit span if enabled.
   *
   * Means, credit span is takes into account, but a new IP will consume a new
   * credit anyway.
   *
   * @return bool
   *   Whether if accounting is done per IP address, while credit span is
   *   enabled.
   */
  public function isPerIp(): bool;

  /**
   * Template plugin to use when user has not enough credit, or value <deny>.
   *
   * @return string|null
   *   Template plugin to use when user has not enough credit. <deny> means
   *   throw proper exception instead according to isAccessDenied().
   */
  public function lowCreditTemplate(): ?string;

  /**
   * On the fly version of lowCreditTemplate().
   *
   * @return string|null
   *   Inline template string to render instead of lowCreditTemplate().
   */
  public function lowCreditInlineTemplate(): ?string;

  /**
   * Plugin data for keeping tabs on ip's accessing.
   *
   * For charging credits per ip. related to isPerIp().
   *
   * @param string $key
   *   Who's config? if not given, everyone.
   *
   * @return mixed
   *   Plugin data for keeping tabs on ip's accessing, for charging credits
   *   per ip. related to isPerIp().
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabCredit::isPerIp()
   */
  public function ipAccounting(?string $key = NULL);

  /**
   * Plugin data for keeping tabs on ip's accessing.
   *
   * For charging credits per ip. related to isPerIp().
   *
   * @param string $key
   *   Who's config is $accounting.
   * @param mixed $accounting
   *   Plugins data.
   *
   * @return \Drupal\hidden_tab\Entity\HiddenTabCredit
   *   This.
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabCredit::isPerIp()
   */
  public function setIpAccounting(string $key, $accounting): HiddenTabCredit;

  /**
   * If credit is not taken into account.
   *
   * @return bool
   *   If credit is not taken into account.
   */
  public function isInfiniteCredit(): bool;

}
