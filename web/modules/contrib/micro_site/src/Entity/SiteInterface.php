<?php

namespace Drupal\micro_site\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\file\FileInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\micro_site\SiteUsers;

/**
 * Provides an interface for defining Site entities.
 *
 * @ingroup micro_site
 */
interface SiteInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface, EntityPublishedInterface {

  /**
   * Gets the Site name.
   *
   * @return string
   *   Name of the Site.
   */
  public function getName();

  /**
   * Sets the Site name.
   *
   * @param string $name
   *   The Site name.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface
   *   The called Site entity.
   */
  public function setName($name);

  /**
   * Gets the Site mail.
   *
   * @return string
   *   Email of the Site.
   */
  public function getEmail();

  /**
   * Sets the Site mail.
   *
   * @param string $mail
   *   The Site mail.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface
   *   The called Site entity.
   */
  public function setEmail($mail);

  /**
   * Gets the Site slogan.
   *
   * @return string
   *   Slogan of the Site.
   */
  public function getSlogan();

  /**
   * Sets the Site slogan.
   *
   * @param string $slogan
   *   The Site slogan.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface
   *   The called Site entity.
   */
  public function setSlogan($slogan);

  /**
   * Gets the piwik id.
   *
   * @return int
   *   The piwik id.
   */
  public function getPiwikId();

  /**
   * Sets the piwik id.
   *
   * @param string $piwik_id
   *   The piwik id.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface
   *   The called Site entity.
   */
  public function setPiwikId($piwik_id);

  /**
   * Gets the piwik url.
   *
   * @return string
   *   The piwik url without the scheme.
   */
  public function getPiwikUrl();

  /**
   * Sets the piwik url without the scheme.
   *
   * @param string $piwik_url
   *   The piwik url.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface
   *   The called Site entity.
   */
  public function setPiwikUrl($piwik_url);

  /**
   * Gets the Site logo.
   *
   * @return \Drupal\file\FileInterface
   *  The file entity.
   */
  public function getLogo();

  /**
   * Sets the Site logo.
   *
   * @param \Drupal\file\FileInterface $logo
   *   The logo file entity.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface
   *   The called Site entity.
   */
  public function setLogo(FileInterface $logo);

  /**
   * Gets the Site favicon.
   *
   * @return \Drupal\file\FileInterface
   *  The file entity.
   */
  public function getFavicon();

  /**
   * Sets the Site favicon.
   *
   * @param \Drupal\file\FileInterface $favicon
   *   The favicon file entity.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface
   *   The called Site entity.
   */
  public function setFavicon(FileInterface $favicon);

  /**
   * Gets the Site creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Site.
   */
  public function getCreatedTime();

  /**
   * Sets the Site creation timestamp.
   *
   * @param int $timestamp
   *   The Site creation timestamp.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface
   *   The called Site entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Site css.
   *
   * @return string
   *   The css code.
   */
  public function getCss();

  /**
   * Sets the Site css.
   *
   * @param string $css
   *   The Site css.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface
   *   The called Site entity.
   */
  public function setCss($css);

  /**
   * Returns the Site registered status indicator.
   *
   * Registered Site are only visible to restricted users. This status is
   * mandatory to be able to manage content, users, menu on the site. A site
   * registered is always accessible from its site URL.
   *
   * @return bool
   *   TRUE if the Site is registered.
   */
  public function isRegistered();

  /**
   * Sets the registered status of a Site.
   *
   * @param bool|null $registered
   *   TRUE to set this Site to registered, FALSE to set it to unregistered.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface
   *   The called Site entity.
   */
  public function setRegistered($registered = NULL);

  /**
   * Returns the Site published status indicator.
   *
   * Unpublished Site are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Site is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Site.
   *
   * @param bool|null $published
   *   TRUE to set this Site to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface
   *   The called Site entity.
   */
  public function setPublished($published = NULL);

  /**
   * Gets the Site revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Site revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface
   *   The called Site entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Site revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Site revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface
   *   The called Site entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Gets the host url for sites entities set in the configuration module.
   *
   * @return string
   *   A URL string for the host base path to the site. (e.g. example.com)
   */
  public static function getHostBaseUrl();

  /**
   * Gets the master public url set in the configuration module.
   *
   * @return string
   *   A URL string for the public master host url. (e.g. www.example.com)
   */
  public static function getHostPublicUrl();

  /**
   * Gets the path for a site.
   *
   * @return string
   *   A URL string for the base path to the site. (e.g. http://example.com/)
   */
  public function getSitePath();

  /**
   * Gets the url type for a site.
   *
   * @return string
   *   A strind subdomain|domain
   */
  public function getTypeUrl();

