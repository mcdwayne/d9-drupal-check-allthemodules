KPLATFORMS
==========

This document describes how to maintain stub makefiles. To understand how to
build platforms using them, please see USAGE.md. To better understand how these
makefiles are structured, please see STRUCTURE.md.

Please follow the installation instructions in INSTALL.md, as we currently use
git versions of both Drush and an extension, make_diff. The usage instructions
below assume that you have followed those instructions exactly.


MAINTENANCE
-----------

For simplicity, we use the stubs/Drupal7.make makefile in our examples. Obvi-
ously, replace this with whichever platform stub makefile is appropriate.

1. Update your makefiles
------------------------

First, we create a temporary lockfile based on a given platform stub:

    $ drush make --no-build --lock=/tmp/make.lock stubs/Drupal7.make
    Wrote .make file /tmp/make.lock

Note: the --lock parameter doesn't work with the ~ path shortcut.

Then, we compare it to the current lockfile, so we can see what changes have
occurred:

    $ drush diff lockfiles/Drupal7.lock /tmp/make.lock --list=0
     Project                Old version  New version  Notices
     drupal                                           download changed.
     [...]
     admin_views            1.2          1.3          version upgraded.
     [...]
     google_analytics       1.4          2.0          WARNING! Major version changed.

Investigate and fix any warnings as appropriate. This will generally involve
pinning a version for a module. This is done by commenting out the module from
the general modules list (i.e., includes/7/modules.make), then adding an entry
in the modules pin list (i.e., /includes/7/modules/pins.make):

    ; The recommended release is on the 2.x branch, so we pin to the latest
    ; supported version on the 1.x branch.
    ; For updates, check: https://drupal.org/project/google_analytics
    projects[goole_analytics][version] = 1

It is usually best to pin to a major version in this case, so that new releases
on that branch are incorporated automatically.

Re-running the commands above should now no longer show any major version
upgrade warnings.


Test the new lockfile
---------------------

It is usually worthwhile to test the new lockfile at this point. For example,
an update to a module could cause a patch to no longer apply. Usually, in such
cases, updating the patches makefile with a new patch, or simply removing
patches that have been incorporated into the new release will be sufficient to
resolve any issues at this stage.

Adding or changing patches or libraries, adding new modules or themes, and
other such modifications can also cause build failures. Running a test build is
as simple as running drush make on the new lockfile:

    $ drush make /tmp/make.lock test_Drupal7


Document platform updates
-------------------------

Along with updating the platforms, it is often useful to document any changes
in release notes. We can generate a complete report fairly easily. First, write
the report we generated earlier into a text file:

    $ drush diff lockfiles/Drupal7.lock /tmp/make.lock --list=0 > lockfiles/Drupal7.RELEASE_NOTES.txt

If there are any remaining warnings in this report, we recommend writing a
brief explanation at the top of the release notes. We can then gather the
release notes from all the updated modules and append that to our report:

    $ drush rln `drush7 diff lockfiles/Drupal7.lock /tmp/make.lock --list` >> lockfiles/Drupal7.RELEASE_NOTES.txt

We now have a nice report showing all updates and other changes to the platform,
an explanation of any major changes or anomalies, and a detailed description of
all code changes across the entire platform.


Finalize the new lockfile
-------------------------

Once all changes and warnings are resolved or accepted, and documented, we copy
the temporary lockfile over the existing one in our repo:

    $ cp /tmp/make.lock lockfiles/Drupal7.lock

Then commit these changes into git:

    $ git commit -am "Update Drupal7 platform."

Once all platforms are updated, tag and push a new release.

SCRIPTS
=======

Rebuild all lockfiles
---------------------

    $ ./scripts/rebuild-kplatforms.sh

Note: lockfiles and release notes are created in the build/ directory.

Make HTML release notes from the build directory
------------------------------------------------

    $ ./scripts/make-releasenotes.sh

Build and run tests on all platforms
------------------------------------

    $ ./scripts/test-kplatforms.sh

Example build from lockfiles
----------------------------

    $ ./scripts/build-all.sh

Note: this will run make clean then rebuild from the lockfiles that already
exist.

Example deployment to server
----------------------------

To deploy the existing build of Drupal8 (assuming it's been made locally
already) to /var/aegir/platforms/Drupal_8.4.5_`date +'%Y.%m.%d'` on the
host example.com.

`
PLATFORM=Drupal8 DEPLOY_BASENAME=Drupal_8.4.5 DEPLOY_HOST=example.com make deploy-platform
`

You can also create a configuration file (see deploy.yml.dist) to deploy
to multiple servers. If you have such a configuration you can deploy like this:

`
./scripts/deploy.sh
`

