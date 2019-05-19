class php::ldap (
    $max_links = undef,
) {

    if !defined(Package['php5-ldap']) { package { 'php5-ldap': require => Package['php5-cli'] } }

    file { '/etc/php5/mods-available/ldap.ini':
        content => template('php/etc/php5/mods-available/ldap.ini.erb'),
        require => Package['php5-ldap'],
        notify  => Exec['php::restart'],
    }

    exec { 'php::ldap::enable':
        provider => 'shell',
        command  => 'php5enmod -s ALL ldap',
        onlyif   => 'for x in `php5query -S`; do if [ ! -f /etc/php5/$x/conf.d/20-ldap.ini ]; then echo "onlyif"; fi; done | grep onlyif',
        require  => File['/etc/php5/mods-available/ldap.ini'],
        notify   => Exec['php::restart'],
    }

}
