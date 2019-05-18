## CONTENTS OF THIS FILE
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Using
 * Maintainers
 
## INTRODUCTION

Friendship module.

 * For a full description of the module, visit the project page:
   [https://www.drupal.org/project/friendship]

 * To submit bug reports and feature suggestions, or to track changes:
   [https://www.drupal.org/project/issues/friendship]

## REQUIREMENTS

This module will requires ds module dependencies.

## INSTALLATION
 
 * Install as you would normally install a contributed Drupal module. Visit:
   [https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules]
   for further information.

## CONFIGURATION
  * You can configure some parametrs on this page /admin/config/people/friendship-settings
## USING
  You can add friendship link to user account display:
  * Enable ds layout on manage display for user.
  * Now you can see "Friendship process link", place field where you want.
  You can add this link using views:
  * Create viwe by user or use user relation in view.
  * In user fields you can find "Friendship proccess link".
  Module provide 3 additional statistic fields for user:
  * Number of friends.
  * Number of followers.
  * Number of following.
  If you want filter user by friends, followers or following:
  * Add relation ship for friendship (apper on user in relation popup).
  * Add contextual filter by current user id (this field apper in friendship fields block after you add friendship relation).
  * In filter block apper new filter (Friendship status).

## MAINTAINERS

Current maintainers:
 * [Vitaliy Bogomazyuk (VitaliyB98)](https://www.drupal.org/u/vitaliyb98)
 
## This project has been sponsored by:
 * [Internetdevels](https://www.drupal.org/internetdevels)
