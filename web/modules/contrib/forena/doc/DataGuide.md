##Data Sources
  Data sources define a connection to a database and a collection of data blocks (paramterized sql and/or xml files).  
 
  Part of the definition of a data source, is the location of a collection of files (sometimes called a repository), which is a directory on the web server
    that will contain all data block files related to that data source. This directory should not be in a place inside of your web
    servers document root, but if you do, take steps to make sure that files in this directory is not writable by the web user.
  
  Defining such data source can be done in one of three ways:
  
  * Defined Forena configuration data tab.
  * Defined within settings.php files which allows for including PHP logic.
  * Defined by a module using a forna_alter_repos hook.
  
  Once the data sources are defined, you can start {building_datablocks} using these data sources.
  
###Data Sources Settings
    
Visit the Data tab within Forena Configuration to define data sources (database connections) using administration
(configuration) screens. Note however that prior to defining a new database connection, you'll need to create a folder 
to store the corresponding data blocks (SQL files) on the file system.

The following table illustrates the options provided:

 <table>
    <tbody>
      <tr>
        <th>Name</th>
        <td>Machine readable name, which is used in referencing all data blocks used by this data source and which should not
          contain any special characters or spaces.</td>
      </tr>
      <tr>
        <th>Title</th>
        <td>Human readable name that describes the data source, and which is primarily used in error messages where the data
          source cannot be accessed.</td>
      </tr>
      <tr>
        <th>Enabled</th>
        <td>Check-box which, if unchecked (disabled), will cause all queries related to this data block to return no data.</td>
      </tr>
      <tr>
        <th>Debug</th>
        <td>Set to TRUE to write the raw data queries and their results to the watchdog log. This can be useful for debugging
          data blocks in a development environment.</td>
      </tr>
      <tr>
        <th>Path</th>
        <td>Repository directory containing the data block files.</td>
      </tr>
      <tr>
        <th>Current user</th>
        <td>The function that will be used to determine the current user (i.e. Drupal's UID, Drupal's User Name, or none). The
          <strong>current_user</strong> argument is passed to all data blocks so that you can write queries that are specific to the
          user logged in (by using <strong>current_user</strong>) preceded by a "<strong>:</strong>".
        </td>
      </tr>
      <tr>
        <th>Data security method</th>
        <td>The function that will be used to check permissions. Supported options are:
          <ul>
            <li><strong>Use drupal permissions</strong>, which will use Drupal security access rights (Access callback =
              forena_user_access_check).</li>
            <li><strong>Match values provided by a data block</strong>, which uses a data block providing permissions list to
              interpret permissions and which should return a single column of permissions based on the current user (may be provided
              by another repository).</li>
          </ul> <strong>Note</strong>: Custom modules may provide their own functions for checking security.
        </td>
      </tr>
      <tr>
        <th>Driver</th>
        <td>The name of the class that will be used to provide the data blocks (= the driver). Data engine plugins
          delivered with Forena include:
          <ul>
            <li>FrxDrupal - Drupal</li>
            <li>FrxOracle - Oracle Database</li>
            <li>FrxPDO - PDO other than Drupal</li>
            <li>FrxPostgres - Postgres Database</li>
            <li>FrxMSSQL - MSSQL Database</li>
            <li>FrxFiles - XML Files</li>
          </ul>
        </td>
      </tr>
      <tr>
        <th>uri</th>
        <td>The connection uri for the data block (not for FrxDrupal driver).</td>
      </tr>
      <tr>
        <th>User</th>
        <td>The user name used to establish the connection to the data provider (only for FrxOracle, FrxPDO or FrxMSSQL
          driver).</td>
      </tr>
      <tr>
        <th>Password</th>
        <td>The password used to establish a connection to data provider (only for FrxOracle, FrxPDO, FrxPostgres or FrxMSSQL
          driver).</td>
      </tr>
      <tr>
        <th>Database</th>
        <td>The database used to establish a connection to data provider (only for FrxDrupal or FrxMSSQL driver).</td>
      </tr>
      <tr>
        <th>Character Set</th>
        <td>Leave blank for default character set (only for FrxOracle driver).</td>
      </tr>
      <tr>
        <th>Oracle native XML</th>
        <td>Enable this option (by checking the check-box) if you want to use Oracle's native XML functions (only for FrxOracle
          driver). In order to use this you must first install a function called f_forena_xml into your database schema. You'll find
          the .sql file which can be used to install this function included in the Forena distribution.</td>
      </tr>
      <tr>
        <th>Postgres native XML</th>
        <td>Enable this option (by checking the check-box) if you want to use Postgres native XML support, which requires
          Postgres 8.3 or better (only for FrxPostgres driver).</td>
      </tr>
      <tr>
        <th>Microsoft SQL native XML</th>
        <td>Enable this option (by checking the check-box) if you want to use XML auto queries to generate XML (only for
          FrxMSSQL driver).</td>
      </tr>
    </tbody>
  </table>
  
  For more info about this topic, checkout <a href="https://www.youtube.com/watch?v=shAVqLPEPYI" target="_blank">Basic
  Reporting on External Data - Quick introduction to reporting using the Northwind sample database</a> (i.e. what is explained from
    about 03:00 to 05:45, and which also explains how to enable the Northwind Extended database in MySQL format).
 
 ### Data Sources in settings.php files
  
 Additional data block repositories can be created to allow Forena to report against most applications. To create
 repositories you'll need to perform these steps:
 
  <ol>
    <li>Edit your Drupal site's settings.php to specify the locations of the additional data block repositories.</li>
    <li>Manually create the directory.</li>
    <li>Create new data block files as necessary to be used in reports.</li>
  </ol>

