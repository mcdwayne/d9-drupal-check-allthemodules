class php::mysql (
    $allow_local_infile = undef,
    $allow_persistent = undef,
    $max_persistent = undef,
    $max_links = undef,
    $trace_mode = undef,
    $default_port = undef,
    $default_socket = undef,
    $default_host = undef,
    $default_user = undef,
    $default_password = undef,
    $connect_timeout = undef,
) {

    if !defined(Package['php5-mysql']) { package { 'php5-mysql': require => Package['php5-cli'] } }

    file { '/etc/php5/mods-available/mysql.ini':
        content => template('php/etc/php5/mods-available/mysql.ini.erb'),
        require => Package['php5-mysql'],
        notify  => Exec['php::restart'],
    }

    exec { 'php::mysql::enable':
        provider => 'shell',
        command  => 'php5enmod -s ALL mysql',
        onlyif   => 'for x in `php5query -S`; do if [ ! -f /etc/php5/$x/conf.d/20-mysql.ini ]; then echo "onlyif"; fi; done | grep onlyif',
        require  => File['/etc/php5/mods-available/mysql.ini'],
        notify   => Exec['php::restart'],
    }

}
