Web application installation guide with docker:
----

Pull and run docker image. 

- `docker build -t pesio/rsabias .`
- `docker run --name rsabias -dit -p 80:80 pesio/rsabias`

For obtaining CMoCL API key, run:
- `docker exec -i -t rsabias /cmocl-key`


Web application manual installation guide:
----

- install dependencies with composer: `composer install`
- configure a database connection in file `app/config/config.local.neon`
- run initialization of a database `composer database-init`
- run initialization of CMoCL `composer cmocl-init`

Requirements:
----

- php >= 5.6
- php extensions
  - bcmath
  - openssl
  
Used libraries:
----

- Nette 2.4
- Bootstrap 4.1
- jQuery 3.3
- Kdyby Console 2.7
- phpseclib 1.0.11 (http://phpseclib.sourceforge.net/)
- OpenPGP 0.0.1 (http://github.com/bendiken/openpgp-php)

Command line application usage:
----

Run `php cli.php` and it downloads all dependencies and shows you all possible commands

- `php cli.php cli:groups` shows all groups from classification table
- `php cli.php cli:classify file` classify keys (and urls) from a file `file`
