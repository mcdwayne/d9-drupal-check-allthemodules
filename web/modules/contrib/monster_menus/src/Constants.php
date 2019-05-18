<?php

namespace Drupal\monster_menus;

/**
 * @file
 * Various constants used by Monster Menus
 */

class Constants {
  // Constants used in mm_get_node_info() and hook_mm_node_info()
  const MM_NODE_INFO_NO_REORDER = 'no_reorder'; // don't allow this node type to be reordered
  const MM_NODE_INFO_NO_RENDER  = 'no_render';  // don't render this node type
  const MM_NODE_INFO_ADD_HIDDEN = 'add_hidden'; // hide the Add node link for this type

  // Constants used in mm_content_get()
  const MM_GET_ARCHIVE = '_arch';  // return archive status
  const MM_GET_FLAGS   = '_flags'; // return flags
  const MM_GET_PARENTS = '_par';   // return parents

  // Constants used in mm_content_get_tree() and mm_content_get_query()
  const MM_GET_TREE_ADD_SELECT        = '_sel';   // add to list of selected columns
  const MM_GET_TREE_BIAS_ANON         = '_bias';  // assume user 0 can't read any groups
  const MM_GET_TREE_DEPTH             = '_depth'; // tree recursion depth
  const MM_GET_TREE_FAKE_READ_BINS    = '_read';  // pretend user has read on all bins (used internally)
  const MM_GET_TREE_FILTER_BINS       = '_bins';  // return recycle bins
  const MM_GET_TREE_FILTER_DOTS       = '_dots';  // return all entries with names starting with '.'
  const MM_GET_TREE_FILTER_GROUPS     = '_grps';  // get groups
  const MM_GET_TREE_FILTER_NORMAL     = '_norm';  // get entries not group or in /users
  const MM_GET_TREE_FILTER_USERS      = '_usrs';  // get entries in /users
  const MM_GET_TREE_INNER_FILTER      = '_inner'; // used internally
  const MM_GET_TREE_MMTID             = '_mmtid'; // tree ID to query
  const MM_GET_TREE_NODE              = '_node';  // node object to query permissions for
  const MM_GET_TREE_RETURN_BINS       = '_rbins'; // return list of parent recycle bins
  const MM_GET_TREE_RETURN_BLOCK      = '_rblk';  // return attributes from the mm_tree_block table
  const MM_GET_TREE_RETURN_FLAGS      = '_rflgs'; // return attributes from the mm_tree_flags table
  const MM_GET_TREE_RETURN_KID_COUNT  = '_rkids'; // return the number of children each entry has
  const MM_GET_TREE_RETURN_MTIME      = '_rmods'; // return the mtime and muid fields
  const MM_GET_TREE_RETURN_NODE_COUNT = '_rnode'; // include a "nodecount" field, containing the number of nodes using this entry
  const MM_GET_TREE_RETURN_PERMS      = '_rprms'; // return whether or not the user can perform an action
  const MM_GET_TREE_RETURN_TREE       = '_rtree'; // return attributes from the mm_tree table
  const MM_GET_TREE_SORT              = '_sort';  // sort results by weight, alpha, etc.; always TRUE when depth != 0
  const MM_GET_TREE_USER              = '_user';  // user object to test permissions against
  const MM_GET_TREE_WHERE             = '_where'; // add a WHERE clause to the outermost query

  // Constants used only in mm_content_get_tree()
  const MM_GET_TREE_ADD_TO_CACHE  = '_cache'; // add results to the caches used by mm_content_get() and mm_content_get_parents()
  const MM_GET_TREE_BLOCK         = '_block'; // retrieve entries which appear in a particular block
  const MM_GET_TREE_FILTER_HIDDEN = '_hide';  // get "hidden" entries
  const MM_GET_TREE_HERE          = '_here';  // list of tree IDs currently being viewed
  const MM_GET_TREE_ITERATOR      = '_iter';  // GetTreeIterator (or subclass)
  const MM_GET_TREE_PRUNE_PARENTS = '_ppar';  // prune parents depending upon max_parents
  const MM_GET_TREE_VIRTUAL       = '_virt';  // include virtual user list sub-entries

