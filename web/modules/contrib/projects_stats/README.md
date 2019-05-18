# Projects Stats

## CONTENTS OF THIS FILE

  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Author

## INTRODUCTION

Are you a module or theme developer? Have you created several modules, themes or
distributions and you want to see how popular they are? Probably not. But if you 
for some reason need to display a number of downloads for a list of modules, 
themes and/or distributions then you should try out this module. Projects Stats
provides a block, which displays a table or a list with project names, downloads 
count and some additional stats. Another great option is a Slack integration. 
You can receive downloads count directly to your Slack channel.

## REQUIREMENTS

None.

## INSTALLATION

1. Install module as usual via Drupal UI, Drush or Composer
2. Go to "Extend" and enable the Projects Stats module.

## CONFIGURATION

After you install the module, go to the block layout 'admin/structure/block' and
add a 'Projects Stats' block. After you click 'Place block' button, you will see
a number of options. You need to enter machine names of the projects you want to
display in your block, and there are several more options like cache age for
block, sort type and table classes. Every block has it's own settings, so you
can have multiple blocks that show different list of projects.

Slack integration can be configured here: 'admin/config/services/projects-stats'
To enable sending messages to your Slack channel, you have to create an
integration. Go to this page: https://slack.com/services/new/incoming-webhook
and follow the instructions. After you create an integration copy and paste the
given Webhook URL to the module settings. You can choose when you want to send a
message. If you choose to use Drupal's cron, then sending interval will depend
on the cron settings. Recommended option is to use the external cron job,
because then you can fine-tune when it runs.

### AUTHOR

Goran Nikolovski  
Website: http://gorannikolovski.com  
Drupal: https://www.drupal.org/u/gnikolovski  
Email: nikolovski84@gmail.com  

Company: Studio Present, Subotica, Serbia  
Website: http://www.studiopresent.com  
Drupal: https://www.drupal.org/studio-present  
Email: info@studiopresent.com  
