<?php

namespace Drush\Commands;

/**
 * A Drush commandfile.
 */
class DropTheRipperCommands extends DrushCommands
{

    /**
     * Path to a custom wordlist, or 'default'.
     *
     * @var string
     */
    protected $wordlist;

    /**
     * Number of entries from the wordlist to use.
     *
     * @var int
     */
    protected $top;

    /**
     * Whether to use the entire wordlist.
     *
     * @var boolean
     */
    protected $all;

    /**
     * Whether to hide cracked passwords.
     *
     * @var boolean
     */
    protected $hide;

    /**
     * Crack Drupal password hashes.
     *
     * @param $user_rids
     *   (Optional) Only check passwords for users with these role IDs (comma separate multiple IDs).
     * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
     * @option wordlist
     *   Path to a custom wordlist (default is openwall's password list).
     * @option top
     *   Number of passwords to read from the wordlist (default is 25).
     * @option all
     *   Use all entries from the wordlist (default if a custom wordlist is supplied).
     * @option hide
     *   Do not show plaintext passwords in output.
     * @option uid
     *   Comma separated list of user ids.
     * @option restricted
     *   Check all users with roles that have restricted (admin) permissions.
     * @option no-guessing
     *   Disables built-in password guessing (e.g. username as password).
     * @bootstrap full
     * @usage drush dtr
     *   Try to crack passwords of all users.
     * @usage drush dtr --top=100
     *   Try to crack passwords of all users, using the first 100 passwords from the wordlist.
     * @usage drush dtr 3
     *   Try to crack passwords of all users with role 3 in D7 ("drush rls" lists role IDs).
     * @usage drush dtr editor
     *   Try to crack passwords of all users with editor role in D8 ("drush rls" lists role IDs).
     * @usage drush dtr --uid=1
     *   Try to crack password of user number 1.
     * @usage drush dtr --restricted
     *   Try to crack passwords of all users with roles that have restricted permissions.
     * @usage drush dtr --wordlist=/tmp/rockyou.txt
     *   Use a custom wordlist for password cracking.
     * @usage drush dtr --all --no-guessing
     *   Try every password in the wordlist, but do not try to guess user passwords.
     *
     * @command drop:the-ripper
     * @aliases dtr,drop-the-ripper
     */
    public function theRipper($user_rids = 'all', array $options = ['wordlist' => 'default', 'top' => 25, 'all' => null, 'hide' => FALSE, 'uid' => 'all', 'restricted' => FALSE, 'no-guessing' => FALSE])
    {
        $this->wordlist = $options['wordlist'];
        $this->all = $options['all'];
        $this->top = $options['top'];
        $this->hide = $options['hide'];

        $start = microtime(TRUE);
        $try_guesses = !$options['no-guessing'];
        $user_uids = $options['uid'];

        $wordlist = $this->get_wordlist();
        if (!$wordlist) {
            $msg = 'Could not find that wordlist.';
            throw new \Exception($msg);
        }
        $passwords = $this->load_wordlist($wordlist);

        $user_query = db_select('users_field_data', 'u')
            ->fields('u')
            ->condition('u.uid', 0, '>');
        $user_query->distinct();
        $conditions = db_or();

        $uids = [];
        if ($options['restricted']) {
            // Ensure User 1 is included as having restricted perms.
            $uids += [1];
            // This argument overrides any supplied rids.
            $user_rids = implode(',', $this->get_restricted_roles());
        }
        if ($user_uids != 'all' && $uid_opt = $this->get_ids($user_uids)) {
            $uids += $uid_opt;
        }
        $uids = array_unique($uids);
        if (count($uids)) {
                $conditions->condition('u.uid', $uids, 'IN');
        }

        if ($user_rids != 'all') {
            if ($rids = $this->get_role_target_ids($user_rids)) {
                $user_query->leftJoin(
                    'user__roles',
                    'ur',
                    'u.uid = ur.entity_id AND ur.bundle = :user',
                    [':user' => 'user']
                );
                $conditions->condition('ur.roles_target_id', $rids, 'IN');
            }
        }

        if ($conditions->count()) {
            $user_query->condition($conditions);
        }

        $users = $user_query->execute()
            ->fetchAll();

        $user_checks = 0;
        $pw_checks = 0;
        foreach ($users as $user) {
            $user_checks++;
            if ($try_guesses) {
                $guesses = $this->user_guesses($user);
                foreach ($guesses as $guess) {
                    $pw_checks++;
                    if ($this->check_password($user, $guess)) {
                        continue 2; // No need to try the passwords for this user.
                    }
                }
            }
            foreach ($passwords as $password) {
                $pw_checks++;
                if ($this->check_password($user, $password)) {
                    break;
                }
            }
        }
        $finish = microtime(TRUE);
        $this->logger()->notice(dt('Ran @pc password checks for @uc users in @sec seconds.', array(
            '@pc' => $pw_checks,
            '@uc' => $user_checks,
            '@sec' => sprintf('%.2f', $finish - $start),
        )));
    }

