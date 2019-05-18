# Drush and the GovReady Dashboard

## Overview
The GovReady module comes with a number of drush commands to help automate and
script communication with the GovReady API:

```
 govready-collect      Push data to the GovReady API.                 
 (govready)                                                           
 govready-scan         Push scan results to the GovReady API.         
 govready-mode         Set the GovReady API mode.                     
 govready-site         Get the stored Site information from GovReady.
 govready-initialize   Set the GovReady API mode.                     
 (govready-init)   
 govready-reset        Reset your GovReady site.                      
```


## govready-collect (govready)
```
Push data to the GovReady API.

Examples:
 drush govready-collect all                Collect and send data from stack, accounts, modules. 
 drush govready-collect accounts           Only collect and send account data.

Arguments:
 type                                      The type of data to send to the GovReady API. Options: all, stack, accounts, modules, domain

Aliases: govready
```

## govready-scan
```
Push scan results to the GovReady API.

Examples:
 drush govready-scan myscan <              Send scan information from a json file.             
 scan-output.json                                                                              
 run-scan.sh | drush govready-scan myscan  Run a scan and pipe the result to the GovReady API.

Arguments:
 name                                      The machine name of the scan that is running
```


## govready-mode
```
Set the GovReady API mode.

Examples:
 drush govready-mode manual                Set the manual mode on the GovReady API.

Arguments:
 mode                                      The mode to set the GovReady API as. Options: automatic, manual, local
```


## govready-site
```
Get the stored Site information from GovReady.
```


## govready-initialize (govready-init)
```
Initialize the GovReady module's communication with the GovReady API.

Aliases: govready-init
```

## govready-reset
```
Reset your GovReady site.
```

## govready-demo-load
```
Existing Measures, Submissions, and Contacts will be deleted and replaced with demo data.
```