  /**
 * Gets the url for a site.
 *
 * @return string
 *   A URL string for the base url to the site. (e.g. example.com/)
 */
  public function getSiteUrl();

  /**
   * Is the site must have a menu associated.
   *
   * @return boolean
   *   TRUE if the site must have a menu.
   */
  public function hasMenu();

  /**
   * Is the site must have a vocabulary associated.
   *
   * @return boolean
   *   TRUE if the site must have a dedicated vocabulary.
   */
  public function hasVocabulary();

  /**
   * Get the menu machine name associated with the site.
   *
   * @return string
   *   The menu machine name.
   */
  public function getSiteMenu();

  /**
   * Get the vocabualry machine name associated with the site.
   *
   * @return string
   *   The vocabulary machine name.
   */
  public function getSiteVocabulary();

  /**
   * Set the menu machine name associated with the site.
   *
   * @param string $site_menu
   *   The menu machine name.
   *
   * @return |Drupal\micro_site\Entity\SiteInterface
   *   The site entity.
   */
  public function setSiteMenu($site_menu);

  /**
   * Set the vocabulary machine name associated with the site.
   *
   * @param string $site_vocabulary
   *   The vocabulary machine name.
   *
   * @return |Drupal\micro_site\Entity\SiteInterface
   *   The site entity.
   */
  public function setSiteVocabulary($site_vocabulary);

  /**
   * Gets the scheme for a site.
   *
   * @return string
   *   A URL string for the scheme site. (e.g. http oir https)
   */
  public function getSiteScheme();

  /**
   * The Shield site is enabled.
   *
   * @return boolean
   *   TRUE if shield is enabled.
   */
  public function getSiteShield();

  /**
   * Gets the shield user.
   *
   * @return string
   *   The user shield.
   */
  public function getSiteShieldUser();

  /**
   * Gets the shield user's password.
   *
   * @return string
   *   The user's password shield.
   */
  public function getSiteShieldPassword();

  /**
   * Get the site users entities for a given field_name.
   *
   * @param string $field_name
   *   The field name which reference site users.
   *
   * @return \Drupal\user\UserInterface[]
   *   The users entities.
   */
  public function getUsers($field_name = SiteUsers::MICRO_SITE_ADMINISTRATOR);


  /**
 * Get the site users id for a given field_name.
 *
 * @param string $field_name
 *   The field name which reference site users.
 * @param boolean $return_entity
 *   If TRUE, return an array of user entity.
 *
 * @return array|\Drupal\user\UserInterface[]
 *   An array of the users id or users entity.
 */
  public function getUsersId($field_name = SiteUsers::MICRO_SITE_ADMINISTRATOR, $return_entity = FALSE);

  /**
   * Get all the site users id.
   *
   * @param boolean $return_entity
   *   If TRUE, return an array of user entity.
   *
   * @return array|\Drupal\user\UserInterface[]
   *   An array of the users id or users entity.
   *
   */
  public function getAllUsersId($return_entity = FALSE);

  /**
   * Get the site administrator users id.
   *
   * @param boolean $return_entity
   *   If TRUE, return an array of user entity.
   *
   * @return array|\Drupal\user\UserInterface[]
   *   An array of the users id or users entity.
   *
   */
  public function getAdminUsersId($return_entity = FALSE);

  /**
   * Get the site manager users id.
   *
   * @param boolean $return_entity
   *   If TRUE, return an array of user entity.
   *
   * @return array|\Drupal\user\UserInterface[]
   *   An array of the users id or users entity.
   *
   */
  public function getManagerUsersId($return_entity = FALSE);

  /**
   * Get the site contributor users id.
   *
   * @param boolean $return_entity
   *   If TRUE, return an array of user entity.
   *
   * @return array|\Drupal\user\UserInterface[]
   *   An array of the users id or users entity.
   *
   */
  public function getContributorUsersId($return_entity = FALSE);

  /**
   * Get internal file uri.
   *
   * @return string
   *   Internal file uri like like public://micro_site_asset/...
   */
  public function cssInternalFileUri();

  /**
   * Get asset file path relative to drupal root to use in library info.
   *
   * @return string
   *   File path relative to drupal root, with leading slash.
   */
  public function cssFilePathRelativeToDrupalRoot();

  /**
   * Gets a micro site data value with the given key.
   *
   * Used to store various data related to custom configuration for a micro site.
   *
   * @param string $key
   *   The key.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The value.
   */
  public function getData($key, $default = NULL);

  /**
   * Sets an micro site data value with the given key.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The value.
   *
   * @return $this
   */
  public function setData($key, $value);

}
