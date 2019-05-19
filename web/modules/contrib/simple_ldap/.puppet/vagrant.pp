# Global Defaults
Exec {
    path            => "/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin",
    logoutput   => on_failure,
    timeout => 0,
}

Service {
    hasrestart    => true,
    hasstatus => true,
}

Package {
    ensure => present,
}

File {
    ensure => present,
}

# Default Node
node default {
    ## Global prerequisites
    class { 'apt': }
    class { 'avahi': }

    # Development tools
    if !defined(Package['git']) { package { 'git': } }

    # Postfix (prevent outgoing mail)
    class { 'postfix':
        canonical_maps => {'/.*@.*/' => 'vagrant'},
    }

    # Apache
    class { 'apache': }
    class { 'apache::mod::deflate': }
    class { 'apache::mod::expires': }
    class { 'apache::mod::headers': }
    class { 'apache::mod::php5': }
    class { 'apache::mod::rewrite': }

    user { 'vagrant':
        groups  => ['www-data'],
        require => Package['apache2'],
    }

    apache::vhost { 'simpleldap.local':
        documentroot => '/var/www/drupal/docroot',
        normalize    => false,
        https        => false,
    }

    file { '/etc/apache2/conf-available/phpldapadmin.conf':
        content => '
            Alias /pma /usr/local/share/phpldapadmin/htdocs
            <Directory /usr/local/share/phpldapadmin/htdocs>
                DirectoryIndex index.php
                Require all granted
            </Directory>',
        require => Package['apache2'],
    }

    exec { 'a2enconf phpldapadmin':
        creates => '/etc/apache2/conf-enabled/phpldapadmin.conf',
        require => File['/etc/apache2/conf-available/phpldapadmin.conf'],
        notify  => Service['apache2'],
    }

    # MySQL
    class { 'mysql::server': }

    file { '/home/vagrant/.my.cnf':
        source  => '/root/.my.cnf',
        require => Exec['mysql::root::my.cnf'],
    }

    exec { 'mysql::database::drupal':
        command => 'mysql --defaults-file=/root/.my.cnf -e "CREATE DATABASE drupal; GRANT ALL ON drupal.* TO drupal IDENTIFIED BY \'drupal\'; FLUSH PRIVILEGES"',
        require => [Service['mysql'], Exec['mysql::root::my.cnf']],
        unless  => 'mysql --defaults-file=/root/.my.cnf -e "SHOW DATABASES LIKE \'drupal\'" | grep drupal',
    }

    # PHP
    class { 'php': }
    class { 'php::curl': }
    class { 'php::gd': }
    class { 'php::imagick': }
    class { 'php::intl': }
    class { 'php::ldap': }
    class { 'php::mcrypt': }
    class { 'php::memcached': }
    class { 'php::mysql': }
    class { 'php::mysqli': }
    class { 'php::oauth': }
    class { 'php::opcache': }
    class { 'php::uploadprogress': }
    class { 'php::xmlrpc': }
    class { 'php::xsl': }

    # OpenLDAP
    class { 'ldap::server': }
    class { 'ldap::phpldapadmin': }

    exec { 'ldap::populate':
        command => 'service slapd stop && rm -rf /var/lib/ldap/* && slapadd -l /vagrant/.puppet/openldap.ldif && chown -R openldap: /var/lib/ldap && service slapd start',
        require => Service['slapd'],
    }

    # Drupal
    class { 'drush': }

    file { '/var/www/drupal':
        ensure  => directory,
        owner   => 'vagrant',
        group   => 'vagrant',
        require => Package['apache2'],
    }

    exec { 'drupal::download':
        command => 'drush dl drupal --drupal-project-rename=docroot',
        cwd     => '/var/www/drupal',
        user    => 'vagrant',
        creates => '/var/www/drupal/docroot',
        require => [File['/var/www/drupal'], Class['drush'], User['vagrant']],
    }

    exec { 'drupal::install':
        command => 'drush -y site-install --db-url=mysql://drupal:drupal@localhost/drupal --site-name="SimpleLDAP Development" --account-pass=admin',
        cwd     => '/var/www/drupal/docroot',
        creates => '/var/www/drupal/docroot/sites/default/settings.php',
        user    => 'vagrant',
        require => [
            Service['mysql', 'postfix'],
            Exec['drupal::download', 'mysql::database::drupal'],
            User['vagrant'],
        ],
    }

    file { '/var/www/drupal/docroot/sites/default/files':
        ensure  => directory,
        owner   => 'www-data',
        group   => 'www-data',
        mode    => 2775,
        require => Exec['drupal::install'],
    }

    # simple_ldap
    file { '/var/www/drupal/docroot/sites/all/modules/simple_ldap':
        ensure  => '/vagrant',
        require => Exec['drupal::download'],
    }

    exec { 'drush::enable::simple_ldap':
        command => 'drush -y en simple_ldap',
        cwd     => '/var/www/drupal/docroot',
        user    => 'vagrant',
        unless  => 'drush pml --status=enabled | grep \(simple_ldap\)',
        require => File['/var/www/drupal/docroot/sites/all/modules/simple_ldap'],
    }

    exec { 'drush::vset::simple_ldap_host':
        command => 'drush -y vset simple_ldap_host localhost',
        cwd     => '/var/www/drupal/docroot',
        user    => 'vagrant',
        unless  => '/usr/bin/test "localhost" == "$(drush vget --format=list simple_ldap_host)"',
        require => Exec['drush::enable::simple_ldap'],
    }

    exec { 'drush::vset::simple_ldap_binddn':
        command => 'drush -y vset simple_ldap_binddn cn=admin,dc=local',
        cwd     => '/var/www/drupal/docroot',
        user    => 'vagrant',
        unless  => '/usr/bin/test "cn=admin,dc=local" == "$(drush vget --format=list simple_ldap_binddn)"',
        require => Exec['drush::enable::simple_ldap'],
    }

    exec { 'drush::vset::simple_ldap_bindpw':
        command => 'drush -y vset simple_ldap_bindpw admin',
        cwd     => '/var/www/drupal/docroot',
        user    => 'vagrant',
        unless  => '/usr/bin/test "admin" == "$(drush vget --format=list simple_ldap_bindpw)"',
        require => Exec['drush::enable::simple_ldap'],
    }

    exec { 'drush::vset::simple_ldap_basedn':
        command => 'drush -y vset simple_ldap_basedn dc=local',
        cwd     => '/var/www/drupal/docroot',
        user    => 'vagrant',
        unless  => '/usr/bin/test "dc=local" == "$(drush vget --format=list simple_ldap_basedn)"',
        require => Exec['drush::enable::simple_ldap'],
    }
}
