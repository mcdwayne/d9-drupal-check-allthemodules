# Website shutdown

## Introduction
This module allows you to shut down/close a website.

All requests handled by Drupal will be redirected to a specified page, unless
it is initiated by a user who has adequate permission to navigate through the
site.

## Configuration
1. Go to *admin/config/system/shutdown*
2. Enable shutdown and provide a redirection page
3. You may exclude some paths
4. Save

## Permissions
You may allow some users/roles to navigate trough the site while being shut
down.
To do that, just assign the 'Navigate through shut website' permissions to
desired role(s).
