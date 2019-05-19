class php::uploadprogress {

    if !defined(Package['php-pear']) { package { 'php-pear': } }
    if !defined(Package['php5-dev']) { package { 'php5-dev': } }

    exec { 'php::uploadprogress':
        command => 'pecl install uploadprogress',
        require => Package['php-pear', 'php5-dev'],
        unless  => 'pecl list uploadprogress',
    }

    file { '/etc/php5/mods-available/uploadprogress.ini':
        content => template('php/etc/php5/mods-available/uploadprogress.ini.erb'),
        require => Exec['php::uploadprogress'],
        notify  => Exec['php::restart'],
    }

    # Enable uploadprogress for all SAPIs except cli
    exec { 'php::uploadprogress:enable':
        provider => 'shell',
        command  => 'for sapi in `php5query -S | grep -v ^cli$`; do php5enmod -s $sapi uploadprogress; done',
        onlyif   => 'for x in `php5query -S | grep -v ^cli$`; do if [ ! -f /etc/php5/$x/conf.d/20-uploadprogress.ini ]; then echo "onlyif"; fi; done | grep onlyif',
        require  => File['/etc/php5/mods-available/uploadprogress.ini'],
        notify   => Exec['php::restart'],
    }

    # Make sure uploadprogress is not enabled for php-cli
    exec { 'php::uploadprogress::disable_cli':
        command => 'php5dismod -s cli uploadprogress',
        onlyif  => 'test -f /etc/php5/cli/conf.d/20-uploadprogress.ini',
        require => Exec['php::uploadprogress:enable'],
    }

}
