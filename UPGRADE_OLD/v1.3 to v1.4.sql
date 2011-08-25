INSERT INTO PROGRAM_USER_CONFIG (TITLE,VALUE,USERNAME,PROGRAM) 
SELECT 'THEME' AS TITLE,'Brushed-Steel' AS VALUE,USERNAME,'Preferences' AS PROGRAM FROM STAFF WHERE EXISTS
(
	SELECT '' FROM PROGRAM_USER_CONFIG WHERE PROGRAM='Preferences' AND USERNAME=staff.USERNAME
);
alter table schools add column zipcode2 varchar(10);
update schools set zipcode2 = zipcode;
alter table schools drop column zipcode;
alter table schools rename zipcode2 to zipcode;

alter table address add column zipcode2 varchar(10);
update address set zipcode2 = zipcode;
alter table address drop column zipcode;
alter table address rename zipcode2 to zipcode;

alter table staff add column current_school_id numeric;
update staff set current_school_id=school_id where profile='admin';
update staff set school_id=NULL WHERE profile='admin';

INSERT INTO PROGRAM_USER_CONFIG (TITLE,VALUE,USERNAME,PROGRAM) 
SELECT 'DELIMITER' AS TITLE,'Tab' AS VALUE,USERNAME,'Preferences' AS PROGRAM FROM STAFF WHERE EXISTS
(
	SELECT '' FROM PROGRAM_USER_CONFIG WHERE PROGRAM='Preferences' AND USERNAME=staff.USERNAME
);