-- example psql database for leavensky project

DROP DATABASE leavensky;
CREATE DATABASE leavensky;
\c leavensky;

CREATE OR REPLACE FUNCTION update_modified_column()	
RETURNS TRIGGER AS $$
BEGIN
    NEW.modified = now();
    RETURN NEW;	
END;
$$ language 'plpgsql';

CREATE TABLE leavensky (
	id serial PRIMARY KEY, 
	leave_user int,
    leave_type text,
	leave_day date,
	creator int,
	modified timestamp default current_timestamp
);

CREATE TRIGGER update_modified BEFORE UPDATE ON leavensky FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
