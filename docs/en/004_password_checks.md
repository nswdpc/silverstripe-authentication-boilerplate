# Passwords

The following validation and verification checks and configuration settings are provided by this module

## Validation

The module provides a ```PasswordValidator``` using [NIST Special Publication 800-63B / 5.1.1.1 Memorized Secret Authenticators](https://pages.nist.gov/800-63-3/sp800-63b.html#5111-memorized-secret-authenticators)

+ The subscriber can choose a memorised secret of 8 characters minimum length
+ No other complexity requirements are imposed
+ No historical count of password use is imposed
+ No password hints are required

## Verification

> PasswordRuleCheck

Sets the password verification routine based on [NIST Special Publication 800-63B / 5.1.1.2 Memorized Secret Verifiers](https://pages.nist.gov/800-63-3/sp800-63b.html#-5112-memorized-secret-verifiers)

+ Dictionary word
+ Contextual words
+ Repetitive characters
+ Sequential characters

### Dictionary word

> DictionaryWordRule

You must have the `enchant` PHP extension installed to check against a list of known dictionary words.

The `locale` configuration value allows for different locales (default: en_AU)

### Context specific words

> ContextualWordRule

The ContextualWordRule class provides for checking of passwords against strings specific to the project, by default it checks within a set of default values from SiteConfig and Member.

You can provide an additional set of context strings by using the `context_strings` configuration

### Repetitive characters

> RepetitiveCharacterRule

This aims to block use of 3 or more repetitive characters in the password (e.g aaa, 99999);

### Sequential Characters

> SequentialCharacterRule

This aims to block use of  3 or more sequential characters in  password based on configured alphabets (e.g abc, 1234).


## Rate limiting

Core Silverstripe configuration sets the amount of failed attempts before the account is locked based on [NIST Special Publication 800-63B / 5.2.2 Rate Limiting (Throttling)](https://pages.nist.gov/800-63-3/sp800-63b.html#throttle)

This module ships with the following value:

```yaml
SilverStripe\Security\Member:
  lock_out_after_incorrect_logins: 20
```

See `_config/config.yml` for more
