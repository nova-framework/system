![Nova Framework](https://novaframework.com/templates/nova4/assets/img/nova.png)

# Nova Framework 4.0 (Kernel)

[![Total Downloads](https://img.shields.io/packagist/dt/nova-framework/system.svg)](https://packagist.org/packages/nova-framework/system)
[![Dependency Status](https://www.versioneye.com/user/projects/554367f738331321e2000005/badge.svg)](https://www.versioneye.com/user/projects/554367f738331321e2000005)
[![GitHub license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/nova-framework/framework/blob/master/LICENSE.txt)
[![GitHub stars](https://img.shields.io/github/stars/nova-framework/system.svg)](https://github.com/nova-framework/system/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/nova-framework/system.svg)](https://github.com/nova-framework/system)

[![Join the chat at https://gitter.im/nova-framework/framework/novausers](https://img.shields.io/gitter/room/nwjs/nw.js.svg)](https://gitter.im/nova-framework/framework/novausers?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

> **Note:** This repository contains the core code of the Nova framework. If you want to build an application using Nova 4, visit the main [Nova repository](https://github.com/nova-framework/framework).

## What is the Nova Framework?

Nova Framework is a PHP 5.6 MVC system. It's designed to be lightweight and modular, allowing developers to build better and easy to maintain code with PHP.

## Requirements

**The framework requirements are limited.**

- PHP 5.5 or greater.
- Apache Web Server or equivalent with mod rewrite support.
- IIS with URL Rewrite module installed - [http://www.iis.net/downloads/microsoft/url-rewrite](http://www.iis.net/downloads/microsoft/url-rewrite)

**The following PHP extensions should be enabled:**

- Fileinfo (edit php.ini and uncomment php_fileinfo.dll or use php selector within cpanel if available.)
- OpenSSL
- INTL

> **Note:** Although a database is not required, if a database is to be used, the system is designed to work with a MySQL database using PDO.

## Installation

This framework was designed and is **strongly recommended** to be installed above the document root directory, with it pointing to the `public` folder.

Additionally, installing in a sub-directory, on a production server, will introduce severe security issues.

#### Recommended
The framework is located on [Packagist](https://packagist.org/packages/nova-framework/framework).

You can install the framework from a terminal by using:

```
composer create-project nova-framework/framework foldername 4.* -s dev
```

The foldername is the desired folder to be created.

## Documentation

Full docs & tutorials are available on [novaframework.com](http://novaframework.com).

Offline docs are available in PDF, EPUB and MOBI formats on [Leanpub](https://leanpub.com/novaframeworkmanual22).

Screencasts are available on [Novacasts](http://novacasts.com).

## Contributing

#### Issue Tracker

You can find outstanding issues on the [GitHub Issue Tracker](https://github.com/nova-framework/system/issues).

#### Pull Requests

* Each pull request should contain only one new feature or improvement.
* Pull requests should be submitted to the correct version branch ie [master](https://github.com/nova-framework/system/tree/master)

#### Code Style

All pull requests must use the PSR-2 code style.

* Code MUST use the PSR-1 code style.
* Code MUST use 4 spaces for indenting, not tabs.
* There MUST NOT be a hard limit on line length; the soft limit MUST be 120 characters; lines SHOULD be 80 characters or less.
* There MUST be one blank line after the namespace declaration, and there MUST be one blank line after the block of use declarations.
* Opening braces for classes MUST go on the next line, and closing braces MUST go on the next line after the body.
* Opening braces for methods MUST go on the next line, and closing braces MUST go on the next line after the body.
* Visibility MUST be declared on all properties and methods; abstract and final MUST be declared before the visibility; static MUST be declared after the visibility.
* Control structure keywords MUST have one space after them; method and function calls MUST NOT.
* Opening braces for control structures MUST go on the same line, and closing braces MUST go on the next line after the body.
* Opening parentheses for control structures MUST NOT have a space after them, and closing parentheses for control structures MUST NOT have a space before.

## License

The Nova Framework is under the MIT License.
