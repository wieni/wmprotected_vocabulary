wmprotected_vocabulary
======================

[![Latest Stable Version](https://poser.pugx.org/wieni/wmprotected_vocabulary/v/stable)](https://packagist.org/packages/wieni/wmprotected_vocabulary)
[![Total Downloads](https://poser.pugx.org/wieni/wmprotected_vocabulary/downloads)](https://packagist.org/packages/wieni/wmprotected_vocabulary)
[![License](https://poser.pugx.org/wieni/wmprotected_vocabulary/license)](https://packagist.org/packages/wieni/wmprotected_vocabulary)

> Adds the possibility to protect taxonomy terms from being deleted when they are being referenced in certain fields.

## Why?
- Deleting a taxonomy term when it is still being referenced on another
  entity can do a lot of damage if the underlying code doesn't handle
  this situation.
- This could be fixed by doing null checks in your code, but sometimes
  it makes more sense to just prevent the user from deleting those
  terms.

## Installation

This package requires PHP 7.1 and Drupal 8 or higher. It can be
installed using Composer:

```bash
 composer require wieni/wmprotected_vocabulary
```

## How does it work?
When trying to delete a protected taxonomy term with references, a
message is shown and the confirmation button will be disabled.

### Configuration
The functionality can be enabled on a per-vocabulary level by checking
the _Protect_ checkbox on the vocabulary edit page. The _Protect fields_
option can be used to configure which fields should be used when
counting the references of a taxonomy term.

## Maintainers
* [**Hans Langouche**](https://github.com/HnLn) - *Initial 
  work*

See also the list of
[contributors](https://github.com/wieni/wmmailable/contributors) who
participated in this project.

## Changelog
All notable changes to this project will be documented in the
[CHANGELOG](CHANGELOG.md) file.

## Security
If you discover any security-related issues, please email
[info@wieni.be](mailto:info@wieni.be) instead of using the issue
tracker.

## License
Distributed under the MIT License. See the [LICENSE](LICENSE.md) file
for more information.
