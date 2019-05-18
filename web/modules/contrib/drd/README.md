Introduction 
------------

Drupal agencies, freelancers, site builders and even Drupal site owners do 
need DRD (Drupal Remote Dashboard) because it is the one and only solution that 
provides a non-intrusive insight into any number of remote Drupal sites without 
the need of any third party service.

No matter where the Drupal sites are hosted and regardless of any configuration 
or deployment specifics, DRD is able to monitor and manage them all at once. 
The dashboard is build for Drupal 8, the monitored remote sites are supported 
on Drupal 6, 7 and 8.

Dependencies
------------

The main DRD module requires taxonomy, update and views, all three from Drupal core, and in 
addition a couple of contrib modules such as 
[EVA](https://www.drupal.org/project/eva) and 
[Key value field](https://www.drupal.org/project/key_value_field). 
Other than that, it has no special requirements.

Each remote Drupal site that should be monitored, requires the 
[DRD Agent](https://www.drupal.org/project/drd_agent) module installed and 
enabled. This module is a simple wrapper around the dynamically loaded DRD 
library which brings all the code that needs to be executed remotely.

Of course, security and privacy comes first. And we've addressed that in the 
product architecture from day one. DRD brings the best possible convenience 
without compromising security or privacy. Read more and discuss about that 
topic in the [Security chapter](https://www.drupal.org/node/2839478) of the 
documentation.

Features
--------

- Collect information from any number of Drupal sites
  - Identify Drupal sites: for each monitored Drupal installation all the 
    hosted Drupal sites are recognised automatically
  - Drupal core, modules and themes: collect the installed projects and their 
    versions
  - Status information: collect all the details from the Drupal status report 
    for each domain
  - Status widget: from the collected status information build a status widget 
    for each domain to display grouped  traffic light status levels for 
    security, health, tuning, seo and others
  - Aggregate status widget: take all the status information from each domain 
    of a Drupal installation - i.e. in a multi-site environment - and aggregate 
    them into a status widget per Drupal installation
  - Blocks: collect any block from remote Drupal sites and display them on the 
    dashboard
  - Error logs: collect the error logs from remote sites and review them in 
    your dashboard

- Execute maintenance tasks
  - Turn on/off maintenance mode
  - Run cron
  - Flush cache
  - Run update.php

- Update Drupal core, modules and themes

  This has been implemented in version 3.4 by supporting a range of different
  deployment methods

- Miscellaneous
  - Change user credentials
  - Execute arbitrary PHP code
  - Rebuild job schedulers
  - Update translations from drupal.org

Installation Guide
------------------

- [Installation steps](https://www.drupal.org/node/2839479)
- [Glossary](https://www.drupal.org/node/2839476) 
- [Security and authantication](https://www.drupal.org/node/2839478)

Contact
-------

- [Issue queue](https://www.drupal.org/project/issues/drd)
- [Slack](https://drupal.slack.com/messages/drd/whats_new)
- [Gitter](https://gitter.im/drupal-remote-dashboard/Lobby)

Current Maintainers 
-------------------

- [boromino](https://www.drupal.org/u/boromino): Richard Papp
- [dipinfwalnas](https://www.drupal.org/u/dipinfwalnas): Walid Nasri
- [gneef](https://www.drupal.org/u/gneef): Gunnar Neef
- [jurgenhaas](https://www.drupal.org/u/jurgenhaas): Jürgen Haas
- [j.slemmer](https://www.drupal.org/u/j.slemmer): Jons Slemmer

Encrypt:

- drd_domain
  - authsetting
  - cryptsetting
- drd_host
  - ssh2setting
