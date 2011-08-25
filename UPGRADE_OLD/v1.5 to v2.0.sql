CREATE TABLE student_field_categories (
    id numeric primary key,
    title character varying(100)
);

CREATE SEQUENCE STUDENT_FIELD_CATEGORIES_SEQ START 4 INCREMENT 1;

ALTER TABLE CUSTOM_FIELDS ADD COLUMN CATEGORY_ID NUMERIC;
CREATE INDEX CUSTOM_FIELDS_IND3 ON CUSTOM_FIELDS (CATEGORY_ID);

CREATE INDEX stu_addr_meets_1 ON students_join_address USING btree (student_id);
alter table stu_addr_meets_1 rename to students_join_address_ind1;
CREATE INDEX relations_meets_1 ON students_join_people USING btree (student_id);
alter table relations_meets_1 rename to students_join_people_ind1;
create index student_enrollment_7 on student_enrollment (school_id);

alter table custom_fields add column system_field char(1);

UPDATE CUSTOM_FIELDS SET SELECT_OPTIONS = replace(SELECT_OPTIONS,',','\n');

INSERT INTO STUDENT_FIELD_CATEGORIES (ID,TITLE) values('1','General');
INSERT INTO STUDENT_FIELD_CATEGORIES (ID,TITLE) values('2','Medical');
INSERT INTO STUDENT_FIELD_CATEGORIES (ID,TITLE) values('3','Address');
update custom_fields set category_id = '1';

INSERT INTO CUSTOM_FIELDS (ID,TYPE,CATEGORY_ID,SYSTEM_FIELD,TITLE,SELECT_OPTIONS) values('200000000','select','1','Y','Gender','Male\rFemale');
ALTER TABLE STUDENTS ADD COLUMN CUSTOM_200000000 VARCHAR(255);
UPDATE STUDENTS SET CUSTOM_200000000=GENDER;
UPDATE STUDENTS SET CUSTOM_200000000='Male' WHERE CUSTOM_200000000='M';
UPDATE STUDENTS SET CUSTOM_200000000='Female' WHERE CUSTOM_200000000='F';

INSERT INTO CUSTOM_FIELDS (ID,TYPE,CATEGORY_ID,SYSTEM_FIELD,TITLE,SELECT_OPTIONS) values('200000001','select','1','Y','Ethnicity','White, Non-Hispanic\rBlack, Non-Hispanic\rAmer. Indian or Alaskan Native\rAsian or Pacific Islander\rHispanic\rOther');
ALTER TABLE STUDENTS ADD COLUMN CUSTOM_200000001 VARCHAR(255);
UPDATE STUDENTS SET CUSTOM_200000001=ETHNICITY;

INSERT INTO CUSTOM_FIELDS (ID,TYPE,CATEGORY_ID,SYSTEM_FIELD,TITLE,SELECT_OPTIONS) values('200000002','text','1','Y','Nickname',NULL);
ALTER TABLE STUDENTS ADD COLUMN CUSTOM_200000002 VARCHAR(255);
UPDATE STUDENTS SET CUSTOM_200000002=NICKNAME;

INSERT INTO CUSTOM_FIELDS (ID,TYPE,CATEGORY_ID,SYSTEM_FIELD,TITLE,SELECT_OPTIONS) values('200000003','text','1','Y','Social Security',NULL);
ALTER TABLE STUDENTS ADD COLUMN CUSTOM_200000003 VARCHAR(255);
UPDATE STUDENTS SET CUSTOM_200000003=SOC_SEC_NO;

INSERT INTO CUSTOM_FIELDS (ID,TYPE,CATEGORY_ID,SYSTEM_FIELD,TITLE,SELECT_OPTIONS) values('200000004','date','1','Y','Birthdate',NULL);
ALTER TABLE STUDENTS ADD COLUMN CUSTOM_200000004 VARCHAR(255);
UPDATE STUDENTS SET CUSTOM_200000004=BIRTH_DATE;

INSERT INTO CUSTOM_FIELDS (ID,TYPE,CATEGORY_ID,SYSTEM_FIELD,TITLE,SELECT_OPTIONS) values('200000005','select','1','Y','Language','English\rSpanish');
ALTER TABLE STUDENTS ADD COLUMN CUSTOM_200000005 VARCHAR(255);
UPDATE STUDENTS SET CUSTOM_200000005=LANGUAGE;

INSERT INTO CUSTOM_FIELDS (ID,TYPE,CATEGORY_ID,SYSTEM_FIELD,TITLE) values('200000006','text','2','Y','Physician');
ALTER TABLE STUDENTS ADD COLUMN CUSTOM_200000006 VARCHAR(255);
UPDATE STUDENTS SET CUSTOM_200000006=PHYSICIAN;

