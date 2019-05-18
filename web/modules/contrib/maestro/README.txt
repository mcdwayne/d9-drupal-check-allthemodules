Maestro Workflow Engine for Drupal 8

For more detailed documentation - refer to the module documentation page: https://www.drupal.org/docs/8/modules/maestro

Installation:
------------
1. Copy/upload the maestro module to the modules directory of your Drupal installation.

2. Enable the 'Maestro Engine', 'Maestro Task Console' and the 'Masestro Template Builder' modules
   in 'Extend' - /admin/modules. More info below on the included modules.

3. Set up user permissions. (/admin/people/permissions)
   - Grant access to the task console for any users that will be assigned workflow tasks

4. Go to the Maestro Engine settings here:  /admin/config/workflow/maestro
   ** Read more about the Maestro Engine (Orchestrator) below **
   - For initial use and getting started with Maestro: Enable the checkbox
      > Run the Orchestrator on Task Console Refreshes.
   - For production or more active development of real workflows, your going to want to have
     the Maestro Engine running automatically in the background.
     Maestro requires you to enter a token for running the orchestrator.
     You WILL get errors if you are trying to run Maestro's engine without an orchestrator token.
   - YOU MUST set the value to valid string, it needs to not be empty. Set the token to something that
     can be called via some sort of URL calling mechanism (wget, CRON, PowerShell Script etc.).

   - The resulting URL to crank the Maestro Engine that will be called when you set a
     token is: http://site_url/orchestrator/{token}
     without setting the token, your setting for running the orchestrator on Task Console refreshes will also fail.

5) Go to the Maestro Template Builder settings: admin/config/workflow/maestro_template_builder
   - Review the location of the library used for the visual workflow editor.
   - Maestro uses the Raphael JS library to create the SVG graphics and the object handling libraries
     for the drag and drop functionality.
   - By default on installation, the module loads the library from:
     //cdnjs.cloudflare.com/ajax/libs/raphael/2.2.7/raphael.js

6) Check out Getting Started below or setup your first workflow template: /maestro/templates/list



Getting Started:
----------------
- If you have the Tools menu (Block) enabled, you will see the common links to the main maestro tools
  including the users Task Console. Additionally if you have the 'Toolbar' module enabled,
  Maestro adds a new convenient Menu.
- Use the Maestro Template builder to view the workflow templates and add/edit the templates can
  also be accessed under the Structure menu.
- Enable the 'Maestro Form Approval Flow Example' module. It's a simple, all-inclusive workflow
  that provides a template, content type, users, roles, permissions for a simple form approval.
- Users access their tasks via the Task Console /taskconsole

Provided Views:
- Outstanding Tasks: View: Shows all the currently active intereactive tasks that require user interaction.
  This would include interactive tasks that are assigned to users and batch tasks that have not yet completed.
  It will show any task the orchestrator is in the process of executing.

  Provides two exposed filters that allows you to filter by process name or task name
  Provides links to Trace this instance of the workflow process and re-assign the task owner

- All In Production Tasks: Shows all the currenty active tasks including conditional (IF) and Batch tasks.
  Provides two exposed filters that allows you to filter by process name or task name
  Provides links to Trace this instance of the workflow process and re-assign the task owner


More Information:
-----------------
- Refer to the module documentation page: https://www.drupal.org/docs/8/modules/maestro
- Go to http://nextide.ca/blogs to read more on Maestro for D8.


Maestro engine, also know as the Orchestrator:
-----------------------------------------------
The Maestro engine orchestrates the workflow which is responsible for executing the workflow template. It does far more
then just assigning the next workflow task. It will test the result of each task as they complete and determine what
the next task is to execute. Workflow templates include conditions so there can be different tasks to setup as the
next task depending if a user approved a form for example. The workflow routing can depend on different user actions
such as clicking an Accept or Reject button,a template variable value, custom php code in a batch function etc.

The orchestrator needs to run in the background continuously. If it's not running then a task can not complete and new
tasks will not start.

Maestro and the Orchestrator have been refactored for Drupal 8 and as of the Beta release,
has no upgrade option (yet) from Drupal 7.


Modules included:
--------------------

Maestro Engine (maestro)
  This is the core engine to Maestro responsible for execution of the engine templates.  
  Contains all of the Maestro APIs.
  Installs with a single example template.
  Installs with two Views for outstanding tasks and all in-production tasks.

Maestro Task Console (maestro_taskconsole)
  The main user interface for users to access their assigned tasks.
  Users can view their assigned tasks and their provided actions
  Requires the Maestro Engine to be installed.

Maestro Template Builder (maestro_template_builder)
  Main administration interface to view and edit the workflow templates
  Provides a SVG-based visual template builder.
  Requires the Maestro Engine to be installed.
  Only required to edit workflow templates.

Maestro Utility Functions (maestro_utilities)
    Extra common use interactive and batch type functions

Maestro Examples:
  Maestro Non-Interactive Task Example
  Maestro Interactive Task Example
    Both these modules provide stub/base code for how you would write your own task type for the Engine.
    
Maestro Form Routing Workflow
  An example workflow.
  It's a simple, all-inclusive workflow that provides a template, content type, users, roles, permissions
  for a simple form approval.


