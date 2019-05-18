Drupal Git
==========
This Module provides routes to access Git repository information. Which is 
avalible on Report page (/admin/reports). Module assume git is installed on 
the server you are running the code on.

Installation
============
Once the module has been installed, navigate to /admin/reports for getting the
git information.

Routes
======
This modules gives below routes.

1. /admin/reports/drupal_git
    Gives form for checking the diff of two branches.
2. /admin/reports/drupal_git/drupalGitLog
    Gives pretty logs graph.
3. /admin/reports/drupal_git/drupalGitStatus
    Gives List of files Which are staged, unstaged, and untracked.
4. /admin/reports/drupal_git/drupalGitAllRepo
    Gives List of all repository branches Remotes & Locals.
5. /admin/reports/drupal_git/drupalGitAllLogs
    Gives all logs, 10 logs per page.
6. /admin/reports/drupal_git/drupalGitLogSummary
    Gives git log summary 100 lines per page.
7. /admin/reports/drupal_git/drupalGitTags
    Gives list of all tags.
8. /admin/reports/drupal_git/drupalGitAllUsersInfo
    Gives List of all authors total commits, username and emailid.
9. /admin/reports/drupal_git/drupalGitOtherInfo
    Gives Last commit id in current branch, List of remote and local branches

Requirements
============
Requires PHP 5.4 or later
A system with git installed (MUST)
Git client (path to Git must be in system variable PATH).

Git installers:
    for Linux - https://git-scm.com/download/linux
    for Windows - https://git-scm.com/download/win
    for others - https://git-scm.com/downloads

Troubleshooting - How to provide username and password for commands
    use SSH instead of HTTPS - https://stackoverflow.com/a/8588786
    store credentials to Git Credential Storage
    https://help.github.com/articles/caching-your-github-password-in-git/

Author/Maintainers
================================================================================
Name: Rajveer gangwar
Email: rajveer.gang@gmail.com
Profile: https://www.drupal.org/u/rajveergang
