class php::xmlrpc {

    if !defined(Package['php5-xmlrpc']) { package { 'php5-xmlrpc': require => Package['php5-cli'] } }

    file { '/etc/php5/mods-available/xmlrpc.ini':
        content => template('php/etc/php5/mods-available/xmlrpc.ini.erb'),
        require => Package['php5-xmlrpc'],
        notify  => Exec['php::restart'],
    }

    exec { 'php::xmlrpc::enable':
        provider => 'shell',
        command  => 'php5enmod -s ALL xmlrpc',
        onlyif   => 'for x in `php5query -S`; do if [ ! -f /etc/php5/$x/conf.d/20-xmlrpc.ini ]; then echo "onlyif"; fi; done | grep onlyif',
        require  => File['/etc/php5/mods-available/xmlrpc.ini'],
        notify   => Exec['php::restart'],
    }

}
