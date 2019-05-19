# vBSSO
Provides universal Secure Single Sign-On between vBulletin and different popular platforms like Drupal.

### Description

Newer versions are available at http://www.vbsso.com/download

Provides universal Single Sign-On feature so that Drupal can use the vBulletin user database to manage authentication
and user profile data. There is documentation available so that it can be extended to other platforms.

This plugin is provided as is. Support and additional customizations are available at an hourly rate.

Plugin doesn't share any user related information with any third party side. It strongly synchronizes the information
between your own platforms connected personally to your vBulletin instance.

Plugin doesn't revert already sync data back to its original state if you decide to disable plugin later.

More details are available at http://www.vbsso.com

### Compatibility

Compatible with Drupal 7.x, Drupal 8.x.

### Requirements

* Installed vBulletin 4.x.
* Installed PHP cURL, mCrypt extensions.
* PHP 5.3, 5.4, 5.5, 5.6, 7.0.

### Installation/Upgrade Drupal 7.x

1. Download Drupal vBSSO.
2. Unzip and upload everything to `/sites/all/modules/` directory of your Drupal installation.
3. Log in to Drupal as administrator.
4. Navigate to Modules > OTHER and activate 'vBSSO for Drupal 7.x' plugin.

### Installation/Upgrade Drupal 8.x

* Install the vBSSO module via FTP 

1. The first thing you will need to do is to download and locate the Drupal vBSSO module to your local computer.
2. Unzip and upload the 'vbsso' folder to `/modules/` directory of your Drupal installation.
3. Log in to Drupal as administrator.
4. Navigate to Manage > Extend > List and activate 'vBSSO for Drupal 8.x' plugin.

### Uninstallation

1. [Optional] Navigate to (in case of enabled network) section deactivate vBSSO plugin:
- Drupal 7.x (Modules > OTHER > vBSSO for Drupal 7.x); 
- Drupal 8.x (Manage > Extend > List > vBSSO for Drupal 8.x).
2. [Optional] Remove `/modules/vbsso` directory via FTP.
3. [Optional: Drupal 8.x] Clear Drupal site cache at /admin/config/development/performance by pressing 'Clear all caches' button.

### Configuration

1. Log in to your vBulletin control panel as administrator.
2. Navigate to `vBSSO` section.
3. Expand section and click on the `Platforms` link.
4. Copy `Platform Url` and `Shared Key` fields from Drupal installation to vBulletin vBSSO.
5. Click on `Connect` button to connect your new platform.
6. Back to Drupal vBSSO Settings page and verify that API Connections fields are actually filled out.

More details are available at http://www.vbsso.com/

### Changelog

### 1.1.0
* Added PHP 5.4, 5.5, 5.6, 7.0 support.
* Added support for Drupal 8.x.
* EOL (End-of-life due to the end of life of this version) for Drupal 6.x support.

_[Updated on Updated March 29, 2017]_

### 1.0.3.1
* Fixed "Edit Member Profile in vBulletin" feature.

_[Updated on February 20, 2015]_

### 1.0.3
* Fixed incorrect saving "Usergroups Associations" fields.
* Fixed an issue when authenticated user has different emails.
* Added support of cross-domain single-sign on in Internet Explorer.

_[Updated on February 20, 2015]_

### 1.0.2
* Fixed "Edit Member Profile in vBulletin" issue.

_[Updated on June 07, 2014]_

### 1.0.1
* Added usergroups support.
* Organized vBSSO Settings page.
* Introduced "Login through vBulletin Page" feature.
* Introduced "View My Profile in vBulletin" feature.
* Introduced "View Member Profile in vBulletin" feature.
* Introduced "Edit My Profile in vBulletin" feature.
* Introduced  "Edit Member Profile in vBulletin" feature.
* Other enhancements and bugs fixes.

_[Updated on July 29, 2013]_

### 1.0
* Initial version released.

_[Released on October 26, 2012]_

