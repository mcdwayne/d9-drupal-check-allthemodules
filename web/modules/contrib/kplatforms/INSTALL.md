KPLATFORMS
----------

Download Kplatforms
-------------------

Download kplatforms somewhere convenient, such as:

    $ git clone --recursive --branch 8.x-3.x http://git.drupal.org/project/kplatforms.git ~/makefiles/kplatforms

Install Composer
----------------

See instructions at https://getcomposer.org/download/

Install Drush 8.x
-----------------

    $ composer global `require drush/drush:~8`

It is recommended to make drush available as `drush` (eg. by placing the path
in in the PATH environment variable in ~/.bashrc); however, most scripts and
the Makefile use the drush path set via the environment variable DRUSH.


If you are running PHP with the suhosin module, you may have to pass the PHP
CLI an option to allow .phar files:

    -d suhosin.executor.include.whitelist=phar


Install make_diff
-----------------

The 'make_diff' extension allows two makefiles to be compared easily. Since it
is not published yet, we cannot install it via 'drush dl'. Instead, clone the
repo from github. We put it directly in the local drush install so as to keep
it isolated from other Drush functionality on the server.

    $ git clone https://github.com/PraxisLabs/make_diff.git ~/.drush/commands/make_diff
    $ drush cc drush
    $ drush | grep make-diff
