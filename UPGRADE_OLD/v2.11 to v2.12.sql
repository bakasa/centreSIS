-- add default and sort order to enrollment codes
ALTER TABLE student_enrollment_codes ADD COLUMN default_code character varying(1);
ALTER TABLE student_enrollment_codes ADD COLUMN sort_order numeric;


-- modify tables so multiple attendances can be better handled
ALTER TABLE attendance_code_categories ADD COLUMN rollover_id numeric;
-- delete attendance codes that may have been orphaned in rollovers
DELETE FROM attendance_codes WHERE table_name!='0' AND NOT exists(SELECT '' FROM attendance_code_categories WHERE id=table_name AND syear=attendance_codes.SYEAR);

ALTER TABLE attendance_code_categories ADD COLUMN sort_order numeric;

ALTER TABLE attendance_completed ADD COLUMN table_name numeric;
UPDATE attendance_completed SET table_name='0';
ALTER TABLE attendance_completed ALTER COLUMN table_name SET NOT NULL;

ALTER TABLE attendance_completed DROP CONSTRAINT attendance_completed_pkey;
ALTER TABLE ONLY attendance_completed ADD CONSTRAINT attendance_completed_pkey PRIMARY KEY (staff_id,school_date,period_id,table_name);


ALTER TABLE course_periods ADD column does_attendancex character varying(255);
UPDATE course_periods SET does_attendancex=',0,' WHERE does_attendance='Y';
ALTER TABLE course_periods DROP COLUMN does_attendance;
ALTER TABLE course_periods ADD COLUMN does_attendance character varying(255);
UPDATE course_periods SET does_attendance=does_attendancex;
ALTER TABLE course_periods DROP COLUMN does_attendancex;


-- add constraints to lunch_period table like attendance_period table
DELETE FROM lunch_period WHERE student_id IS NULL;
DELETE FROM lunch_period WHERE school_date IS NULL;
DELETE FROM lunch_period WHERE period_id IS NULL;
ALTER TABLE lunch_period ALTER COLUMN student_id SET NOT NULL;
ALTER TABLE lunch_period ALTER COLUMN school_date SET NOT NULL;
ALTER TABLE lunch_period ALTER COLUMN period_id SET NOT NULL;
ALTER TABLE ONLY lunch_period ADD CONSTRAINT lunch_period_pkey PRIMARY KEY (student_id, school_date, period_id);
CREATE INDEX lunch_period_ind1 ON lunch_period USING btree (student_id);
CREATE INDEX lunch_period_ind2 ON lunch_period USING btree (period_id);
CREATE INDEX lunch_period_ind3 ON lunch_period USING btree (attendance_code);
CREATE INDEX lunch_period_ind4 ON lunch_period USING btree (school_date);
CREATE INDEX lunch_period_ind5 ON lunch_period USING btree (attendance_code);


-- drop the unused area_code column in schools
ALTER TABLE schools DROP COLUMN area_code;

-- modify schools so schools can exist in each syear
-- add syear in schools pkey so school can exist in each year and constrain syear non null
-- populate schools for all syears
ALTER TABLE schools DROP CONSTRAINT schools_pkey;
UPDATE schools SET syear=NULL;
INSERT INTO schools (syear,id,title,address,city,state,zipcode,phone,principal,www_address) SELECT y.syear,s.id,s.title,s.address,s.city,s.state,s.zipcode,s.phone,s.principal,s.www_address FROM schools s,school_years y where y.school_id=s.id;
DELETE FROM schools WHERE syear IS NULL;
ALTER TABLE schools ALTER COLUMN syear SET NOT NULL;
ALTER TABLE ONLY schools ADD CONSTRAINT schools_pkey PRIMARY KEY (id,syear);
-- add school number
ALTER TABLE schools ADD COLUMN school_number character varying(50);


-- add not null constraints to staff first_name and last_name
-- but first make sure no null's
UPDATE staff SET first_name='' WHERE first_name IS NULL ;
UPDATE staff SET last_name='' WHERE last_name IS NULL ;
ALTER TABLE staff ALTER COLUMN first_name SET NOT NULL ;
ALTER TABLE staff ALTER COLUMN last_name SET NOT NULL ;

-- add field for number of display columns for student/staff custom fields
ALTER TABLE student_field_categories ADD COLUMN columns numeric(4,0);
ALTER TABLE staff_field_categories ADD COLUMN columns numeric(4,0);


-- remove '.' from students name_suffix and user name title
UPDATE students SET name_suffix='Jr' WHERE name_suffix='Jr.';
UPDATE students SET name_suffix='Sr' WHERE name_suffix='Sr.';
UPDATE staff SET title='Mr' WHERE title='Mr.';
UPDATE staff SET title='Mrs' WHERE title='Mrs.';
UPDATE staff SET title='Ms' WHERE title='Ms.';
-- add name_suffix to staff
ALTER TABLE staff ADD COLUMN name_suffix character varying(3);


-- add not null constraint to school_gradelevels
DELETE FROM school_gradelevels WHERE school_id IS NULL;
ALTER TABLE school_gradelevels ALTER COLUMN school_id SET NOT NULL;