INSERT INTO CUSTOM_FIELDS (ID,TYPE,CATEGORY_ID,SYSTEM_FIELD,TITLE) values('200000007','text','2','Y','Physician Phone');
ALTER TABLE STUDENTS ADD COLUMN CUSTOM_200000007 VARCHAR(255);
UPDATE STUDENTS SET CUSTOM_200000007=PHYSICIAN_PHONE;

INSERT INTO CUSTOM_FIELDS (ID,TYPE,CATEGORY_ID,SYSTEM_FIELD,TITLE) values('200000008','text','2','Y','Preferred Hospital');
ALTER TABLE STUDENTS ADD COLUMN CUSTOM_200000008 VARCHAR(255);
UPDATE STUDENTS SET CUSTOM_200000008=HOSPITAL;

INSERT INTO CUSTOM_FIELDS (ID,TYPE,CATEGORY_ID,SYSTEM_FIELD,TITLE) values('200000009','textarea','2','Y','Comments');
ALTER TABLE STUDENTS ADD COLUMN CUSTOM_200000009 VARCHAR(2052);
UPDATE STUDENTS SET CUSTOM_200000009=MEDICAL_COMMENTS;

INSERT INTO CUSTOM_FIELDS (ID,TYPE,CATEGORY_ID,SYSTEM_FIELD,TITLE) values('200000010','radio','2','Y','Has Doctor''s Note');
ALTER TABLE STUDENTS ADD COLUMN CUSTOM_200000010 CHAR(1);
UPDATE STUDENTS SET CUSTOM_200000010=DOCTORS_NOTE;

INSERT INTO CUSTOM_FIELDS (ID,TYPE,CATEGORY_ID,SYSTEM_FIELD,TITLE) values('200000011','textarea','2','Y','Doctor''s Note Comments');
ALTER TABLE STUDENTS ADD COLUMN CUSTOM_200000011 VARCHAR(2052);
UPDATE STUDENTS SET CUSTOM_200000011=DOCTORS_NOTE_COMMENTS;

alter table address add column address varchar(255);
update address set address=coalesce(house_no||' ','')||coalesce(FRACTION||' ','')||coalesce(LETTER||' ','')||coalesce(DIRECTION||' ','')||COALESCE(STREET,'')||COALESCE(' '||APT,'');
alter table address drop column street2;
alter table address drop column street3;
alter table address drop column unlisted;
alter table address drop column change_uid;
alter table address drop column change_date;
alter table address drop column mail_plus4;
update address set zipcode = zipcode||'-'||COALESCE(plus4,'');
alter table address add column mail_address varchar(255);

alter table address add column phone2 varchar(30);
update address set phone2 = (case WHEN area_code IS NULL THEN '' ELSE '('||area_code||') ' END ) || substr(phone,1,3)||'-'||substr(phone,4);
alter table address drop column area_code;
alter table address drop column phone;
alter table address rename phone2 to phone;

alter table students_join_address add column MAILING VARCHAR(1);
alter table students_join_address add column RESIDENCE VARCHAR(1);

ALTER TABLE STUDENTS_JOIN_PEOPLE ADD COLUMN STUDENT_RELATION VARCHAR(100);
UPDATE STUDENTS_JOIN_PEOPLE SET STUDENT_RELATION = (SELECT VALUE FROM PEOPLE_JOIN_CONTACTS pjc WHERE pjc.PERSON_ID=STUDENTS_JOIN_PEOPLE.PERSON_ID AND pjc.TITLE='Relation');
DELETE FROM PEOPLE_JOIN_CONTACTS WHERE TITLE='Relation';

alter table attendance_calendar drop constraint attendance_calendar_pkey; 
alter table attendance_calendar add primary key (syear,school_id,school_date);

alter table staff add column schools varchar(255);
update staff set schools = ','||school_id||',';
create index staff_ind3 on staff (schools);
alter table staff drop column school_id;

update student_enrollment set next_school=school_id where next_school IS NULL;

alter table course_periods add column parent_id numeric;
update course_periods set parent_id=course_period_id;

alter table course_periods add column days varchar(7);
update course_periods set days='MTWHF';

alter table school_periods add column start_time VARCHAR(5);
alter table school_periods add column end_time VARCHAR(5);
alter table school_periods add column block VARCHAR(10);
alter table schedule add column scheduler_lock varchar(1);
alter table gradebook_assignments add column description varchar(1000);
create index course_periods_ind5 on course_periods (parent_id);
alter table attendance_calendar add column block varchar(10);
alter table students_join_address add column bus_pickup varchar(1); 
alter table students_join_address add column bus_dropoff varchar(1);
alter table custom_fields add column default_selection varchar(255);