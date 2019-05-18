KPLATFORMS
==========

Kplatforms is a set of makefiles structured for easy maintenance. It is used,
along with the Aegir Hosting System (http://aegirproject.org), to maintain
Koumbit Networks' (hence the 'K') mass-hosting platforms.

Normal usage of Kplatforms involves simply running Drush Make with one of the
supplied 'lock' files. Lockfiles are simply mankefiles with all versions
completely specified. Further instructions can be found in USAGE.md.

Kplatforms uses a particular directory layout to make maintaining makefiles
easier. For a discussion on the reasoning behind this, see STRUCTURE.md.

A specific workflow was developed to maintain the 'lock' files. This workflow
is described in MAINTENANCE.md.