#### Step 1: Edit your Drupal site's settings.php file</h4>

The list of additional repositories is stored in a php global variable called <strong>$_forena_repositories</strong>. The
following example code illustrates the lines that would need to be added to your Drupal site's settings.php file to create a
new data block repository using <strong>local</strong> as the machine readable name (which is used in referencing all data
blocks used by this data source and which should not contain any special characters or spaces):

````php
global $_forena_repositories;
$_forena_repositories['local'] = array(
  'source' =&gt; 'sites/default/local_blocks',
  'title' =&gt; 'Site Specific Data Blocks' );
````

The following table illustrates the options provided:
  <table>
    <tbody>
      <tr>
        <th>path</th>
        <td>Repository directory containing the data block files.</td>
      </tr>
      <tr>
        <th>title</th>
        <td>Human readable name that describes the data source, and which is primarily used in error messages where the data
          source cannot be accessed.</td>
      </tr>
      <tr>
        <th>debug</th>
        <td>Set to TRUE to write the raw data queries and their results to the watchdog log. This can be useful for debugging
          data blocks in a development environment.</td>
      </tr>
      <tr>
        <th>user callback</th>
        <td>The function that will be used to determine the current user (i.e. Drupal's UID, Drupal's User Name, or none). The
          <strong>current_user</strong> argument is passed to all data blocks so that you can write queries that are specific to the
          user logged in (by using <strong>current_user</strong>) preceded by a "<strong>:</strong>".
        </td>
      </tr>
      <tr>
        <th>access callback</th>
        <td>The function that will be used to check permissions. To use Drupal security access rights specify a value of <strong>user_access</strong>.
          Custom modules may provide their own functions for checking security.
        </td>
      </tr>
      <tr>
        <th>data provider</th>
        <td>The name of the class that will be used to provide the data blocks. Data engine plugins delivered with Forena
          include:
          <ul>
            <li>FrxDrupal - Drupal</li>
            <li>FrxOracle - Oracle Database</li>
            <li>FrxPDO - PDO other than Drupal</li>
            <li>FrxPostgres - Postgres Database</li>
            <li>FrxMSSQL - MSSQL Database</li>
            <li>FrxFiles - XML Files</li>
          </ul>
        </td>
      </tr>
      <tr>
        <th>uri</th>
        <td>The connection uri for the data block (not for FrxDrupal driver).</td>
      </tr>
      <tr>
        <th>user</th>
        <td>The user name used to establish the connection to the data provider (only for FrxOracle, FrxPDO or FrxMSSQL
          driver).</td>
      </tr>
      <tr>
        <th>password</th>
        <td>The password used to establish a connection to data provider (only for FrxOracle, FrxPDO, FrxPostgres or FrxMSSQL
          driver).</td>
      </tr>
      <tr>
        <th>database</th>
        <td>The database used to establish a connection to data provider (only for FrxDrupal or FrxMSSQL driver).</td>
      </tr>
      <tr>
        <th>character_set</th>
        <td>Leave blank for default character set (only for FrxOracle driver).</td>
      </tr>
      <tr>
        <th>oracle_xml</th>
        <td>Set to true if you want to use Oracle's native XML functions (only for FrxOracle driver). In order to use this you
          must first install a function called f_forena_xml into your database schema. You'll find the .sql file which can be used to
          install this function included in the Forena distribution.</td>
      </tr>
      <tr>
        <th>postgres_xml</th>
        <td>Set to true if you want to use Postgres native XML support, which requires Postgres 8.3 or better (only for
          FrxPostgres driver).</td>
      </tr>
      <tr>
        <th>mssql_xml</th>
        <td>Set to true if you want to use XML auto queries to generate XML (only for FrxMSSQL driver).</td>
      </tr>
    </tbody>
  </table>
  
 #### Step 2: Manually create the directory
 
 Create the directory corresponding to the <strong>path</strong> specified in the previous step.
  
 <h2 id="datablocks">Data Blocks (SQL Queries)</h2>
