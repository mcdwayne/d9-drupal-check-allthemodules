
INTRODUCTION
------------

The Migrate GatherContent module allows you to import content from
GatherContent (https://gathercontent.com/) to your Drupal website.

This module is based on Drupal Core's Migrate functionality and the UI is built
with those concepts in mind. So if you already have a good understanding
of how Drupal Migrate works then the UI here should be familiar to you.


FEATURES
------------

This module allows you to import content to almost any Content Entity including:
 * Nodes
 * Taxonomy Terms
 * Files
 * Comments
 * Paragraphs
 * Media
 * And More...

You can also establish entity relationships by creating Migration Dependencies.
This is how you can create nodes with entity references like paragraphs, media,
taxonomy terms etc.

By virtue of using Migrate you can extend this module through custom plugins and
alter hooks.


INSTALLATION
------------

0 - Prerequisites:
Requires Drupal Core Migrate, Migrate Plus modules as well as
Chepper's GatherContent API (this should automatically be installed
through composer). You will also need a GatherContent Account and
API Key.
More information: https://docs.gathercontent.com/reference

2 - Configuration:
After successful installation provide your email and API key. Choose which
projects you want to use. This will expose the list of available GatherContent
Templates that you want to map.


HOW TO USE
------------

The general process for importing content is as follows.

 1. Create a Mapping group where you want to store your mappings.
    Note, it's recommended to create one group per content type.

 2. In that group create mappings for each entity type that you want to create.
    E.g Nodes, paragraphs, taxonomy terms etc.

 3. Import that content.

