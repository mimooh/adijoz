#!/bin/bash

LEAVENSKY_DB_USER='leavensky'  
LEAVENSKY_DB_PASS='secret'  
LEAVENSKY_SESSION_NAME='sgsp'
LEAVENSKY_LANG="pl"	

# End of configuration. Run this shell script to setup postgres for leavensky project. Then restart apache.

# init 
USER=`id -ru`
[ "X$USER" == "X0" ] && { echo "Don't run as root / sudo"; exit; }

[ $LEAVENSKY_DB_PASS == 'secret1' ] && {  # TODO
	echo "Password for leavensky user needs to be changed from the default='secret'."; 
	echo
	exit;
} 

# www-data user needs LEAVENSKY_DB vars. They are kept in www-data environment: /etc/apache2/envvars #{{{
temp=`mktemp`
sudo cat /etc/apache2/envvars | grep -v LEAVENSKY_  > $temp
echo "export LEAVENSKY_DB_USER='$LEAVENSKY_DB_USER'" >> $temp
echo "export LEAVENSKY_DB_PASS='$LEAVENSKY_DB_PASS'" >> $temp
echo "export LEAVENSKY_SESSION_NAME='$LEAVENSKY_SESSION_NAME'" >> $temp
sudo cp $temp /etc/apache2/envvars
rm $temp

[ "X$1" == "Xclear" ] && { 
	echo "sudo -u postgres psql -c \"DROP DATABASE leavensky\"";
	echo "sudo -u postgres psql -c \"DROP USER $LEAVENSKY_DB_USER\"";
	echo "enter or ctrl+c";
	read;
	sudo -u postgres psql -c "DROP DATABASE leavensky";
	sudo -u postgres psql -c "DROP USER $LEAVENSKY_DB_USER";
}

sudo -u postgres psql -lqt | cut -d \| -f 1 | grep -qw 'leavensky' && { 
	echo ""
	echo "leavensky already exists in psql. You may wish to call";
	echo "DROP DATABASE leavensky; DROP USER $LEAVENSKY_DB_USER" 
	echo "by running:"
	echo ""
	echo "	bash install.sh clear";
	echo ""
	exit
}


#}}}
# psql#{{{
sudo -u postgres psql << EOF
CREATE DATABASE leavensky;
CREATE USER $LEAVENSKY_DB_USER WITH PASSWORD '$LEAVENSKY_DB_PASS';

\c leavensky;

CREATE TABLE people(
	id serial PRIMARY KEY,
	name text
);

INSERT INTO people(name) values('Lannister Jaimie');
INSERT INTO people(name) values('Lannister Tyrion');
INSERT INTO people(name) values('Lannister Cersei');

-- Instead of using fake Lannisters above, you can connect to another DB containing real people by dblink:
-- CREATE EXTENSION dblink;
-- CREATE VIEW people as SELECT id, name FROM dblink('dbname= host= user= password=', 'SELECT id, name FROM workers') as foo (id integer, name text);

CREATE OR REPLACE FUNCTION update_modified_column()	
RETURNS TRIGGER AS \$\$
BEGIN
    NEW.modified = now();
    RETURN NEW;	
END;
\$\$ language 'plpgsql';

CREATE TABLE leavensky (
	id serial PRIMARY KEY, 
	year int,
	user_id int,
	creator_id int,
	modified timestamp default current_timestamp,
	taken text,
	leaves text,
	block text,
	limits text
);

CREATE VIEW v as SELECT people.name, people.id as user_id, leavensky.year, leavensky.creator_id, leavensky.leaves,leavensky.limits, leavensky.taken, leavensky.block FROM people LEFT JOIN leavensky ON (people.id=leavensky.user_id);

CREATE TRIGGER update_modified BEFORE UPDATE ON leavensky FOR EACH ROW EXECUTE PROCEDURE update_modified_column();

ALTER DATABASE leavensky OWNER TO $LEAVENSKY_DB_USER;
ALTER TABLE leavensky OWNER TO $LEAVENSKY_DB_USER;

GRANT ALL PRIVILEGES ON DATABASE leavensky TO $LEAVENSKY_DB_USER;
GRANT ALL ON ALL TABLES IN SCHEMA public TO $LEAVENSKY_DB_USER;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO $LEAVENSKY_DB_USER;


EOF
echo;
#}}}
# final#{{{
echo;
echo "Restarting apache..."
sudo service apache2 restart
#}}}
