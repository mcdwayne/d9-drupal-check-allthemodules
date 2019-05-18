-- SUMMARY --

An overview in the status report at '../admin/reports/status' of fundamental GIT
info for all repos, from the Drupal root to the deepest GIT folder it detects.

Provides per GIT directory:
- status (current branch, untracked files and local file changes)
- tag (if it does not exist probably branches are used for release management)
- last pull date
- last commit
- ignored existing folders and files
- submodules.

If you find locally changed files (highlighted) ON THE SERVER it usually means
trouble (including hacking attempts) as they should all be deployed under
version control. The 'sites/*/files' folder and similar are in the '.gitignore'
file. It is recommended to have the complete Drupal folder under GIT version
control to detect any changes. Use 'git init' on it if this is not the case.

On your local development environment changed files indicate you have to stage
(with 'git add'), commit or push your changes. In the status report it is all
pointed out.


-- REQUIREMENTS --

- GIT should be installed.
- Unix based OS (Linux or OSX).
- The 'shell_exec' PHP function has NOT been disabled in the php.ini.
- Some folder(s) under Git version control, preferably also the Drupal root.


-- CONCEPT --

The module executes several git commands on the current environment and shows
the output on the status report at '../admin/reports/status'. It uses
shell_exec() for it (see https://stackoverflow.com/a/39681338/523688).

It shows the info for all git folders it detects recursively.

Used commands:

--- git --version ---
Shows the current git version (and checks if git is available).

--- locate "$PWD*/.git" | grep -e ".git$" | sort ---
See https://stackoverflow.com/a/8838668/523688.
To create a list of all detected git folder to loop through with below commands.

We use 'locate' instead of 'find' because it returns the full paths of the git
folders. See https://unix.stackexchange.com/a/124758/139407 if a GIT folder does
not show immediately.

--- git status ---
The current branch, detects untracked files and local file changes. The whole
GIT Info section shows in the Warnings found section if any of the below texts
are found:
- 'Changed but not updated' (for older Git versions)
- 'Changes not staged for commit'
- 'Changes to be committed'
- 'Your branch is ahead' (you should push your changes)
- 'Your branch is behind' (you should pull changes from the repo)

These texts are then notified by being highlighted in red. If all is fine you
should not have any changed files ON THE SERVER as all files are deployed under
version control by Git. In that case the GIT Info section shows green colored
(OK). On your local machine a warning here indicates you have some files to
stage (with 'git add'), commit or push.

--- git tag --points-at HEAD ---
See https://stackoverflow.com/a/37497511/523688.
All tags on current HEAD (or commit). If no tag exists on a server environment
probably branches are used for release management.

--- stat -c %Y .git/FETCH_HEAD ---
See https://stackoverflow.com/a/9229377/523688.
Last pull timestamp.

--- git log -1 --pretty="Last commit %h by %an %ar. %s" ---
See https://stackoverflow.com/a/7293026/523688.
Last commit comment together with the committer and a timestamp.

--- git clean -ndX ---
See https://stackoverflow.com/a/2196755/523688.
List of ignored existing folders and files.

--- git submodule status | cut -d\' \' -f3-4 ---
See https://stackoverflow.com/a/19659265/523688.
List of submodules of the current repo.


-- HOW TO DEFINE DIRECTORIES OUTSIDE THE DRUPAL ROOT --

By default the Drupal root gets scanned recursively for GIT repos. To also track
others add for example the following to the settings.php file or a custom module
(comma separated):

  global $_gitinfo_extra_folders_;
  $_gitinfo_extra_folders_ = '/var/www/data,/home/admin/lib/angular';

Note the underscores and that an absolute path should be used (from system
root).

Alternatively a Drupal setting can be used. For example add the following to
the 'settings.php' file (comma separated):

  $config['gitinfo.settings']['gitinfo_extra_folders'] =
  '/var/www/data,/home/admin/lib/angular';

or using:

  drush cset -y gitinfo.settings gitinfo_extra_folders
  '/var/www/data,/home/admin/lib/angular'

No UI is offered as the option is advanced enough to presume that the people
using it have Drush installed.

The global variable gets priority as it is a higher level method (needs commit
rights) and would disallow to make changes for admins capable of defining Drupal
settings. That might be desired for some use cases.


-- NOTES --

The correct indenting of a nested unordered list depends on the used admin
theme. With the default Seven theme it works well.

Recently added GIT folders might not be detected right away. Run 'sudo updatedb'
first to update the Linux 'locate' cache. On OSX this is: 'sudo
/usr/libexec/locate.updatedb'.

If no tag exists for a GIT folder then probably branches are used for release
management.
