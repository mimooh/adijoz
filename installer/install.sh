#!/bin/bash

LEAVENSKY_DB_USER='leavensky'  
LEAVENSKY_DB_PASS='secret'  

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
sudo cat /etc/apache2/envvars | grep -v LEAVENSKY_DB_USER | grep -v LEAVENSKY_DB_PASS > $temp
echo "export LEAVENSKY_DB_USER='$LEAVENSKY_DB_USER'" >> $temp
echo "export LEAVENSKY_DB_PASS='$LEAVENSKY_DB_PASS'" >> $temp
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
	leaves text
);

CREATE TABLE summary(
	id serial PRIMARY KEY, 
	year int,
	user_id int,
	creator_id int,
	modified timestamp default current_timestamp,
	taken text,
	limits text
);
INSERT INTO leavensky(year , user_id , creator_id , leaves) values(2018  , 1 , 666 , '[["2018-01-05","zal"] ,["2018-01-06","zal"]]');
INSERT INTO leavensky(year , user_id , creator_id , leaves) values(2018  , 2 , 666 , '[["2018-02-05","zal"] ,["2018-02-06","zal"]]');

INSERT INTO summary(year , user_id , creator_id , limits) values(2018 , 1 , 666 , '{"zal":3 , "wyp":3 , "dod":3 , "sl":3 , "dwps":3 , "zl":1}');
INSERT INTO summary(year , user_id , creator_id , limits) values(2018 , 2 , 666 , '{"zal":4 , "wyp":4 , "dod":4 , "sl":4 , "dwps":4 , "zl":1}');

CREATE TRIGGER update_modified BEFORE UPDATE ON leavensky FOR EACH ROW EXECUTE PROCEDURE update_modified_column();


ALTER TABLE leavensky OWNER TO $LEAVENSKY_DB_USER;
GRANT ALL ON TABLE leavensky TO $LEAVENSKY_DB_USER;
GRANT ALL ON SEQUENCE leavensky_id_seq TO $LEAVENSKY_DB_USER;

ALTER TABLE summary OWNER TO $LEAVENSKY_DB_USER;
GRANT ALL ON TABLE summary TO $LEAVENSKY_DB_USER;
GRANT ALL ON SEQUENCE summary_id_seq TO $LEAVENSKY_DB_USER;

ALTER DATABASE leavensky OWNER TO $LEAVENSKY_DB_USER;
GRANT ALL PRIVILEGES ON DATABASE leavensky TO $LEAVENSKY_DB_USER;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO $LEAVENSKY_DB_USER;
GRANT ALL PRIVILEGES  ON ALL SEQUENCES IN SCHEMA public TO $LEAVENSKY_DB_USER;


EOF
echo;
#}}}
# final#{{{
echo;
echo "Restarting apache..."
sudo service apache2 restart
#}}}
