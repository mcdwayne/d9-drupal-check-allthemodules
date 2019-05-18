CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Usage examples


INTRODUCTION
------------

Providing methods to modify JIRA issues
out of Drupal via REST.


Requirements (via composer.json):
Library: https://github.com/seifertT/jira-php-api


REQUIREMENTS
------------

This module requires the following modules:

 * Key (https://www.drupal.org/project/key)

INSTALLATION
------------

From 8.x-2.x on module will be based on jira-php-api library which uses guzzle instead of cURL.

Please use composer to download it into your D8 project, e.g. install current 8.x-3.x-dev by running the
following command in the drupal root folder/where your composer.json resides:

composer require drupal/jira_rest:3.x-dev

or current stable release 8.x-3.0 with:

composer require drupal/jira_rest:3.0

Enable the module by navigating to "Extend" (admin/modules) or via drush

CONFIGURATION
-------------

Setup your JIRA instance & parameters under this route: admin/config/jira_rest/config


USAGE EXAMPLES
-------------

Try if you can reach the route /jira_rest/test from your admin account.

Quick Examples
(load issue by key)

    $jira_rest_wrapper_service = new JiraRestWrapperService();
    $issue = $jira_rest_wrapper_service->getIssueService()->load('PROJECT-KEYID');

(search)

    $search = $this->jiraRestWrapperService->getIssueService()->createSearch();

    // search for existing open issues
    $search->search(utf8_encode("status = Open"));

    foreach ($search->getIssues() as $i){
      $issue = $i;
      break;
    }

    $issuekey = $issue->key
    ...


(create issue and subissue)

    $issue = $this->jiraRestWrapperService->getIssueService()->create();
    //mandatory fields to set
    $issue->fields->project->setKey('DEV'); //or you can use the project id with: $issue->fields->project->setId($jiraProjectId);
    $issue->fields->setDescription(utf8_encode('title of issue') );
    $issue->fields->issuetype->setId('1');	// Issue type : Bug
    $issue->fields->addGenericJiraObject('priority');
    $issue->fields->priority->setId('4'); //Priority Minor
    $issue->fields->setSummary(utf8_encode('a summary / description'));
    //create the parent issue
    $issue->save();

    //set a label
    $labels[] = utf8_encode('Urgent');
    $issue->fields->setLabels($labels);
    //add a comment
    $issue->addComment('a comment to be added',true); //true for forcing presaving the issue object

    //creating a subissue

    $subissue = $issue->createSubIssue();
    $subissue->fields->project->setKey('DEV');
    $subissue->fields->setDescription(utf8_encode('a title for subissue') );
    $subissue->fields->issuetype->setId('8');	// Sub-task issuetype: Technical task
    $subissue->fields->addGenericJiraObject('priority');
    $subissue->fields->priority->setId('4');
    $subissue->fields->setSummary(utf8_encode('a desc for subissue'));

    $subissue->addComment('subissue added by xxx',true);
    $subissue->save();

(more)
    //get Subissues
    $parentIssue->getSubIssues()
    //access customfields
    $reportIssue->fields->setCustomfield_10400(utf8_encode('text...'));
