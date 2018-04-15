#!/bin/bash

DB_USER='leavensky'  
DB_PASS='secret'  

# End of configuration. Run this shell script to setup postgres for leavensky project.

# init #{{{
USER=`id -ru`
[ "X$USER" == "X0" ] && { echo "Don't run as root / sudo"; exit; }

[ $DB_PASS == 'secret' ] && { 
	echo "Password for leavensky user needs to be changed from the default='secret'."; 
	echo
	exit;
} 
#}}}
# www-data user needs LEAVENSKY_DB vars. They are kept in www-data environment: /etc/apache2/envvars #{{{
temp=`mktemp`
sudo cat /etc/apache2/envvars | grep -v LEAVENSKY_DB_USER | grep -v LEAVENSKY_DB_PASS > $temp
echo "export LEAVENSKY_DB_USER='$DB_USER'" >> $temp
echo "export LEAVENSKY_DB_PASS='$DB_PASS'" >> $temp
sudo cp $temp /etc/apache2/envvars
rm $temp

sudo -u postgres psql -lqt | cut -d \| -f 1 | grep -qw 'leavensky' && { 
	echo "Leavensky already exists in psql. You may wish to clear psql from leavensky by invoking";
	echo 'sudo -u postgres psql -c "DROP DATABASE leavensky;"' 
	echo 'sudo -u postgres psql -c "DROP USER leavensky;"' 
	echo
	exit;
}

#}}}
# psql#{{{
sudo -u postgres psql << EOF
CREATE DATABASE leavensky;
CREATE USER $DB_USER WITH PASSWORD '$DB_PASS';

\c leavensky;

CREATE OR REPLACE FUNCTION update_modified_column()	
RETURNS TRIGGER AS \$\$
BEGIN
    NEW.modified = now();
    RETURN NEW;	
END;
\$\$ language 'plpgsql';

CREATE TABLE leavensky (
	id serial PRIMARY KEY, 
	leave_user int,
    leave_type text,
	leave_day date,
	creator int,
	modified timestamp default current_timestamp
);

CREATE TRIGGER update_modified BEFORE UPDATE ON leavensky FOR EACH ROW EXECUTE PROCEDURE update_modified_column();

ALTER TABLE leavensky OWNER TO $DB_USER;
GRANT ALL ON TABLE leavensky TO $DB_USER;
GRANT ALL ON SEQUENCE leavensky_id_seq TO $DB_USER;


EOF
echo;
#}}}
