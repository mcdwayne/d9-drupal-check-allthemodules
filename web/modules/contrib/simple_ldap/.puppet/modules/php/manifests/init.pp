class php {

    if !defined(Package['php5-cli']) { package { 'php5-cli': } }
    if !defined(Package['php-pear']) { package { 'php-pear': } }

    # Changes to PHP configs will notify this Exec. Any other classes can then
    # subscribe to this in order restart their appropriate services.
    exec { 'php::restart':
        command     => '/bin/true',
        refreshonly => true,
    }

}
