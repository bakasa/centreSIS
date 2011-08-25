-- remove columns deprecated in v2.9
ALTER TABLE course_periods DROP COLUMN does_grades;


-- this is a change needed for 2.9->2.9.1 but there was no upgrade script for 2.9.1 so attempt it here
-- first delete old permissions if they were already manually converted, then convert the remaining
DELETE FROM staff_exceptions WHERE modname='misc/Export.php' AND exists (SELECT '' FROM staff_exceptions s WHERE s.modname='Students/AdvancedReport.php' AND s.user_id=staff_exceptions.user_id);
DELETE FROM profile_exceptions WHERE modname='misc/Export.php' AND exists (SELECT '' FROM profile_exceptions p WHERE p.modname='Students/AdvancedReport.php' AND p.profile_id=profile_exceptions.profile_id);
UPDATE staff_exceptions SET modname='Students/AdvancedReport.php' WHERE modname='misc/Export.php';
UPDATE profile_exceptions SET modname='Students/AdvancedReport.php' WHERE modname='misc/Export.php';



-- add rollover_id needed to rollover report_card_grades
ALTER TABLE report_card_grade_scales ADD COLUMN rollover_id numeric;

-- add rollover_id so calendars can be rolled
ALTER TABLE attendance_calendars ADD COLUMN rollover_id numeric;

-- add last_school needed when re-rolling student enrollment
ALTER TABLE student_enrollment ADD COLUMN last_school numeric;


-- add include column to student field categories
ALTER TABLE student_field_categories ADD COLUMN include character varying(100);


-- bump categories to make room for new standard Comments tab
-- bump by first flipping negative then positive to avoid id conflicts
-- first make sure no illegal negative id's
DELETE FROM student_field_categories WHERE ID<0;
-- bump the id counter
SELECT nextval('STUDENT_FIELD_CATEGORIES_SEQ');
UPDATE student_field_categories SET id=-id WHERE id>3;
UPDATE student_field_categories SET id=1-id WHERE id<0;
-- bump field categories to keep properly associated
UPDATE custom_fields SET category_id=category_id+1 WHERE category_id>3;
-- create the new Comments category
INSERT INTO student_field_categories (id,title,sort_order,include) values(4,'Comments',NULL,NULL);
-- update the permissions and profiles for the menu changes
-- bump category_id of exosting permissions
UPDATE staff_exceptions SET modname=split_part(modname,'=',1)||'='||to_number(split_part(modname,'=',2),'0')+1 where split_part(modname,'=',1)='Students/Student.php&category_id' AND to_number(split_part(modname,'=',2),'0')>3;
UPDATE profile_exceptions SET modname=split_part(modname,'=',1)||'='||to_number(split_part(modname,'=',2),'0')+1 where split_part(modname,'=',1)='Students/Student.php&category_id' AND to_number(split_part(modname,'=',2),'0')>3;
-- convert old student comments permissions to new
INSERT INTO staff_exceptions (user_id,modname,can_use,can_edit) (SELECT DISTINCT ON (user_id) user_id,'Students/Student.php&category_id=4',can_use,can_use from staff_exceptions WHERE modname='Students/Student.php?include=Comments');
INSERT INTO profile_exceptions (profile_id,modname,can_use,can_edit) (SELECT DISTINCT ON (profile_id) profile_id,'Students/Student.php&category_id=4',can_use,can_use from profile_exceptions WHERE modname='Students/Student.php?include=Comments');
-- delete old student comments permissions
DELETE FROM staff_exceptions WHERE modname='Students/Student.php?include=Comments';
DELETE FROM profile_exceptions WHERE modname='Students/Student.php?include=Comments';
-- update for add user menu change
UPDATE staff_exceptions SET modname='Users/User.php&staff_id=new' WHERE modname='Users/User.php?staff_id=new';
UPDATE profile_exceptions SET modname='Users/User.php&staff_id=new' WHERE modname='Users/User.php?staff_id=new';


-- add database structures for address fields
CREATE TABLE address_field_categories (
    id numeric NOT NULL,
    title character varying(100),
    sort_order numeric,
    residence character(1),
    mailing character(1),
    bus character(1)
);

CREATE SEQUENCE address_field_categories_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE TABLE address_fields (
    id numeric NOT NULL,
    "type" character varying(10),
    search character varying(1),
    title character varying(30),
    sort_order numeric,
    select_options character varying(10000),
    category_id numeric,
    system_field character(1),
    required character varying(1),
    default_selection character varying(255)
);

--
-- TOC entry 49 (OID 11541153)
-- Name: address_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE address_fields_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE INDEX address_desc_ind ON address_fields USING btree (id);

CREATE INDEX address_desc_ind2 ON custom_fields USING btree ("type");

CREATE INDEX address_fields_ind3 ON custom_fields USING btree (category_id);

ALTER TABLE ONLY address_fields
    ADD CONSTRAINT address_fields_pkey PRIMARY KEY (id);

ALTER TABLE ONLY address_field_categories
    ADD CONSTRAINT address_field_categories_pkey PRIMARY KEY (id);

SELECT pg_catalog.setval('address_field_categories_seq', 1, false);

SELECT pg_catalog.setval('address_fields_seq', 1, true);


-- add database structures for people (contact) fields
CREATE TABLE people_field_categories (
    id numeric NOT NULL,
    title character varying(100),
    sort_order numeric,
    custody character(1),
    emergency character(1)
);

CREATE SEQUENCE people_field_categories_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE TABLE people_fields (
    id numeric NOT NULL,
    "type" character varying(10),
    search character varying(1),
    title character varying(30),
    sort_order numeric,
    select_options character varying(10000),
    category_id numeric,
    system_field character(1),
    required character varying(1),
    default_selection character varying(255)
);

