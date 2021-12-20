# Symfony Survival Kit

This bundle has been created to share all the tools and code for Symfony microservices.

It provides:
- A common way to do things
- Provides a toolbox

So there are a few rules:
- No functional feature or code (no resource model)! Only technical code that can be widely reused
- Tools provided must be independent as much as possible
- Limit the number of external dependencies
- Keep compatibility as much as possible and plan smooth migrations
- Services should not use autowiring or autoconfiguration. Instead, all services should be defined explicitly
- Services not meant to be used by the microservice directly, should be defined as private

## This bundle provides the following:
### Basic tools
- logger tool
- http client tool

## Installation

- add `symfony-survival-kit` repository in composer.json
```    
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/proleadin/symfony-survival-kit"
        }
    ]
```
- use composer to install `$ composer require leadin/symfony-survival-kit`

## Development
### Versioning
- Bundle must be versioned following the [Semantic Versioning Standard](https://semver.org/) X.Y.Z (MAJOR.MINOR.PATCH, e.g. 1.0.0)
- Once a versioned bundle has been released, the contents of that version MUST NOT be modified. Any modifications MUST be released as a new version
- To release create new github tag and publish release with it

### New functionality or improvements
- Bundle can be tested only through existing Symfony microservice
- For the tests purpose of new development You can switch locally any microservice to use the bundle dev branch instead of the release. In composer.json only, you should prefix your custom branch name with `dev-`, and replace the current release version
```
    "require": {
        "leadin/symfony-survival-kit": "dev-custom_branch"
    }
```
- When you run `$ composer update leadin/symfony-survival-kit`, you will get your modified version of the bundle instead of the one from the release
