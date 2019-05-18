<?php

namespace Drupal\hidden_tab\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\hidden_tab\Entity\Base\DescribedEntityInterface;
use Drupal\hidden_tab\Entity\Base\MultiAspectPluginSupportingInterface;
use Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface;
use Drupal\hidden_tab\Entity\Base\StatusedEntityInterface;
use Drupal\hidden_tab\Entity\Base\TimestampedEntityInterface;
use Drupal\hidden_tab\Utility;

/**
 * Provides an interface defining a hidden tab mailer entity type.
 */
interface HiddenTabMailerInterface extends
  ContentEntityInterface,
  RefrencerEntityInterface,
  StatusedEntityInterface,
  DescribedEntityInterface,
  TimestampedEntityInterface,
  MultiAspectPluginSupportingInterface {

  /**
   * Default email template.
   */
  const EMAIL_TITLE_DEFAULT_TEMPLATE = 'Your link is ready: {{ credit_link }}';

  /**
   * Default email template.
   */
  const EMAIL_BODY_DEFAULT_TEMPLATE = 'Your link is ready: {{ credit_link }}';

  /**
   * Default mail sending schedule.
   */
  const EMAIL_SCHEDULE_DEFAULT_PERIOD = 1;

  /**
   * Default email sending schedule granularity.
   */
  const EMAIL_SCHEDULE_DEFAULT_GRANULARITY = 'month';

  /**
   * Permission constant.
   */
  public const PERMISSION_ADMINISTER = Utility::ADMIN_PERMISSION;

  /**
   * Permission constant.
   */
  public const PERMISSION_CREATE = 'create hidden tab mailer';

  /**
   * Permission constant.
   */
  public const PERMISSION_VIEW = 'view hidden tab mailer';

  /**
   * Permission constant.
   */
  public const PERMISSION_UPDATE = 'update hidden tab mailer';

  /**
   * Permission constant.
   */
  public const PERMISSION_DELETE = 'delete hidden tab mailer';

  /**
   * Permission / Operation constant.
   */
  public const OP_ADMINISTER = HiddenTabMailerInterface::PERMISSION_ADMINISTER;

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
   * Id of template used to render body of the mail.
   *
   * @return string|null
   *   Template used to render body of the mail.
   */
  public function emailTemplate(): ?string;

  /**
   * Id of template used to render title of the mail.
   *
   * @return string|null
   *   Template used to render title of the mail.
   */
  public function emailTitleTemplate(): ?string;

  /**
   * Template used to render body of the mail, stored inline.
   *
   * @return string|null
   *   Template used to render body of the mail.
   */
  public function emailInlineTemplate(): ?string;

  /**
   * Template used to render title of the mail, stored inline.
   *
   * @return string|null
   *   Template used to render title of the mail.
   */
  public function emailTitleInlineTemplate(): ?string;

  /**
   * How often the mail should be sent.
   *
   * @return int|null
   *   How often the mail should be sent.
   */
  public function emailSchedule(): ?int;

  /**
   * Granularity of the emailSchedule().
   *
   * Values: second, minute, hour, day, month, year, week.
   *
   * @return string|null
   *   Granularity of the emailSchedule().
   */
  public function emailScheduleGranul(): ?string;

  /**
   * The next time the mail should be sent, timestamp.
   *
   * @return int|null
   *   The next time the mail should be sent, timestamp.
   */
  public function nextSchedule(): ?int;

}
