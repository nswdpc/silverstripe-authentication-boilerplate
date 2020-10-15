# Silverstripe Authentication Boilerplate

This module provides a standard set of rules for defining access to Silverstripe sites:

- password validation configuration per NIST standards
- password handling and management
- password checking via pwnedpasswords API
- multi-factor authentication setup (MFA)
- security extensions
- security reports
- pending profiles

> This module is under active development and should not be considered production-ready just yet
>
> We welcome testing and feedback via the Github issue tracker

## Requirements

+ silverstripe/totp-authenticator - for MFA via a Time-based One-time Password
+ nswdpc/silverstripe-pwnage-hinter -  provides pwned password/breached account assistance
+ silverstripe/security-extensions - provides features including sudo mode, password change on next sign in
+ silverstripe/securityreport - "Users, Groups and Permissions" report in the administration area for Administrators
+ spomky-labs/otphp - TOTP base library

See [composer.json](./composer.json) for details

## Configuration

See [_config/config.yml](./_config/config.yml)

## License

[BSD-3-Clause](./LICENSE.md)

## Documentation

* [Documentation](./docs/en/001_index.md)

## Maintainers

+ [dpcdigital@NSWDPC:~$](https://dpc.nsw.gov.au)

## Bugtracker

We welcome bug reports, pull requests and feature requests on the Github Issue tracker for this project.

Please review the [code of conduct](./code-of-conduct.md) prior to opening a new issue.

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.

Please review the [code of conduct](./code-of-conduct.md) prior to completing a pull request.
