# laravel-skebby

Package for using Skebby SMS gateway with Laravel projects.

### Requirements

- php >= 7.0
- php curl extension

### Installation

you can install this package using the following composer command

`composer require alagaccia/skebby`


### How to use

1. Create the following ENV constants:

    SKEBBY_USER="your Skebby username"

    SKEBBY_PWD="your Skebby password"

    SKEBBY_ALIAS="your Skebby alias"

2. Copy `use alagaccia\skebby\Skebby;` in your controller file

3. Create a new instance `$skebby = new Skebby;`

4. Send your SMS

       $skebby->send('phone-number', 'text-message');
