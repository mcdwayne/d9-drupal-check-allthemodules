INTRODUCTION
------------
 
Allows the setting of a release number, to keep track of released
versions of your site. This will allow you to determine if a new build
was successfully released to the server and that the new configuration
was imported just by looking at the status report page.

HOW TO USE
----------

The release number will be show on the status page. If the version is
shown without errors but doesn't match your new release number, the new
configuration was not deployed successfully. If the release number in
the sync configuration doesn't match the release number in the active
configuration an error will be shown in the status report that the
active configuration is out of date.

Update the release number and export config before tagging a new
release. This will allow you to check on the live/staging server if your
release has been successfully uploaded and imported.

The drush command release-tracker-bump (rtb) is available to bump the release
number. You can use 'major', 'minor' or 'patch' (default) to specify the type of
release bump you wish to do.

Using drush:
- drush release-tracker-bump
- Export: drush cex
- Add your exported config to version control
- Tag commit for release
    
Using the UI:
- Can't be done, that way nobody can manually fake the release.

