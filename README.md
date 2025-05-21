
# Drupal 11 Boilerplate

Startin point for Drupal for interns.


## Installation

Install my-project with npm

```bash
  git clone git@github.com:slobodan-prodanovic-etondigital/drupal-11-boilerplate.git
  cd drupal-11-boilerplate
  fin init
```
- Copy .docksal/misc/services.local.yml to web/sites/default/services.local.yml
- Copy .docksal/misc/settings.local.yml to web/sites/default/settings.local.yml

 ```bash
fin db import .dokcksal/misc/init_db.sql
fin composer install
```

Website link: http://drupal11.docksal.site/
PHPMyAdmin: http://pma.drupal11.docksal.site/
MailHog: http://mail.drupal11.docksal.site/

Access Data:
Url: http://drupal11.docksal.site/user/login
Username: admin
Password: xx
