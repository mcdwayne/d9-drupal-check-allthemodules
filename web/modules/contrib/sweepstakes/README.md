Sweepstakes
===========

## Purpose
To create and manage Sweepstakes/contests and randomly allot prizes to winners.
  
## Implementation
This module exposes a content type `Sweepstakes` which allows the user to specify
  various settings related to the particular sweepstakes.
  
  The sweepstakes entries are stored into a custom db table that is exposed as an entity to drupal
  by schema.module & data.module
  
  The module exposed a few blocks and ctools access plugins to help admins setup the key
  giveaway page.

* Sweepstakes Entry Block - The enter button users press to participate in the contest.
* Sweepstakes: Sweepstakes has Expired - plugin to control access depending on whether the sweepstakes has expired.