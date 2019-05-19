#######################################################################################
Zeomine Server Monitor: Settings Server
#######################################################################################

Zeomine Server Monitor (ZSM) is a Python-based server monitoring program that can be
managed either by local YAML files, or via this settings server module. ZSM is
primarily a file analysis program aimed at logs and server health as observed in
Linux's /proc folder, but may be extended with core and custom plugins.

#######################################################################################
# Adding the module to Drupal

* Composer
Go to your project root, and require the module:
composer require drupal/zsm

* Download
Download the module package to modules/contrib.

#######################################################################################
# Installing the module

1) Enable the main module and desired plugin modules at admin/modules.
2) On Views, either enable the EVA views and arrange them on user profile pages, or
duplicate them into new views if you want to alter them.

* We are investigating particular cases in which certain plugins are not added to the
ZSM plugin field. Enable the modules one at a time, and go to

/admin/structure/zsm_core_settings/fields/zsm_core.zsm_core.field_zsm_enabled_plugins/storage

in order to confirm the installers have added the plugins to the field.

#######################################################################################
# Setting up endpoint

1) Create a ZSM Core entity.
2) Create plugins you wish to attach to it.
3) Click the "Manage Plugins" Views field to get a list of plugins you have created,
and select the ones you want to add to the module.

#######################################################################################
# Setting up ZSM Python library

* Server
1) Install Python 3, and the Requests and PyYAML library
sudo apt-get install python3
sudo apt-get install python3-requests
sudo apt-get install python3-yaml

2) Clone or download ZSM to the server, from this location:
https://bitbucket.org/ceriumsoftwarellc/zeomine-server-monitor

* Setting up first config
Make zsm_init.py executable , and create a config file in user_data/configs.
Note: remoteconf_sample.txt contains the needed example code.

* Executing ZSM
1) Manual, from within the zsm folder
python3 zsm_init.py --conf=[config-file].yml
2) Cron Job
* * * * * python3 /path/to/zsm/zsm_init.py --conf=/path/to/zsm/user_data/configs/[config-file].yml