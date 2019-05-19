# d8-wordfilter
A simple but extendable word filter for Drupal 8.

The Wordfilter module allows users to create and manage Wordfilter configurations.
Wordfilter configurations..
 - are multilingual, fully exportable Drupal configuration entities.
 - may be (re)-used as filters at any text format, node and comment.
 - contain a user-defined list of words, which will be filtered out (when enabled as filter).
 - can optionally use a substitution text for the defined filter words.

You can choose between different filtering processes, like direct filtering of specified words or token filtering. You may add your own implementation, e.g. for using an external web service which does the filtering for you.

This software is licensed under the GPLv2 license, see LICENSE.txt.
