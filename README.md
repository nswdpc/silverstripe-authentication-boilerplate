# Silverstripe Authentication Boilerplate

This module provides a standard set of rules for defining access to Silverstripe sites:

- password validation configuration per NIST standards
- password handling and management
- password checking via pwnedpasswords API
- multi-factor authentication setup (MFA)
- security reports
- pending profiles

See [composer.json](./composer.json) for details

## Configuration

See [_config/config.yml](./_config/config.yml)

More: [Multi Factor Authentication](./docs/003_mfa.md)

## Good-to-know

### Password validator

Setting a PasswordValidator in project configuration will replace the password validator provided by this module:
```php
$validator = \My\Own\PasswordValidator::create();
\SilverStripe\Security\Member::set_password_validator($validator);
```

## License

[BSD-3-Clause](./LICENSE.md)

## Documentation

* [Documentation](./docs/en/001_index.md)

## Maintainers

PD web team

## Bugtracker

We welcome bug reports, pull requests and feature requests on the Github Issue tracker for this project.

Please review the [code of conduct](./code-of-conduct.md) prior to opening a new issue.

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.

Please review the [code of conduct](./code-of-conduct.md) prior to completing a pull request.
