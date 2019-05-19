class php::curl (
    $cainfo = undef,
) {

    if !defined(Package['php5-curl']) { package { 'php5-curl': require => Package['php5-cli'] } }

    file { '/etc/php5/mods-available/curl.ini':
        content => template('php/etc/php5/mods-available/curl.ini.erb'),
        require => Package['php5-curl'],
        notify  => Exec['php::restart'],
    }

    exec { 'php::curl::enable':
        provider => 'shell',
        command  => 'php5enmod -s ALL curl',
        onlyif   => 'for x in `php5query -S`; do if [ ! -f /etc/php5/$x/conf.d/20-curl.ini ]; then echo "onlyif"; fi; done | grep onlyif',
        require  => File['/etc/php5/mods-available/curl.ini'],
        notify   => Exec['php::restart'],
    }

}
