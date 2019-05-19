class php::oauth {

    if !defined(Package['php5-oauth']) { package { 'php5-oauth': require => Package['php5-cli'] } }

    file { '/etc/php5/mods-available/oauth.ini':
        content => template('php/etc/php5/mods-available/oauth.ini.erb'),
        require => Package['php5-oauth'],
        notify  => Exec['php::restart'],
    }

    exec { 'php::oauth::enable':
        provider => 'shell',
        command  => 'php5enmod -s ALL oauth',
        onlyif   => 'for x in `php5query -S`; do if [ ! -f /etc/php5/$x/conf.d/20-oauth.ini ]; then echo "onlyif"; fi; done | grep onlyif',
        require  => File['/etc/php5/mods-available/oauth.ini'],
        notify   => Exec['php::restart'],
    }

}
