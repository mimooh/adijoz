#!/bin/bash

DB_USER='leavensky'  
DB_PASS='secret'  

# End of configuration. Run this shell script to setup postgres for leavensky project. Then restart apache.

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
	year int,
	leave_user int,
    leave_type text,
	leave_day date,
	creator int,
	modified timestamp default current_timestamp
);

CREATE TABLE leavensky_summary(
	id serial PRIMARY KEY, 
	year int,
	leave_user int,
	creator int,
	taken text,
	limits text,
	modified timestamp default current_timestamp
);
INSERT INTO leavensky_summary(year,leave_user,creator,limits) values(2018,1,666,'{"zal":5,"wyp":7,"dod":3,"sl":2,"dwps":1,"zl":0}');
INSERT INTO leavensky_summary(year,leave_user,creator,limits) values(2018,2,666,'{"zal":15,"wyp":17,"dod":13,"sl":2,"dwps":1,"zl":0}');

CREATE TRIGGER update_modified BEFORE UPDATE ON leavensky FOR EACH ROW EXECUTE PROCEDURE update_modified_column();

ALTER TABLE leavensky OWNER TO $DB_USER;
GRANT ALL ON TABLE leavensky TO $DB_USER;
GRANT ALL ON SEQUENCE leavensky_id_seq TO $DB_USER;

ALTER TABLE leavensky_summary OWNER TO $DB_USER;
GRANT ALL ON TABLE leavensky_summary TO $DB_USER;
GRANT ALL ON SEQUENCE leavensky_summary_id_seq TO $DB_USER;


EOF
echo;
#}}}
