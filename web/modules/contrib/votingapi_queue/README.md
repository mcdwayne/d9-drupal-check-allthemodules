# VotingAPI Queue

This module provides a queue for updating voted entites. The configuration
hooks into the default votingapi settings page. Choose manual on "tally
results" settings. Additional settings for batch size and cron execution
are available.

The submodule Votingapi Queue Cron Worker includes a worker that runs on
cron (time = 60 minutes). That can be used if drush or multiple cronjobs
are not supported by your environment. That way you can still process a
lot of items without having the default votingapi cron implementation
break your cronjob. The queue approach gives you control over how many
items you want to process in one cron run.

# Requirements

- VotingAPI
- Drush and/or cron
- (Although drush is not required, you will lose a lot of functionality
without it.)

# Recommended configuration

I suggest supervisord or something similar to spawn the worker
process. If that's not available a cron that runs every 1-5 minutes can
be used as well. If you don't need updating that quickly or want to
defer cache invalidation you can always use a different cron schedule.
Add some buffer time to avoid accidentally having too many workers run at
the same time - there's no semaphore being used so you can have multiple
workers run at the same time.

## Population examples:

The easiest way to populate items for processing is to enable
`queue_cron_populate` on votingapi settings form. If you don't want to
keep your cron lean / separate the population from the normal cron run
you can add a custom cron to trigger drush population:

Add all available items for processing:
    
    ~$ drush votingapi-queue-populate

Add maximum 1000 items for each run for processing:

    ~$ drush votingapi-queue-populate --limit=100

## Worker examples (time limit is in seconds):

For a cronjob that runs every minute and goal is immediate processing:
    
    ~$ drush votingapi-queue-run --time-limit=45

For a cron that runs every 5 minutes with immediate processing in mind:

    ~$ drush votingapi-queue-run --time-limit=280
    
For a cron that runs every hour and shall process all available items:

    ~$ drush votingapi-queue-run --run-once=true

Depending on how often your cronjob is configured to run you will have
to tweak the amount of items for each run accordingly. On an amazon web
services ec 2 m3 server population of 1000 items in less than 2 minutes
was possible.

# Recommended modules

- QueueUI
- QueueWatcher

# Available Drush commands

(options available, see drush help for more information)

Start a new worker:

    ~$ drush votingapi-queue-run

Populate queue:

    ~$ drush votingapi-queue-populate

Reset:

    ~$ drush votingapi-queue-set-last-run-time FALSE

Debug/ Info:

    ~$ drush votingapi-queue-debug
