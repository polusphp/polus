# Polus

Polus is a micro framework built on top off [Relay](https://github.com/relayphp) 
and [Aura components](https://github.com/auraphp).

## Installing Polus

You will need [Composer](https://getcomposer.org) to install Polus.

Pick a project name, and use Composer to create it with Radar; here we create
one called `example-project`:

    composer create-project polus/project example-project

Confirm the installation by changing into the project directory and starting the
built-in PHP web server:

    cd example-project
    php -S localhost:8080 -t public/

You can then browse to <http://localhost:8080/> and see simple html output:

    Welcome to polus

    127.0.0.1
