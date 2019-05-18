Periodically mail
============

Description
-----------

This is a very simple module to send mail periodically to users of with
some role.

Usage
-----

Install the module. Visit admin/config/services/periodically_mail path and
set module for any user role, sending periode, start time and email body.
It will send email to all users which have specified role after run cron if
specified time in settings is less than current. After that on the start
time will be added specified periode.
