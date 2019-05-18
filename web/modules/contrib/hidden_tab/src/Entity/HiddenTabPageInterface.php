<?php

namespace Drupal\hidden_tab\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\hidden_tab\Entity\Base\DescribedEntityInterface;
use Drupal\hidden_tab\Entity\Base\RefrencerEntityInterface;
use Drupal\hidden_tab\Entity\Base\StatusedEntityInterface;
use Drupal\hidden_tab\Entity\Base\TimestampedEntityInterface;
use Drupal\hidden_tab\Utility;

/**
 * Provides an interface defining a hidden_tab_page entity type.
 *
 * This module adds tabs to entities, (tabs are called pages interchangeably)
 * They are called hidden because they may also be accessed from a secret Uri
 * (the tab URI with a hash key).
 *
 * This configuration entity stores all the pages (tabs) created by the site
 * builders.
 *
 * @see \Drupal\hidden_tab\Entity\HiddenTabPage
 */
interface HiddenTabPageInterface extends
  ConfigEntityInterface,
  RefrencerEntityInterface,
  StatusedEntityInterface,
  DescribedEntityInterface,
  TimestampedEntityInterface {

  /**
   * Permission constant.
   */
  const PERMISSION_ADMINISTER = Utility::ADMIN_PERMISSION;

  /**
   * Permission constant.
   */
  const PERMISSION_CREATE = 'create hidden tab page';

  /**
   * Permission constant.
   */
  const PERMISSION_VIEW = 'view hidden tab page';

  /**
   * Permission constant.
   */
  const PERMISSION_UPDATE = 'update hidden tab page';

  /**
   * Permission constant.
   */
  const PERMISSION_DELETE = 'delete hidden tab page';

  /**
   * Permission / Operation constant.
   */
  const OP_ADMINISTER = self::PERMISSION_ADMINISTER;

  /**
   * Permission / Operation constant.
   */
  const OP_VIEW = 'view';

  /**
   * Permission / Operation constant.
   */
  const OP_UPDATE = 'update';

  /**
   * Permission / Operation constant.
   */
  const OP_DELETE = 'delete';

  // -----

  /**
   * Permission constant.
   */
  const PERMISSION_VIEW_ALL_TABS = 'view all hidden tab pages';

  /**
   * Permission constant.
   */
  const PERMISSION_VIEW_ALL_URIS = 'view all hidden tab pages via uri';

  /**
   * Permission constant.
   */
  const PERMISSION_VIEW_SECRET_URI = 'view secret uri';

  /**
   * Permission / Operation constant.
   */
  const OP_VIEW_SECRET_URI = self::PERMISSION_VIEW_SECRET_URI;

  /**
   * For new entities, default permission to access via tab uri.
   */
  public const TAB_PERMISSION_DEFAULT_PERMISSION = Utility::ADMIN_PERMISSION;

  /**
   * See credit charging service for more information.
   *
   * @see \Drupal\hidden_tab\Service\CreditChargingInterface
   */
  public const DEFAULT_CREDIT_CHECK_ORDER = 'xex pex pxx peu xeu pxu xxu';

  // -----

  /**
   * Uri from which the page can be accessed, a single machine-name word.
   *
   * This word is appended after the entity id, in form of /entity/{id}/{uri} to
   * create a path.
   *
   * @return string|null
   *   Returns uri from which the page can be accessed, a single machine-name
   *   word. This uri is appended to the entity uri. So if the uri of a page is
   *   'hello' (value of the method) the final generated Uri will be
   *   /entity/123/hello.
   */
  public function tabUri(): ?string;

  /**
   * The secret URI from which the tab can be accessed from.
   *
   * @return string
   *   The secret URI from which the tab can be accessed from.
   */
  public function secretUri(): ?string;

  /**
   * If not found is showed instead of access denied on illegal access to tab.
   *
   * @return bool
   *   If not found is showed instead of access denied on illegal access to tab.
   */
  public function isAccessDenied(): bool;

  /**
   * Permission required to access the tab on entity.
   *
   * @return string|null
   *   Permission required to access the tab on entity.
   */
  public function tabViewPermission(): ?string;

  /**
   * The permission user must posses to access the secret uri.
   *
   * @return string
   *   The permission user must posses to access the secret uri.
   */
  public function secretUriViewPermission(): ?string;

  /**
   * Order of checking credit using credit service (page, then user, then...).
   *
   * @return string[]
   */
  public function creditCheckOrder(): array;

  /**
   * Name of template used to generate the page.
   *
   * Each page has a template (which defines it's layout). Templates are
   * provided by template plugins.
   *
   * The templates should contain named regions. Later, printable komponents
   * will be put into these regions and passed to the template file provided by
   * the plugin to render.
   *
   * @return string|null
   *   Name of template used to generate the page.
   *
   * @see \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplateInterface
   */
  public function template(): ?string;

  /**
   * An inline string, an inline twig template used to render the page.
   *
   * Regions, according to the inlineTemplateRegionCount(), are available as
   * context value like regions.region_0, regions.region_1 and so on. Also
   * current_user is available. Site builders may create a template on the fly.
   * An inline template overrides template().
   *
   * @return string|null
   *   An inline string, a twig template, used to render the page.
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPageInterface::inlineTemplateRegionCount()
   */
  public function inlineTemplate(): ?string;

  /**
   * How many regions the inline template has.
   *
   * @return int
   *   Number of regions available in the inline template.
   *
   * @see \Drupal\hidden_tab\Entity\HiddenTabPageInterface::inlineTemplate()
   */
  public function inlineTemplateRegionCount(): int;

}
