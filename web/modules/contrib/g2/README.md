Glossary2 (G2)
==============

(c) 2005-2015 Frederic G. MARAND

Licensed under the CeCILL version 2.1 and General Public License version 2 and later.

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/FGM/g2/badges/quality-score.png?b=8.x-1.x)](https://scrutinizer-ci.com/g/FGM/g2/?branch=8.x-1.x)

**WARNING** 2015-11-28 : This version of the project is _not_ usable at the
  moment : it is very much a work in progress. An initial usable version is
  expected before 2016-01-01.



0. Table of contents
--------------------

  0. Table of contents
  1. Introduction
  2. Completion status
  3. Prerequisites
  4. Version warnings
  5. Installing / upgrading / uninstalling
  6. Ruby XML-RPC client
  7. Notice


1. Introduction
---------------

G2 is a glossary management module written for Drupal.

It is not intended as a direct replacement for glossary.module, which has been
available with drupal for a number of years, or the more recent lexicon.module,
but as an alternative for sites needing a glossary for a large number of
entries, or a different feature set.

Unlike [glossary] or [lexicon], it uses nodes instead of terms to hold
definitions, and does not automatically link terms in other nodes to their
entries in G2, but relies on <dfn> markup in definitions, and can link to terms
in multi-byte character sets and terms containing special characters like
slashes or ampersands without specific markup.

[glossary]: https://www.drupal.org/project/glossary
[lexicon]: https://www.drupal.org/project/lexicon

It has been designed to handle glossaries with many thousands of nodes without
slowing or increasing its memory requirements significantly.

Its development to this date has been entirely sponsored by OSInet.fr


* Project page on Drupal.org: http://drupal.org/project/g2
* Documentation wiki (contributions welcome) http://wiki.audean.com/g2/start
* Sample implementation (about 6000 terms, localized to french) http://www.riff.org/glossaire


2. Completion status
### Main features

| Blocks    | Service     | API       | Block / controller  |
|-----------|-------------|-----------|---------------------|
| Alphabar  | Done        | Working   | Working             |
| API       | n.a.        | Working   | n.a.                |
| Latest    | Working     | Working   | Working             |
| Random    |             |           |                     |
| Top       | Working     | Working   | Working             |
| WOTD      |             |           |                     |

* 'Done' status means working and with high test coverage
* 'Working' status means appears to work but not (completely) tested

### Pages

| Page          | Status
|---------------|-------------------------------------------|
| main          | Working                                   |
| entries       | Working                                   |
| initial       | Working                                   |
| node add form | Working                                   |
| settings      | Working                                   |
| WOTD feed     |                                           |

At this point in the module port, the composer.json file is only here to allow
Scrutinizer to find core classes, not for actual deployment.

---

Everything below this line related to the 7.x-1.x branch, and is likely to be
incorrect for 8.x-1.x

---

3. Prerequisites
----------------

  * Any Drupal versions from 4.7.x to 7.0
  * MySQL 5.x, configured for UTF-8 encoding (collating: utf8_general_ci)
  * PHP 5.3 for 7.x versions, PHP 5.1 before


4. VERSION WARNINGS
-------------------

Since 2009-09-27:
- sites not configured with clean URLs are no longer taken into account
- the module is only maintained/evolved for the Drupal 6.x and 7.x branches.
- A 5.0 version exists, but only to ease upgrades from 4.7 to 6.

5. Installing / upgrading / uninstalling
----------------------------------------
5.1 Installing
--------------

Installing is Drupal standard: just copy the module, enable it and configure it:
- module configuration is at admin/config/content/g2
- module blocks are individually configurable at
  - admin/structure/block/manage/g2/alphabar
  - admin/structure/block/manage/g2/latest
  - admin/structure/block/manage/g2/random
  - admin/structure/block/manage/g2/top
  - admin/structure/block/manage/g2/wotd

5.2 Upgrading
-------------

- 4.7 -> 5, 5 -> 5, 5 -> 6, 7 -> 7: standard Drupal updates
- 5 -> 7, 6 -> 7: the configuration upgrade is in place; however, there is
  currently no upgrade path for data, due to the switch from node module fields
  to Field API. This is expected to be resolved by the time the first stable
  version for Drupal 7 is released.
- 4.7 -> 6: no direct update: update to the latest 5, then 5 -> 6
- 4.7 -> 7: no direct update: update to the latest 5, then 5 -> 6, then 6 -> 7,
  but see limitations regarding 6.x -> 7.x above.

5.3 Uninstalling
----------------

WARNING: Should you want to uninstall the module, take care to first remove
all G2 nodes before removing the module. This includes:

- your glossary definitions,
- the unpublished page used for the glossary home page skeleton.
- the unpublished page used for the disambiguation skeleton

Unless you do this, you will have inconsistent nodes in your system, because
Drupal will be missing the module to load G2 entries. If you do not modify
any of these nodes, reinstalling the module will restore consistency and
enable a clean node deletion and uninstall later on.

6. Ruby XML-RPC client
----------------------

This version includes a Ruby client demonstrating how to use the G2
XML-RPC services from non-Drupal code. Keep in mind this is basic demo
code, that should probably not be used without extra care in production.

7. NOTICE
---------

7.1 Statistics
--------------

The statistics displayed on the "entries starting by initial ..." page
at URL <drupal>/g2/initial/<some initial segment> mention :

"Displaying 0 entries starting by ... from a total number of ... entries.

It must be understood that this "total number" is actually the total number
a user without administrative permissions can see, that is, published entries.
The "published" epithete is not used because site visitors are not expected
to be aware of the publishing process.

7.2 Random block
----------------

This block only works if the glossary has at least three visible entries.
Since G2 is designed for large glossaries, this is not considered a bug.
