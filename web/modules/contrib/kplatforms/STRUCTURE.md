KPLATFORMS
==========

This document describes the filesystem layout for kplatforms makefiles. To
understand how to use them to build platforms, please see USAGE.md. To better
understand how these makefiles are maintained, please see MAINTENANCE.md.


STRUCTURE
---------

For simplicity, we are limiting our discussion to the Drupal7 and CiviCRM4-D7
platforms. Several more platforms are included in kplatforms, and this system
scales very well. However, listing them all here would complicate explanations.

Filesystem layout
-----------------

Drush makefiles allow for the inclusion of other makefiles. In order to reduce
duplication and encourage these makefiles to be self-documenting, kplatforms
makes extensive use of makefile includes.

Here is the (abridged) layout of files and directories:

    $ tree
    .
    ├── includes
    │   ├── 6
    │   │   └── ...
    │   ├── 7
    │   │   ├── apachesolr.make
    │   │   ├── civicrm-44.make
    │   │   ├── civicrm-l10n.make
    │   │   ├── civicrm-modules.make
    │   │   ├── core.make
    │   │   ├── dev
    │   │   │   └── libraries.make
    │   │   ├── dev.make
    │   │   ├── geo.make
    │   │   ├── modules
    │   │   │   ├── libraries.make
    │   │   │   ├── patches.make
    │   │   │   └── pins.make
    │   │   ├── modules.make
    │   │   ├── payment.make
    │   │   └── themes.make
    │   ├── 8
    │   │   └── ...
    ├── lockfiles
    │   ├── CiviCRM4-D7.lock
    │   ├── CiviCRM4-D7.RELEASE_NOTES.txt
    │   ├── Drupal7.lock
    │   ├── Drupal7.RELEASE_NOTES.txt
    │   └── ...
    └── stubs
        ├── CiviCRM44-D7.make
        ├── Drupal7.make
        └── ...

There are 3 top-level directories: includes, lockfiles and stubs. We examine
each type in more detail later. The lockfiles and stubs directories simply
contain lockfiles and release notes in the former, and stub makefiles in the
latter, with no further hierarchy.

The 'includes' directory, on the other hand, has subdirectories that match a
Drupal core version. Since Drupal does not support compatibility between major
versions of core, there is no re-use across them.


Stubs
-----

Stubs are makefiles that usually do not contain any projects to download
directly. Instead, they usually include a version of core, and various other
makefiles to build up a full platform.

In the example below, we see that the contents are pretty basic. The is docu-
mentation throughout, as well as the basic Drush make 'api' and 'core'
elements. We also set a default sub-directory within which to download
projects. This allows us to keep our included makefiles cleaner.

We then include a core makefile, a number of module makefiles and a theme
makefile. Note that we specify the location of the include file relative to the
location of the stub. We also see an example of an include that has been
commented-out (dev.make). Since that makefile contains developer modules that
are not appropriate for a production platform, we exclude it. But it is simply
a matter of un-commenting that line, to add those components when we want to
build a development platform.


    ;****************************************
    ; General
    ;****************************************

    ; Description
    ; A drush stub makefile for Drupal 7.x.

    ; drush make API version
    api = 2

    ; Drupal core
    core = 7.x

    ; Defaults
    defaults[projects][subdir] = "contrib"

    ;****************************************
    ; Includes
    ;****************************************

    ; Core
    includes[] = ../includes/7/core.make

    ; Modules
    includes[] = ../includes/7/apachesolr.make
    ;includes[] = ../includes/7/dev.make
    includes[] = ../includes/7/geo.make
    includes[] = ../includes/7/modules.make
    includes[] = ../includes/7/payment.make

    ; Themes
    includes[] = ../includes/7/themes.make

    ;****************************************
    ; End
    ;****************************************


Includes
--------

Within a given Drupal major version directory (e.g. 7), we find a number of
makefiles. Some of these group together components that are related (e.g.,
geo.make), and allows for them to easily be left out of platforms where they
are irrelevant. We also find a core makefile that includes a core distribution
and any patches we need to apply to Drupal. These included makefiles have
standard headers similar to the stub makefile above.

Then we have a larger general list of modules in modules.make, an excerpt of
which can be found below. This makefile lists all the modules that will be
downloaded as part of the platform, except those that were segmented into their
own dedicated makefiles.

Note how some of these projects have been commented out, and labelled 'pinned'
or 'patched'. These modules will be found in the appropriate makefile that we
see included at the bottom of modules.make.

To keep this list easy to read, so that we can get an idea of the contents of a
platform at a glance, we split out associated libraries and patches. Also, we
collect all modules that need to specify a particular version in the pins.make.


    [...]

    ;****************************************
    ; Modules
    ;****************************************

    projects[] = adminrole
    [...]
    ;projects[] = backup_migrate  // pinned
    [...]
    ;projects[] = entityreference  // patched
    [...]
    projects[] = xmlsitemap

    ;****************************************
    ;* Libraries and Patches
    ;****************************************

    includes[] = modules/libraries.make
    includes[] = modules/patches.make
    includes[] = modules/pins.make

    ;****************************************
    ; End
    ;****************************************


Libraries and patches
---------------------

How Drush make allows libraries and patches to be specified is beyond the
scope of this documentation. Refer to the latest Drush make documentation for
details:

 * libraries: https://github.com/drush-ops/drush/blob/master/docs/make.txt#L238
 * patches: https://github.com/drush-ops/drush/blob/master/docs/make.txt#L84

These components often span multiple lines, and should usually include some
additional documentation. While not strictly required, we split these compo-
nents out into their own makefiles, since legibility suffers greatly when they
are included directly in modules.make.


Pins
----

When maintaining sites on a stable platform, we generally want to avoid major
version upgrades for modules during regular maintenance updates. As such, we
can pin a module to a major version like so:

    [...]
    ; The recommended release is on the 2.x branch, so we pin to the latest
    ; supported version on the 1.x branch.
    ; For updates, check: https://drupal.org/project/google_analytics
    projects[google_analytics][version] = 1
    [...]

Sometimes even a minor version update introduces a new bug, breaks custom code,
or causes some other issue. In such cases, we can specify an exact version of a
module in a similar manner to the above.

Pinning versions ought to be the exception, as fixing the underlying issue(s)
should always be preferred. So, when updating lockfiles (see MAINTENANCE.md),
we should always ensure there remains good reason to pin the modules listed in
pins.make.

Refer to the latest Drush make documentation for further details:

 * versions: Complete https://github.com/drush-ops/drush/blob/master/docs/make.txt#L71


Lockfiles
---------

Finally, the lockfiles directory includes makefiles that have been run through
'drush make --lock --no-build' (see MAINTENANCE.md). This basically takes our
stub makefile, compiles all the inclusions, and then checks with drupal.org to
determine the latest versions of all the components, just as if they were all
going to be downloaded in a normal run of Drush make. It then writes this out
into a new makefile that now specifies all the up-to-date versions. This form
of makefile is called a lockfile, similar to what we see with Composer or Gem.

In addition, this directory includes release notes for the latest versions of
the lockfiles. These are simple reports showing the changes since the previous
release, as well collecting all the release notes from any updated projects.
