INTRODUCTION
============

The Search Configuration module has two primary functions, to alter how the
search form is rendered and to provide restrictions on the content that is
exposed when searching.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/search_config

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/search_config


DESCRIPTION
===========

The basic functionality of this module is to configure the search forms so as to give an easy user interface for the
users during the search mechanism.

The key features are listed below:

 * Remove the basic search form if advanced search form is present.
 * Move the basic keyword search to advanced search form.
 * Options to override advanced forms fieldset.
 * Options to specify the relative positions of the field labels. i.e, either the default or below the field or in the
   hidden state.
 * Options to alter the field labels of the basic and the advanced search forms.
 * Options to select the users who can access various fields.
 * Replace the type options with custom type groups.
 * Options to alter the pager limit.

This module has been ported to Drupal 8 as part of the Google Summer of Code 2016 project.

PREREQUISITES
=============

Search module (Drupal core).

INSTALLATION
============

Standard module installation.

See https://www.drupal.org/docs/7/extending-drupal/overview for further information.

CONFIGURATION
============

* Form Appearance

  Navigate to Administration » Configuration » Search pages »

  "Labels and string overrides" fieldset

  This section allows you to configure the field labels. Options include:
    - alter the field titles of the basic and advanced search forms
    - title position. These can be positioned above (default), below or hidden

  You can basically see two configuration options: Labels and string overrides and Additional node search configuration.
  The labels and string overrides is to configure the field labels. You have options to alter the field titles of the
  basic and advanced search forms.

  You also have the liberty to specify the relative position of the field title. It can be above the field (the
  default format), below the field or in the hidden state.

  Additional node search configuration configures the additional features and make this module more lively. Once you
  select the additional node search configuration option, you have the options to deal with form control, removing
  ... keyword options, selecting the roles who can see the fields, replacing type option with custom type groups and
  setting the pager limit.

  Form control allows you to configure the search form in such a way that only one form is displayed at a given instant.
  This is achieved by selecting the checkbox 'Only show one form at a time.' in the form control. Another option is to
  move the basic keyword search to advanced search form.

  You can also replace the type option available with custom type groups. i.e, you can search based on content types of
  your choice. This is done by adding the required content type in the field 'Replace type options with custom type
  groups'.

  You could also configure the maximum number of results to be displayed in a page. You can select the value of your
  choice from the drop down menu of 'Pager limit' in the results section.

* Configure user permissions

  Navigate to Administration » People » Permissions:

  - Exclude nodes from search results

    This allows users with this permission to flag content as not to be searched.

    You can select the roles who can view particular fieldset.
    There are basically three types of roles; Anonymous user, Authenticated user and the Administrator. You could
    set the privileges to be assigned to the various types of users through this search configuration tool.


MAINTAINERS
============

Current maintainers:
Naveen Valecha - <https://www.drupal.org/u/naveenvalecha>
Joyce George - <https://www.drupal.org/u/joyceg>

Past maintainers:
Joseph Yanick - https://www.drupal.org/u/jbomb
