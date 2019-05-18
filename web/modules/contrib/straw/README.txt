CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Basic usage
 * Maintainers

INTRODUCTION
------------

The Straw (Super Term Reference Autocomplete Widget) module provides a new
interface for associating taxonomy terms with content using a term reference
field. It looks just like a normal placeholder select widget, but it shows the
whole tag hierarchy both when displaying existing values, and it both shows and
searches the whole hierarchy and when finding matches for the autocomplete
dropdown. If term creation is enabled, it also allows a new term to be created
along with all of its parents that don't exist already.

* To submit bug reports and feature suggestions, or to track changes:
- https://www.drupal.org/project/issues/straw


REQUIREMENTS
------------

At this time Straw can only be used with term reference fields. As such, the
core taxonomy module must be enabled in order to make use of it.


INSTALLATION
------------

Install this module via composer by running the following command:

* composer require drupal/straw


CONFIGURATION
------------
Configuring a field widget to use Straw requires two steps. First, in the
settings for the field, choose the "Straw selection" reference method. Then, for
the field widget settings on the Manage Form Display tab, choose "Autocomplete
(Straw style)".


BASIC USAGE
------------

Once Straw is configured for a field, that field will show all the parents of
the selected terms in addition to the term itself. Each level of the hierarchy
is separated from the others by two right-facing arrows (>>), and if term
creation is enabled for the field, these same separators can also be used
between levels of the hierarchy when typing in a new term, and all terms in the
hierarchy that don't already exist will get created. For instance, entering
"Travel >> Tourist Destinations" will create two terms, "Travel" and "Tourist
Destinations", with the latter as a child of the former.


MAINTAINERS
------------

Current maintainers:
 * Clint Randall (camprandall) - https://drupal.org/u/camprandall
 * Jay Kerschner (JKerschner) - https://drupal.org/u/jkerschner
 * Brian Seek (brian.seek) - https://drupal.org/u/brianseek
 * Mike Goulding (mikeegoulding) - https://drupal.org/user/mikeegoulding

This project has been sponsored by:
 * Ashday Interactive Systems
   Building your digital ecosystem can be daunting. Elaborate websites,
   complex cloud applications, mountains of data, endless virtual wires
   of integrations.
