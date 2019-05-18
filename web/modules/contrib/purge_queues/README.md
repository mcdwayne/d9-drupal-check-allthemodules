# Purge Queues

This module provides extra [Purge](https://www.drupal.org/project/purge) queue plugins, and solves the [Duplicated Queued Items](https://www.drupal.org/node/2851893) problem.

While a general solution that works for all Purge queue implementations
is hard to implement, a specific change to the database plugin in order
to reject duplicate items is feasible.

Two database plugins are provided:

 * `database_alt`: extends the `database` plugin provided by Purge.
It works the same, but provides an extended schema to store the invalidation
type and expression in database columns. Queue item data is not altered at all.
 * `database_unique`: based on `database_alt`, avoids the enqueuing of
 duplicated items.

## Usage

 * Download and enable `purge_queues`
 * Change the queue engine to "Database unique" at `admin/config/development/performance/purge`

## Author Information

Jonathan Araña Cruz - [SB IT Media, S.L](http://sbit.io).