Data blocks are files that are located in a <strong>database repository</strong>, which is a (secured) directory on the web
server that contains all data block files related to a specific {datasource}. The actual format of these files is dependent 
on which driver is being used for the data source.
 
the most common format for these files is <abbr title="Structered Query Language">SQL</abbr>, used for all supported data engines except for FrxFiles (XML Files). Here is a sample of such SQL file:
    
````sql
--ACCESS=access administration pages
SELECT type, count(type) as typecount
FROM {watchdog}
GROUP BY type
ORDER BY type asc
LIMIT 50
````

    </li>
    <li>an alternative format for these files is <abbr title="eXtensible Markup Language">XML</abbr>.
    </li>
  </ul>
  <p>SQL and XML files live on the file system on the web server and can be created using the SQL or XML editor of your choice.</p>
  <p>
    To create new data blocks in SQL format, you may want to consider using the {create_datablock} link on the
    {structure_forena_data} screen. This will launch the <strong>query writing tool</strong> that comes with Forena, which is called the <strong>Forena Query Builder</strong>. This is a separated module that comes with Forena. It can be enabled as per standard Drupal instructions to enable an additional module. For a video tutorial
    about this query writing tool, checkout <a href="https://www.youtube.com/watch?v=mPdFftH4CGs"
      target="_blank">Define data blocks with optional filters</a>.
  </p>