    /**
     * Check a user password in Drupal 8.
     *
     * @param object $user
     *   A user object.
     *
     * @param string $password
     *   The password to check.
     *
     * @return bool
     *   Whether the password matched.
     */
    function check_password($user, $password)
    {
        $container = \Drupal::getContainer();
        $user_auth = $container->get('user.auth');
        $matched = FALSE;
        $this->logger()->debug(dt("Check: uid=@uid name=@name for password '@pass'", array(
            '@uid' => $user->uid,
            '@name' => $user->name,
            '@pass' => $password,
        )));
        if ($user_auth->authenticate($user->name, $password) === $user->uid) {
            $this->logger()->notice(dt('Match: uid=@uid name=@name password=@pass', array(
                '@uid' => $user->uid,
                '@name' => $user->name,
                '@pass' => $this->hide ? 'XXXXXXXXXX' : $password,
            )));
            $matched = TRUE;
        }
        return $matched;
    }

    /**
     * Get a list of restricted permissions in Drupal 8.
     *
     * @return array
     *   The names of permissions marked as restricted.
     */
    function get_restricted_permissions()
    {
        $container = \Drupal::getContainer();
        $all_perms = $container->get('user.permissions')->getPermissions();
        foreach ($all_perms as $name => $perm) {
            if (!empty($perm['restrict access'])) {
                $restricted_perms[] = $name;
            }
        }
        $this->logger()->debug(dt('Restricted perms: @perms', ['@perms' => implode(', ', $restricted_perms)]));
        return $restricted_perms;
    }

    /**
     * Get a list of roles with restricted permissions in Drupal 8.
     *
     * @return array
     *   The names of roles with restricted permissions.
     */
    function get_restricted_roles()
    {
        $restricted_perms = $this->get_restricted_permissions();
        $roleStorage = \Drupal::entityManager()->getStorage('user_role');
        $roles = $roleStorage->loadMultiple();
        $restricted_roles = array();
        foreach ($roles as $name => $role) {
            if ($role->isAdmin() || count(array_intersect($restricted_perms, $role->getPermissions()))) {
                $restricted_roles[] = $name;
            }
        }
        $this->logger()->debug(dt('Restricted roles: @roles', ['@roles' => implode(', ', $restricted_roles)]));
        return $restricted_roles;
    }

    /**
     * Process the --wordlist option, or use a default.
     *
     * @return string|bool
     *   Path to the wordlist, or FALSE if it does not exist.
     */
    function get_wordlist()
    {
        if ($this->wordlist == 'default') {
            $wordlist = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'password';
        } else {
            // Custom wordlist; use all entries unless a --top option was supplied.
            if ((int)$this->top == 0) {
                $this->all = TRUE;
            }
        }
        if (!file_exists($wordlist)) {
            $wordlist = FALSE;
        }
        return $wordlist;
    }

    /**
     * Make a few guesses about a user's password.
     *
     * @param object $user
     *   A (basic) user object.
     *
     * @return array
     *   Guesses at the user's password.
     */
    function user_guesses($user)
    {
        $guesses = array();
        $guesses[] = $user->name;
        $guesses[] = $user->name . date('Y');
        $guesses[] = $user->mail;
        if (preg_match('/(.*)@(.*)\..*/', $user->mail, $matches)) {
            $guesses[] = $matches[1]; // Username portion of mail.
            $guesses[] = $matches[2]; // First part of domain.
        }
        return array_unique(array_filter($guesses));
    }

    /**
     * Parse a wordlist file into an array.
     *
     * @param string $wordlist
     *   Path to the wordlist file.
     *
     * @return array
     *   Candidate passwords.
     */
    function load_wordlist($wordlist)
    {
        $passwords = file($wordlist);
        $passwords = array_filter($passwords, [$this, 'wordlist_filter_callback']);
        $passwords = array_map([$this, 'trim_newline'], $passwords);
        $passwords = array_unique($passwords);

        if (!$this->all) {
            if (($top = (int)$this->top) > 0) {
                $passwords = array_slice($passwords, 0, $top);
            }
        }

        return $passwords;
    }

    /**
     * Callback for wordlist array filtering; removes comments.
     *
     * @param string $line
     *   An item from a wordlist.
     *
     * @return bool
     *   FALSE if the line is a comment.
     */
    function wordlist_filter_callback($line)
    {
        return (strpos($line, '#!comment:') !== 0);
    }

    /**
     * Callback for wordlist array trimming; remove only trailing newlines.
     *
     * @param string $line
     *   An item from a wordlist.
     *
     * @return string
     *   Candidate password with trailing newline removed.
     */
    function trim_newline($line)
    {
        // Note that double quotes are necessary for the whitespace characters.
        return rtrim($line, "\r\n");
    }

    /**
     * Parse the supplied (u|r)ids option.
     *
     * @param string $ids
     *   The user-supplied list of uids or rids.
     *
     * @return array|bool
     *   Array of numeric ids, or FALSE if none were valid.
     */
    function get_ids($ids)
    {
        $ids = explode(',', $ids);
        $ids = array_map('trim', $ids);
        $ids = array_filter($ids, 'is_numeric');
        return (empty($ids) ? FALSE : $ids);
    }

    /**
     * Parse the supplied roles option.
     *
     * @param string $user_rids
     *   The user-supplied list of roles.
     *
     * @return array|bool
     *   Array of roles_target_id's, or FALSE if none were valid.
     */
    function get_role_target_ids($user_rids)
    {
        $rids = explode(',', $user_rids);
        $rids = array_map('trim', $rids);
        // todo: filter target_id (based on ASCII character set)
        return (empty($rids) ? FALSE : $rids);
    }
}