--
-- TOC entry 49 (OID 11541153)
-- Name: people_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE people_fields_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE INDEX people_desc_ind ON people_fields USING btree (id);

CREATE INDEX people_desc_ind2 ON custom_fields USING btree ("type");

CREATE INDEX people_fields_ind3 ON custom_fields USING btree (category_id);

ALTER TABLE ONLY people_fields
    ADD CONSTRAINT people_fields_pkey PRIMARY KEY (id);

ALTER TABLE ONLY people_field_categories
    ADD CONSTRAINT people_field_categories_pkey PRIMARY KEY (id);

SELECT pg_catalog.setval('people_field_categories_seq', 1, false);

SELECT pg_catalog.setval('people_fields_seq', 1, true);


-- add database structures for staff (user) fields
CREATE TABLE staff_field_categories (
    id numeric NOT NULL,
    title character varying(100),
    sort_order numeric,
    include character varying(100),
    admin character(1),
    teacher character(1),
    parent character(1),
    none character(1)
);

CREATE SEQUENCE staff_field_categories_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE TABLE staff_fields (
    id numeric NOT NULL,
    "type" character varying(10),
    search character varying(1),
    title character varying(30),
    sort_order numeric,
    select_options character varying(10000),
    category_id numeric,
    system_field character(1),
    required character varying(1),
    default_selection character varying(255)
);

CREATE SEQUENCE staff_fields_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE INDEX staff_desc_ind1 ON staff_fields USING btree (id);

CREATE INDEX staff_desc_ind2 ON staff_fields USING btree ("type");

CREATE INDEX staff_fields_ind3 ON staff_fields USING btree (category_id);

ALTER TABLE ONLY staff_fields
    ADD CONSTRAINT staff_fields_pkey PRIMARY KEY (id);

ALTER TABLE ONLY staff_field_categories
    ADD CONSTRAINT staff_field_categories_pkey PRIMARY KEY (id);

SELECT pg_catalog.setval('staff_field_categories_seq', 3, false);

SELECT pg_catalog.setval('staff_fields_seq', 1, true);

-- create the standard user field categories
INSERT INTO staff_field_categories (id,title,sort_order,include,admin,teacher,parent,none) VALUES (1, 'General Info', 1, NULL, 'Y', 'Y', 'Y', 'Y');
INSERT INTO staff_field_categories (id,title,sort_order,include,admin,teacher,parent,none) VALUES (2, 'Schedule', 2, NULL, NULL, 'Y', NULL, NULL);

-- add Genreral Info category to users with permission to Users screen
INSERT INTO staff_exceptions (user_id,modname,can_use,can_edit) (SELECT DISTINCT ON (user_id) user_id,'Users/User.php&category_id=1',can_use,can_edit FROM staff_exceptions WHERE modname='Users/User.php');
INSERT INTO profile_exceptions (profile_id,modname,can_use,can_edit) (SELECT DISTINCT ON (profile_id) profile_id,'Users/User.php&category_id=1',can_use,can_edit FROM profile_exceptions WHERE modname='Users/User.php');
-- add permission to Schedule tab to teachers
INSERT INTO staff_exceptions (user_id,modname,can_use,can_edit) (SELECT staff_id,'Users/User.php&category_id=2','Y',NULL FROM staff WHERE profile='teacher' AND profile_id IS NULL);
INSERT INTO profile_exceptions (profile_id,modname,can_use,can_edit) (SELECT id,'Users/User.php&category_id=2','Y',NULL FROM user_profiles WHERE profile='teacher');


-- add permission to address, contact, and user fields same as student fields
INSERT INTO staff_exceptions (user_id,modname,can_use,can_edit) (SELECT DISTINCT ON (user_id) user_id,'Students/AddressFields.php',can_use,can_edit FROM staff_exceptions WHERE modname='Students/StudentFields.php');
INSERT INTO staff_exceptions (user_id,modname,can_use,can_edit) (SELECT DISTINCT ON (user_id) user_id,'Students/PeopleFields.php',can_use,can_edit FROM staff_exceptions WHERE modname='Students/StudentFields.php');
INSERT INTO staff_exceptions (user_id,modname,can_use,can_edit) (SELECT DISTINCT ON (user_id) user_id,'Users/UserFields.php',can_use,can_edit FROM staff_exceptions WHERE modname='Students/StudentFields.php');
INSERT INTO profile_exceptions (profile_id,modname,can_use,can_edit) (SELECT DISTINCT ON (profile_id) profile_id,'Students/AddressFields.php',can_use,can_edit FROM profile_exceptions WHERE modname='Students/StudentFields.php');
INSERT INTO profile_exceptions (profile_id,modname,can_use,can_edit) (SELECT DISTINCT ON (profile_id) profile_id,'Students/PeopleFields.php',can_use,can_edit FROM profile_exceptions WHERE modname='Students/StudentFields.php');
INSERT INTO profile_exceptions (profile_id,modname,can_use,can_edit) (SELECT DISTINCT ON (profile_id) profile_id,'Users/UserFields.php',can_use,can_edit FROM profile_exceptions WHERE modname='Students/StudentFields.php');


-- add permission to student labels same as mailing labels
INSERT INTO staff_exceptions (user_id,modname,can_use,can_edit) (SELECT DISTINCT ON (user_id) user_id,'Students/StudentLabels.php',can_use,can_edit FROM staff_exceptions WHERE modname='Students/MailingLabels.php');
INSERT INTO profile_exceptions (profile_id,modname,can_use,can_edit) (SELECT DISTINCT ON (profile_id) profile_id,'Students/StudentLabels.php',can_use,can_edit FROM profile_exceptions WHERE modname='Students/MailingFields.php');