  const MM_GET_TREE_STATE_COLLAPSED = (1<<0);
  const MM_GET_TREE_STATE_DENIED    = (1<<1);
  const MM_GET_TREE_STATE_EXPANDED  = (1<<2);
  const MM_GET_TREE_STATE_HERE      = (1<<3);
  const MM_GET_TREE_STATE_HIDDEN    = (1<<4);
  const MM_GET_TREE_STATE_LEAF      = (1<<5);
  const MM_GET_TREE_STATE_NOT_WORLD = (1<<6);
  const MM_GET_TREE_STATE_RECYCLE   = (1<<7);

  // Constants used in mm_content_copy()
  const MM_COPY_ALIAS              = 'alia';
  const MM_COPY_COMMENTS           = 'comm';
  const MM_COPY_CONTENTS           = 'cont';
  const MM_COPY_ITERATE_ALTER      = 'itra';
  const MM_COPY_NAME               = 'name';
  const MM_COPY_NODE_PRESAVE_ALTER = 'noda';
  const MM_COPY_OWNER              = 'ownr';
  const MM_COPY_READABLE           = 'read';
  const MM_COPY_RECUR              = 'recr';
  const MM_COPY_TREE               = 'tree';
  const MM_COPY_TREE_PRESAVE_ALTER = 'trea';
  const MM_COPY_TREE_SKIP_DUPS     = 'tdup';

  // Constants present in mm_tree.name
  const MM_ENTRY_NAME_DEFAULT_USER  = '.Default';
  const MM_ENTRY_NAME_DISABLED_USER = '.Disabled';
  const MM_ENTRY_NAME_GROUPS        = '.Groups';
  const MM_ENTRY_NAME_LOST_FOUND    = '.LostAndFound';
  const MM_ENTRY_NAME_RECYCLE       = '.Recycle';
  const MM_ENTRY_NAME_SYSTEM        = '.System';
  const MM_ENTRY_NAME_USERS         = '.Users';
  const MM_ENTRY_NAME_VIRTUAL_GROUP = '.Virtual';

  // Constants present in mm_tree.alias
  const MM_ENTRY_ALIAS_SYSTEM     = '-system';
  const MM_ENTRY_ALIAS_LOST_FOUND = 'lost';

  // Constants used in mm_content_get_tree(), _mm_content_get_tree_query(),
  // mm_content_user_can() and mm_content_user_can_node()
  const MM_PERMS_WRITE          = 'w';
  const MM_PERMS_SUB            = 'a';
  const MM_PERMS_APPLY          = 'u';
  const MM_PERMS_READ           = 'r';
  const MM_PERMS_IS_GROUP       = '_isgrp';
  const MM_PERMS_IS_USER        = '_isusr';
  const MM_PERMS_ADMIN          = '_admin';
  const MM_PERMS_IS_RECYCLE_BIN = '_isbin';
  const MM_PERMS_IS_RECYCLED    = '_isrec';
  // Constant used in mm_content_user_can_recycle().
  const MM_PERMS_IS_EMPTYABLE   = 'EMPTY';

  // Constants used for the $mmtid parameter to mm_content_node_is_recycled().
  const MM_NODE_RECYCLED_MMTID_CURR = -1;  // Recycled on the current page
  const MM_NODE_RECYCLED_MMTID_EXCL = 0;   // Recycled on all referring pages

  // The maximum number of sub-items per item in the tree is
  // MM_CONTENT_BTOA_BASE ^ MM_CONTENT_BTOA_CHARS. If you might have more than
  // this many /Users (or any other level of the tree) someday, increase
  // MM_CONTENT_BTOA_CHARS and run mm_content_update_sort(). A larger
  // MM_CONTENT_BTOA_BASE cannot be used, unless you are using a case-sensitive
  // collation on the mmtree.sort_idx database column.
  //
  // The maximum nesting level of the tree is the length of mm_tree.sort_idx /
  // MM_CONTENT_BTOA_CHARS. While you can increase this by altering the schema,
  // you may find that MySQL starts to complain about there being too many tables
  // in the JOIN in monster_menus_url_inbound_alter(). MM_CONTENT_MYSQL_MAX_JOINS
  // is used in monster_menus.install to ensure that this limit isn't exceeded.
  const MM_CONTENT_BTOA_START = 33;   // ord('!')
  const MM_CONTENT_BTOA_BASE  = 64;
  const MM_CONTENT_BTOA_CHARS = 4;

