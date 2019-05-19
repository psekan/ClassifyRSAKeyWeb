<?php
@mkdir(__DIR__ . '/www/css/');
@mkdir(__DIR__ . '/www/js/');
copy(__DIR__ . '/vendor/twbs/bootstrap/dist/css/bootstrap.css', 'www/css/bootstrap.css');
copy(__DIR__ . '/vendor/twbs/bootstrap/dist/css/bootstrap-reboot.css', 'www/css/bootstrap-reboot.css');
copy(__DIR__ . '/vendor/twbs/bootstrap/dist/js/bootstrap.js', 'www/js/bootstrap.js');
copy(__DIR__ . '/vendor/components/jquery/jquery.min.js', 'www/js/jquery.js');
