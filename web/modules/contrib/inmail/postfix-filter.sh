#!/bin/bash
#
# Drupal Inmail processing script
#
# You can setup your local MTA (e.g. Postfix) to pipe incoming email to this
# script. It will then run the Inmail processor (analyzers and handlers) for
# each message.

# Enter Drush environment
cd `dirname $0`

# Parse options
while getopts hdo: option; do
  case $option in
    h) # Help, usage
      cat <<EOF
Pass an email message to 'drush inmail-process'. This is meant to be used as a
filter for a Postfix alias or similar. See README.txt for an example.

Options:
  -h                Display this help text and usage information.
  -d                Print debugging information from the shell environment,
                    Drush and Inmail.
  -o <options>      Pass options to Drush.
EOF
      exit
      ;;
    d) # Dump environment information for debugging
      pwd
      echo
      id
      echo
      set
      echo
      drush status
      echo
      drush inmail-plugins
      echo
      DRUSHOPTS=-d
      ;;
    o) # Additional Drush options
      DRUSHOPTS="$DRUSHOPTS $OPTARG"
      ;;
  esac
done

# Access the arguments after the options
shift $(($OPTIND - 1))

# Email content (one message) is piped from stdin to the Drush command
drush $DRUSHOPTS inmail-process $1