  // When MM tries to turn a long URL into a menu path, it does a JOIN against
  // mm_tree for each path segment. This constant keeps MySQL from reporting an
  // error due to too many JOINs. While this value can technically be 61,
  // experience has shown that performance is so poor with that number, that the
  // server becomes unusable.
  const MM_CONTENT_MYSQL_MAX_JOINS = 40;

  // Constants related to the virtual group "dirty" field
  const MM_VGROUP_DIRTY_NOT       = 0; // not dirty
  const MM_VGROUP_DIRTY_NEXT_CRON = 1; // update during next hook_cron()
  const MM_VGROUP_DIRTY_FAILED    = 2; // previously failed sanity check
  const MM_VGROUP_DIRTY_REDO      = 3; // failed, but OK to regenerate
  // If the count of users in a virtual group decreases by more than this ratio,
  // return an error message and stop the vgroup regeneration. Set the matching
  // record's "dirty" field in mm_vgroup_query to MM_VGROUP_DIRTY_REDO to ignore
  // this condition and regenerate the group during the next run.
  const MM_VGROUP_COUNT_SANITY    = 0.20;

  // Constant used by DefaultController::saveSitemap()
  const MM_SITEMAP_MAX_LEVEL_DEFAULT = 6;

  // Constants used by mm_ui.inc
  const MM_UI_MAX_USERS_IN_GROUP = 20; // max number of users to display (there can be more in the DB)
  const MM_UI_MAX_REORDER_ITEMS = 100; // max number of nodes/subpages to reorder

  // hook_mm_showpage_routing() page removal prevention modes
  const MM_PREVENT_SHOWPAGE_REMOVAL_NONE = '';
  const MM_PREVENT_SHOWPAGE_REMOVAL_WARN = 'warn';
  const MM_PREVENT_SHOWPAGE_REMOVAL_HALT = 'halt';

  // Search feature: Set this value to TRUE to enable some debugging messages and
  // to see the search query submitted via AJAX.
  const MMSR_debug = FALSE;

  // Constants used in mm_import_export.inc.
  const MM_IMPORT_ADD     = 'add';
  const MM_IMPORT_UPDATE  = 'update';
  const MM_IMPORT_DELETE  = 'delete';

  // Miscellaneous Constants

  const MM_HOME_MMTID_DEFAULT = 7;
  const MM_COMMENT_READABILITY_DEFAULT = 'comment readability default';  // permission name

  // Block IDs
  const MM_MENU_BID     = '1';  // block ID (bid) containing the page section title
  const MM_MENU_DEFAULT = '0';  // block ID (bid) neutral default
  const MM_MENU_UNSET   = '-1'; // block ID (bid) when unspecified

  const MM_DEFAULT_NODES_PER_PAGE       = 10;
  const MM_LAZY_LOAD_NUMBER_OF_NODES    = 10; // If the lazy loader is used to get nodes for a page, this is the number of nodes it gets per chunk
  const MM_MAX_NUMBER_OF_NODES_PER_PAGE = 500; // In cases where all the nodes are loaded for a particular page, limit the number returned (a value of 0 here bypasses this check)

  // Max. number of items to auto-delete from recycle bins during a cron run
  const MM_CRON_EMPTY_BINS_LIMIT = 500;

  const MM_LARGE_GROUP_TOKEN = 'mm_large_group_token';

  // Max. number of results to display at admin/mm/fix-nodes
  const MM_ADMIN_NODE_URL_PREVIEW_COUNT = 50;

  // Name of the default page region
  const MM_UI_REGION_CONTENT = 'content';

  // The default age, in seconds, after which to act on unchanged user homepages
  const MM_UNMODIFIED_HOMEPAGES_MAX_AGE = 30 * 24 * 60 * 60;

  const MM_NODE_ACCESS_ALLOW  = 'allow';
  const MM_NODE_ACCESS_DENY   = 'deny';
  const MM_NODE_ACCESS_IGNORE = NULL;

  const MM_IMPORT_VERSION = '1.0';
}
