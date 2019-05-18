# Hacked! Documentation

## Table of Contents

* Introduction
* Requirements
* Recommended modules
* Installation
* Configuration
* Maintainers


## Introduction

The Hacked! module scans the currently installed Drupal contributed modules and themes, re-downloads them and determines if they have been changed.  Changes are marked clearly and if the Diff module is installed then Hacked! will allow you to see the exact lines that have changed.

* For a full description of the module visit
[https://www.drupal.org/node/667576](https://www.drupal.org/node/667576).
* To submit bug reports and feature suggestions, or to track changes visit
[https://www.drupal.org/project/issues/hacked](https://www.drupal.org/project/issues/hacked).


## Requirements

This module does not require any additional modules outside of Drupal core.


## Recommended Modules

* [Diff](https://www.drupal.org/project/diff)


## Installation

Install the Hacked! module as you would normally install a contributed Drupal module. Visit [Installing Drupal 8 Modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-modules-find-import-enable-configure-drupal-8) for further information.


## Configuration

1. Install the Hacked! module.
2. Enable the Hacked! module.
3. Navigate to Administration > Reports > Hacked. The "List projects" tab will provide a list of modules which have changes. Each list item provides a "View details of changes" link which gives a more detailed list of what exact changes occurred.
The "Settings" tab allows two options: "Ignore line endings" which is helpful if the project has been edited on a platform different from the original author's (E.g. if a file has been opened and saved on Windows) and "Include line endings" when hashing files differences in line endings will be included.
4. To run a report which includes disabled modules, navigate to Administration > Reports > Updates > Settings and click the "Check for updates of disabled and uninstalled modules and themes" checkbox. Save configuration.

The process of fetching and comparing with clean projects can be a time-consuming one, so give it some time to load on the first run.

## Drush

Drush commands:

* `hacked-details (hd)`: Show the Hacked! report about a specific project.
* `hacked-diff`: Output a unified diff of the project specified.
* `hacked-list-projects`: List all projects that can be analysed by Hacked!


## Maintainers

* [Stuart Clark (Deciphered)](https://www.drupal.org/u/deciphered)
* [Steven Jones](https://www.drupal.org/u/steven-jones)
* [Colan Schwartz (colan)](https://www.drupal.org/u/colan)

This project is currently maintained by developers at:

* [ComputerMinds](https://www.drupal.org/computerminds)
* [Realityloop](https://www.drupal.org/realityloop)
* [Consensus Enterprises](https://www.drupal.org/consensus-enterprises)
