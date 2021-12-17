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
