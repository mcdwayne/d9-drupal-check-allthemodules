CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation

INTRODUCTION
------------

Current maintainer:  Kurinjiselvan V (https://www.drupal.org/u/kurinji-selvan)

Display the host and database name of the default database on the status
report page, admin toolbar and you can use as a block for Drupal 8.

This module is inherited from the Drupal 7 Show Database Name 
(https://www.drupal.org/project/show_database_name) was developed by 
Gregg Marshall (https://www.drupal.org/u/greggmarshall).

The genesis of this module came about as once again I copied a website to
a development instance and forgot to change settings.php to use the a
development database, and ended up making changes to production when I
just wanted to try something on development.
	
There are two use cases for this module:
1.  Give a quick way to identify which database you are using when switching
    from development to staging to production.
2.  When you are given a website to maintain and you are trying to figure out
    which of many databases are actually driving the content you are seeing.
	
The module just reports what comes from settings.php and makes no attempt to
test the database connection.

INSTALLATION
------------

* Install as usual, see https://www.drupal.org/node/1897420 for further information.
* The module adds a permission, "access database information", which is used to control
  who can view the database host/name.