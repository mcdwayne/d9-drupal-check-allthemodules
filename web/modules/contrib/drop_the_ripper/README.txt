DROP THE RIPPER
---------------

A Drush command to crack user passwords using a wordlist (like John the Ripper).

DRUPAL / DRUSH VERSIONS
-----------------------

This is all a bit confusing. DtR supports Drush 8 and 9 and Drupal 7 and 8.

It has a Drush 8 commandfile which works with both Drupal 7 and 8.

There's also a Drush 9 command implementation which only works with Drupal 8.

To keep things simple, the 7.x and 8.x releases of DtR are the same; either of
them supports all of the combinations outlined above.

INSTALLATION
------------

Drush 8 will typically put DtR in ~/.drush/drop_the_ripper from where it can be
used globally - for example:

$ drush dl drop_the_ripper
Project drop_the_ripper (8.x-1.x-dev) downloaded to /home/mcdruid/.drush/drop_the_ripper.  [success]
Project drop_the_ripper contains 0 modules: .

$ drush dtr

For DtR to work as a Global Drush Command with Drush 9 you have to place it in
one of a few special locations where Drush will find it, or you can use the
--include option to tell drush where to look, for example:

$ drush --include=~/.drush/drop_the_ripper dtr

See: https://github.com/drush-ops/drush/blob/master/docs/commands.md#global-drush-commands

USAGE
-----

Note that one of the most useful options is --restricted which targets all users
with roles that have one or more restricted permissions.

$ drush help drop-the-ripper
Crack Drupal password hashes.

Examples:
 drush dtr                                 Try to crack passwords of all users.
 drush dtr --top=100                       Try to crack passwords of all users, using the first 100 passwords from the wordlist.
 drush dtr 3                               Try to crack passwords of all users with role 3 in D7 ("drush rls" lists role IDs).
 drush dtr editor                          Try to crack passwords of all users with editor role in D8 ("drush rls" lists role IDs).
 drush dtr --uid=1                         Try to crack password of user number 1.
 drush dtr --restricted                    Try to crack passwords of all users with roles that have restricted permissions.
 drush dtr --wordlist=/tmp/rockyou.txt     Use a custom wordlist for password cracking.
 drush dtr --all --no-guessing             Try every password in the wordlist, but do not try to guess user passwords.

Arguments:
 user-rids                                 (Optional) Only check passwords for users with these role IDs (comma separate multiple IDs).

Options:
 --all                                     Use all entries from the wordlist (default if a custom wordlist is supplied).
 --hide                                    Do not show plaintext passwords in output.
 --no-guessing                             Disables built-in password guessing (e.g. username as password).
 --restricted                              Check all users with roles that have restricted (admin) permissions.
 --top=<25>                                Number of passwords to read from the wordlist (default is 25).
 --uid                                     Comma separated list of user ids.
 --wordlist=</path/to/wordlist>            Path to a custom wordlist (default is openwall's password list).

Aliases: dtr

EXAMPLES
--------

$ drush dtr
Match: uid=2 name=fred password=qwerty                       [success]
Match: uid=4 name=marvin password=123456                     [success]
Ran 65 password checks for 4 users in 2.68 seconds.          [success]

$ drush dtr --restricted --all
Match: uid=7 name=sally password=Qwert                       [success]
Ran 7085 password checks for 2 users in 294.19 seconds.      [success]

$ drush dtr --uid=11,42 --top=100
Match: uid=11 name=tom password=changeme                     [success]
Ran 126 password checks for 2 users in 4.85 seconds.         [success]

CREDITS
-------

DtR uses a default wordlist from http://www.openwall.com/wordlists
