# mailgun-tools
Add Mailgun tools to Artisan

## Installation

Install using composer

```sh
composer require springfieldclinic/mailgun-tools
```

Publish config file

```sh
php artisan vendor:publish --provider="SpringfieldClinic\MailgunTools\MailgunToolsServiceProvider"
```

Add the necessary environment variables to your `.env` that are mentioned in `config/mailgun-tools.php`

Done!