#!/bin/bash

## Point of script is to build a single giant git repo that has a branch per composer install of all modules, one branch per module.  Such that devel is composer required only on the devel branch, and so forth. This is part of a larger strategy to easy distribute work with the intend ot only do weekly true ups of the publicly known plugins with committed updates. 

## This also assumes you have a composer installed Drupal 8 repo.
## AND that you have a git repo present. 

## See this doc for the former.  
## https://docs.google.com/document/d/1UnVzmQhCv9_IT0-u-tjvWPDR-QQAU31ij2iKkio8U3U/edit#

cd ~/Documents/d9/midcamp2019-drupal
git checkout master

INPUT=modulelistmachinenames.csv

##Safe way to deal with Internal File Separator or IFS resets
OLDIFS=$IFS
IFS=,

## Error if fine not found
[ ! -f $INPUT ] && { echo "$INPUT file not found"; exit 99; }

## Loop through the CSV
while read col1 col2
do
## threw errors if I didn't store this one as a variable, not sure why.  
## Store the machine name as a variable
MODULENAME=$col1

## Print to screen just for keeping an eye on progress reasons. 
echo $MODULENAME

## check out a new branch called MODULENAME

git checkout $MODULENAME

# Install the module with composer
# composer require ${col2}

# use the awesome drupal-check tool, store output as a variable dcount
dcout=$(drupal-check web/modules/contrib/${MODULENAME})


# grep the hash and store it in the variable hashout
hashout=$(composer show -i drupal/${MODULENAME} | grep source)

## Push to a CSV
awk '{$1 = $dcout} {$2 = $hashout} 1' ../file.csv


## Now git merge master to merge the new line into the doc and reset for the next new checkout.  

# git add .

# git commit -m "added modulename to this branch"

git checkout master

## Done with the loop
done < $INPUT

## reset to the original IFS
IFS=$OLDIFS