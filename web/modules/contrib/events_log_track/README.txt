INTRODUCTION
------------
This module track logs of specific events that you'd like to log. The events  by
the user (using the forms) are saved in the database and can be viewed on the
page admin/reports/events-track. You could use this to track number of times the
CUD operation performed by which users.

Currently, the following sub modules of Events Log Track are supported:
- Menu (custom menu's and menu items CUD operations)
- Node (CUD operations)
- Taxonomy (vocabulary and term CUD operations)
- User (CUD operations)
- User authentication (login/logout/request password)

The event log track can be easily extended with custom events.


INSTALLATION
------------
Enable the module and the sub modules for the events that you'd like to log.
From that point onwards the events will be logged.

After performing some operations the page admin/reports/events-track will show
the events.
