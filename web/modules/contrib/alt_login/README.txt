$Id: README.txt,v 1.5 2011/01/05 00:44:12 thehunmonkgroup Exp $

****************************************************

Alternate login module -- README

written by Chad Phillips: thehunmonkgroup at yahoo dot com
Currently maintained by Matthew Slater: matslats dot net
****************************************************

This module provides an interface that allows registered users to use a login
name which is different than their username.

The login name is chosen from existing text fields on the user entity, including
 the uid. To use, simply enable the module, visit admin/config/people/altlogin

Arrange the user form & user display to ensure that users can can or cannot edit
that field.

Note that users can still login with their normal username--this just adds the
option of another login name. Also note that an alternate login name may not
be equivalent to any other current alternate login name, nor any current
username.

INSTALLATION:

1. Put the entire 'alt_login' folder in either:
      a.  /modules
      b.  /sites/YOURSITE/modules

2. Enable the module at Administer -> Modules.

3. At Administration » Config » People » Alt Login, and nominate the field to be 
   used.

4. At Administration » Config » People » Accounts » Fields arrange the forms and
   displays so that the user can see the field when they need to
