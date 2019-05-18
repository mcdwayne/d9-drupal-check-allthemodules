<?php

namespace Drupal\loopit\Aggregate;

class AggregateFilter {
  static $context = [];

  /**
   * Match full class name against a group of namespaces.
   *
   * Will be called plenty of time so use string related functions instead of
   * regex ones.
   *
   * @param string $group
   *  The group of namespaces. Can be:
   *  - __core__: any class comming from Drupal core
   *  - __contrib__ : any class comming from contributed module (relative to
   *  modules/contrib/ directory)
   *  - __custom__ : any class comming from custom module (relative to
   *  modules/custom/ directory)
   *  - __module__: any class comming from a Drupal module in any module
   *  directory (from the state variable "system.module.files").
   *
   * @param string $full_classname
   *  The full class name
   */
  public static function matchNamespaceGroup($group, $full_classname) {
    $match = FALSE;
    $dr_ns_len = 7;
    $full_classname = ltrim($full_classname, '\\');
    // TODO: The first part of namespace: Drush, Symfony, Vendors, ...
    // TODO: Use autoload map for valid class name check
    if (strpos($full_classname, 'Drupal\\') === 0) {
      if (($pos = strpos($full_classname, '\\', $dr_ns_len)) !== FALSE) {
        $dr_ns = substr($full_classname, $dr_ns_len, $pos-$dr_ns_len);
        // Drupal core classes from Core or Component namespace.
        if ($group == '__core__' && ($dr_ns == 'Core' || $dr_ns == 'Component')) {
          $match = TRUE;
        }
        // Drupal module
        elseif (isset(self::$context['system.module.files'][$dr_ns])) {
          if ($group == '__module__') {
            $match = TRUE;
          }
          else if ($group == '__core__' && strpos(self::$context['system.module.files'][$dr_ns], 'core/') === 0) {
            $match = TRUE;
          }
          else if ($group == '__contrib__' && strpos(self::$context['system.module.files'][$dr_ns], 'modules/contrib/') === 0) {
            $match = TRUE;
          }
          else if ($group == '__custom__' && strpos(self::$context['system.module.files'][$dr_ns], 'modules/custom/') === 0) {
            $match = TRUE;
          }
        }
      }
    }

    return $match;
  }

  /**
   * Match a pattern to a string.
   *
   * Available patterns:
   *
   * - "*": all
   * - "*some_end": ends with "some_end"
   * - "some_start*": starts with "some_start"
   * - "*some_contains*": contains "some_contains"
   * - full match: $pattern === $string
   * - keep reserved keys from aggregates (__CLASS__, __HASH__,
   * __ARRAY_PARENTS__)
   * - Available namespace groups for matching. See self::matchNamespaceGroup()
   *
   * @param string $pattern
   * @param string $string
   * @return boolean
   */
  public static function match($pattern, $string) {
    $match = FALSE;

    // Any
    if ($pattern === '*') {
      $match = TRUE;
    }
    // Keep reserved keys from aggregates
    elseif (substr($string, 0, 2) === '__' && substr($string, -2) === '__') {
      $match = TRUE;
    }
    // Available namespace groups for matching
    elseif (in_array($pattern, ['__core__', '__module__', '__contrib__', '__custom__'])) {
      $match = self::matchNamespaceGroup($pattern, $string);
    }
    // Free match on drupal namespace (in addition to the provided
    // namespace groups, see previous elseif)
    elseif (substr($pattern, 0, 2) == '__' && substr($pattern, -2) === '__') {
      $dr_ns_len = 7;
      $string = ltrim($string, '\\');
      // TODO: See also match on $full_classname in self::matchNamespaceGroup()
      if (strpos($string, 'Drupal\\') === 0) {
        //$string = substr($string, $dr_ns_len);
        if (($pos = strpos($string, '\\', $dr_ns_len)) !== FALSE) {
          $dr_ns = substr($string, $dr_ns_len, $pos-$dr_ns_len);
          $match = self::match(substr($pattern, 2, -2), $dr_ns);
        }
      }
    }
    else {
      // "Starts with" substring
      $asterisk = substr($pattern, -1);
      $starts_with = $ends_with = FALSE;
      if (
          $asterisk === '*'
          && substr($pattern, -2) !== '\\' . $asterisk
        ) {
          // Drop the last which is "*"
          $starts_with = substr($pattern, 0, -1);
      }
      // "Ends with" substring
      if ($pattern[0] === '*') {
        // Drop the first which is "*"
        $ends_with = substr($pattern, 1);
      }

      // "Contains" match using "starts with" and "ends with" substring
      if ($starts_with && $ends_with) {
        $starts_with = substr($starts_with, 1);
        $ends_with = substr($ends_with, 0, -1);
        $match = $starts_with === $ends_with && (strpos($string, $starts_with) !== FALSE);
      }
      // "Starts with" match
      elseif ($starts_with && strpos($string, $starts_with) === 0) {
        $match = TRUE;
      }
      // "Ends with" match
      elseif ($ends_with) {
        $ends_with_count = strlen($ends_with);
        if(substr($string, -$ends_with_count) === $ends_with) {
          $match = TRUE;
        }
      }
      // Whole string match
      elseif (!$starts_with && !$ends_with) {
        // Drop any escape like "\*"
        $match = str_replace('\\', '', $pattern) === $string;
      }
    }

   return $match;
  }