<h2 id="datablocksecurity">Data Block Security</h2>
  <p>
    Data Blocks Security is a technique used to secure a selected data block. It is an additional (but optional) security layer on
    top of the Drupal permission &quot;access <strong>repository name</strong> data&quot; right (whereas &quot;repository
    name&quot; is the name of the data block's repository). Using this technique it is possible to enforce additional Drupal
    permissions such as:
  </p>
  <ul>
    <li>access content</li>
    <li>access administration pages</li>
    <li>administer content</li>
    <li>administer users</li>
    <li>administer permissions</li>
    <li>... (the (Drupal permissions) sky is the limit)</li>
  </ul>
Data Blocks Security is actually implemented by means of a comment containing <strong>ACCESS=<em>some_permission</em></strong>.
It is added near the top of a data block which indicates the Drupal permission required to access the data
retrieved by the data block. Its format depends on the format of the Data Block (which can be either in SQL format or in XML format), as further detailed below.

<strong>Note</strong>: While working on these permissions, it may be a good idea to enable the {masquerade_module} module to experience the effects of granting some permission to various types of user roles.

### SQL Data Blocks Security

To specifying Data Blocks Security for a data block in SQL format, use an SQL comment starting with <strong>ACCESS=<em>some_permission</em></strong>,
as in this example:
  
````sql
--ACCESS=administer users
SELECT u.uid,u.name
FROM {role} r
  JOIN {users_roles} ur ON r.rid=ur.rid
  JOIN users u ON ur.uid=u.uid WHERE r.rid = :role
--INFO
type[role]=int
````

### XML Data Blocks Security
To specifying Data Blocks Security for a data block in XML format, use an XML comment line containing <strong>ACCESS=<em>some_permission</em></strong>.

### Custom Data Blocks Security

The security for data block repository is configurable and pluggable, meaning that developers may create functions that
determine how the permission is checked. 


## SQL Parameters
The <strong>:role</strong> parameter in the data blocks example is a
named token that will be replaced in the SQL query with a parameter
  from the report. The parameter replacement is done by Forena in a way that protects against SQL injection. Although
these tokens are modeled after a commonly used database binding syntax, the replacement is done by Forena. Use this
syntax instead of the native parameter binding for any database you are accessing with Forena. Tokens may be referenced
multiple times within the same SQL query.

### SQL Parameter Data Types
All data coming in from parameter forms and from the URL is considered string data. In some cases you may need to make
sure that a data block casts the incoming parameters in a particular type. Numeric values should be included in the SQL
without surrounding quotes, but in a way that is safe from SQL injection attacks.</p>

You can use the --INFO section of your data block to specify data types for parameters in the data block as in this
example:

````sql

--ACCESS=access content
SELECT nid FROM node 
  WHERE promote=1
    AND status=1
  ORDER BY sticky DESC, created 
--IF=:limit
LIMIT :limit 
--ELSE 
LIMIT 10
--END
--INFO
type[limit]=int
````

In the above example (the last line of it), the <strong>:limit</strong> parameter is specified to be of type <strong>int</strong>.  The following Parameter Data Types are supported:

  <table>
    <tbody>
      <tr>
        <th>int</th>
        <td>Convert to an integer. This is useful for limit queries as in the above example.</td>
      </tr>
      <tr>
        <th>numeric</th>
        <td>Convert to a floating point number (e.g. 6.2).</td>
      </tr>
      <tr>
        <th>array</th>
        <td>Convert to an array. This is useful for <strong>IN</strong> clauses.</td>
      </tr>
      <tr>
        <th>date</th>
        <td>Convert to an ISO representation of a date string in YYYY-MM-DD HH:MI:SS format. PHP date creation syntax is supported, so you can use values like now + 1 year.</td>
      </tr>
      <tr>
        <th>unixtime</th>
        <td>Convert to a UNIX timestamp version of time and treat this as an integer. This is particularly useful for working with Drupal dates.  PHP date creation syntax is supported, so you can use values like now + 1 year.</td>
      </tr>
    </tbody>
  </table>

## Conditional SQL  
When building data blocks you can specify sections of sql that are only included if a particular parameter is present.
This lets you create SQL that has optional filters that can be of significant complexity and don't get included unless needed.</p>

### If statements

The <strong>--IF / --ELSE / --END</strong> syntax provides a way to test a value of an incoming parameter to conditionally
construct part of an SQL statement. The following example illustrates this technique: 

````sql
SELECT * from
  states 
--IF=:state 
WHERE code=:state 
--ELSE 
WHERE code='AL' 
--END 
ORDER BY NAME
````

In the above example the WHERE clause is only added to the SQL if there is a value specified for the report parameter
<strong>:state</strong>. If no value for the :state parameter is provided, then the WHERE clause limits the selection to code
of 'AL'. The ORDER BY clause is always included (since it is not part of the --IF / --ELSE / --END construct).

### Switch Case Else
Alternatively the <strong>--SWITCH / --CASE / --ELSE / --END</strong> syntax provides a way to test for multiple values of an
incoming parameter and conditionally construct part of an SQL statement. The following example illustrates this technique:

````sql

SELECT * from some_database_table
--SWITCH=:sort
--CASE=code
ORDER BY code
--CASE=total
ORDER BY total
--ELSE
ORDER BY name
--END 
````

In the above example, if the report parameter <strong>:sort</strong> passed a value of
<strong>code</strong>, it would create an <strong>ORDER BY code</strong> line in the SQL statement. But if it passed 
a value of <strong>total</strong> it would create an <strong>ORDER BY total</strong> line in the SQL statement. In 
all other cases, it would create an <strong>ORDER BY name</strong> line in the SQL statement.
<h2 id="datablockincludes">Data Block Includes</h2>
  <p>Data blocks can be build from other data blocks. To accomplish this use the --INCLUDE directive as in this example:
  </p>
<pre class="code">
--ACCESS=access content
SELECT * FROM (
--INCLUDE=users_by_state
) t
WHERE state=:state
</pre>
  <p>Be aware however of these restrictions / limitations when including other data blocks:
  </p>
  <ul>
  <li>you can only include blocks within the same repository.</li>
  <li>the security of the including data block is the security that is used for the included data block (the --ACCESS line of the included data block is ignored).</li>
  </ul>  
  
## Drupal Entities
When using the Drupal data driver, you can create data blocks that load Drupal entities instead of selecting columns from the
database. To do this specify an <strong>entity_type</strong> and <strong>entity_id</strong> column in the --INFO section of
your data block as illustrated in this example:
  
````sql
--ACCESS=access content
SELECT nid, type, title, uid, sticky, promote FROM node
WHERE type=:content_type AND status=1 
ORDER BY title
--INFO
; This demonstrates loading node entities.
entity_type = node
entity_id = nid

````
This example illustrates loading a node, but any entity type (like users, or some custom entity type) may be loaded.

## Raw Mode Queries
In some cases where Forena is being used to export data, memory consumption can be reduced by specifying a <strong>return_type</strong>
option of <strong>raw</strong> in the data block.

The following example illustrates raw mode used in a Drupal query.</p>
````sql

SELECT nid, type, title, uid, sticky, promote from node
--INFO
; Use raw mode 
return_type = raw
````
Currently this option is only supported in the Drupal and PDO drivers. The one drawback for using raw mode is that all 
of the XPATH features are disabled for that data query as Forena will not prerender the result set into XML. This means
that your iterators can only use an frx:foreach attribute of * and only column names may be used as token replacements 
in the query. You also will be unable to use XPATH evaluation expressions in your reports that use this feature.
