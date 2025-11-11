This folder contains some rules for [PHP CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer), overriden to comply with recent PHP and IDEs new features.  

To use this folder with PHPCS, you'll need to execute this:
```sh
./vendor/bin/phpcs --config-set installed_paths phpcs/LsaStandard
```