  /**
   * Filter using the "subset_array_parents" option
   *
   * @param Drupal\loopit\Aggregate\AggregateArray $aggregate
   * @param mixed $current
   * @param string $index
   * @return mixed
   */
  public static function onCurrentSubsetArrayParents(AggregateArray $aggregate, $current, $index) {

    $options = $aggregate->getOptions();
    if (isset($options['subset_array_parents'])) {
      $parents = $aggregate->getArrayParents();
      $parents[] = $index;

      // OR conjuction for entries in $options['subset_array_parents'].
      // AND conjuction between parents keys match (from $subset_array_path).
      $accept = FALSE;
      foreach ($options['subset_array_parents'] as $subset_array_path => $subset_leaf) {
        $subset_array_parents = explode('/', $subset_array_path);

        // Compare $subset_array_parents to $parents.
        foreach ($parents as $i => $parent) {

          // For levels that are omit assume TRUE.
          if (!isset($subset_array_parents[$i])) {
            $accept = $accept && TRUE;
          }
          // In first time $subset_array_parents[$i] is always set (at least one
          // entry in $subset_array_path).
          else {
            // For the first time of sublevels set the starter value of $accept.
            if (!$i) {
              $accept = self::match($subset_array_parents[$i], $parent);
            // When going through sublevels AND is used.
            }
            else {
              $accept = $accept && self::match($subset_array_parents[$i], $parent);
            }
          }
        }

        // Compare the leaf value $subset_leaf if set (no matter how many
        // parents are there).
        // OR conjunction for leaf match if an array is provided.
        if ($accept && isset($subset_leaf) && $current && !is_array($current)) {

          if (!is_array($subset_leaf)) {
            $subset_leaf = [$subset_leaf];
          }

          $accept_leaf = FALSE;
          foreach ($subset_leaf as $a_subset_leaf_key => $a_subset_leaf) {
            $current_prefix = $current_suffix = '';
            // By default the leaf key to match is not provided.
            $accept_leaf_key = TRUE;

            // A fix to adapt $current as a class namespace if the leaf key
            // is "provider". Used to show the provider for drupal plugins
            // like:
            //   '\*services/plugin.manager.*/\*definitions' => [
            //     '*provider' => '__custom__'
            //   ]
            // Provider key also assumes that $current is a module name
            // TODO: Look for better way.
            if ($index == 'provider' || $index == '*provider') {
              $current_prefix = 'Drupal\\';
              $current_suffix = '\\';
            }

            // Do additional check for the leaf key for no numeric keys.
            if (!is_numeric($a_subset_leaf_key)) {
              // Change "__CLASS__" to "class" for matching. "__CLASS__" is
              // reserved key that bypass matching
              $index_in = $index == '__CLASS__'? 'class' : $index;

              $accept_leaf_key = self::match($a_subset_leaf_key, $index_in);
            }

            // At least one $a_subset_leaf (OR conjunction for matching the leaf array).
            $accept_leaf = $accept_leaf || $accept_leaf_key && self::match($a_subset_leaf, $current_prefix . $current . $current_suffix);
          }
          $accept = $accept_leaf;
        }

        // At least one $accept is sufficient by
        // $options['subset_array_parents'] entry.
        if ($accept) {
          return $current;
        }
      }

      // hasChildren will equal FALSE
      if (!$accept) {
        return FALSE;
      }
    }
    return $current;
  }
}