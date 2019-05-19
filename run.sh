#!/bin/sh

chown -R apache:apache /var/www/localhost

httpd -D FOREGROUND &

cat << EOF > /cmocl-key
#!/bin/sh
php /var/www/localhost/cli.php cmocl:key
EOF
chmod u+x /cmocl-key

if [ ! -d "/run/mysqld" ]; then
	mkdir -p /run/mysqld
	chown -R mysql:mysql /run/mysqld
fi

if [ -d /var/lib/mysql/mysql ]; then
	echo '[i] MySQL directory already present, skipping creation'

	echo "Starting all process"
    exec /usr/bin/mysqld --user=mysql --console &
else
	echo "[i] MySQL data directory not found, creating initial DBs"

	chown -R mysql:mysql /var/lib/mysql

	# init database
	echo 'Initializing database'
	mysql_install_db --user=mysql --datadir=${MYSQL_DATAPATH} > /dev/null
	echo 'Database initialized'

	echo "[i] MySql root password: $MYSQL_PASSWORD"

	# create temp file
	tfile=`mktemp`
	if [ ! -f "$tfile" ]; then
	    return 1
	fi

	# save sql
	echo "[i] Create temp file: $tfile"
	cat << EOF > $tfile
USE mysql;
FLUSH PRIVILEGES;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED BY '$MYSQL_PASSWORD' WITH GRANT OPTION;
EOF


	# Create new database
	if [ "$MYSQL_DATABASE" != "" ]; then
		echo "[i] Creating database: $MYSQL_DATABASE"
		echo "CREATE DATABASE IF NOT EXISTS \`$MYSQL_DATABASE\` CHARACTER SET utf8 COLLATE utf8_general_ci;" >> $tfile
	fi

	echo 'FLUSH PRIVILEGES;' >> $tfile

	# run sql in tempfile
	echo "[i] run tempfile: $tfile"
	/usr/bin/mysqld --user=mysql --bootstrap --verbose=0 < $tfile
	rm -f $tfile

	echo "Starting all process"
    exec /usr/bin/mysqld --user=mysql --console &

    echo "[i] Sleeping 5 sec"
    sleep 5

    cd /var/www/localhost
    composer database-init
    composer cmocl-init
    cd /
fi


#Docker stopper
tail -f /etc/issue
