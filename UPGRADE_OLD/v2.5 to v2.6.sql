-- convert user preferences to user_id from username
ALTER TABLE program_user_config ADD column user_id numeric;
UPDATE program_user_config SET user_id=(select staff_id from staff where username=program_user_config.username);
DELETE from program_user_config where user_id is null;
ALTER TABLE program_user_config ALTER user_id set not null;

DROP INDEX program_user_config_ind1;
CREATE INDEX program_user_config_ind1 ON program_user_config USING btree (user_id, program);
ALTER TABLE program_user_config DROP COLUMN username;


-- convert staff_exceptions to user_id from username
ALTER TABLE staff_exceptions ADD column user_id numeric;
UPDATE staff_exceptions SET user_id=(select staff_id from staff where username=staff_exceptions.username);
DELETE FROM staff_exceptions WHERE user_id is null;
ALTER TABLE staff_exceptions ALTER user_id SET NOT NULL;

ALTER TABLE staff_exceptions DROP COLUMN username;


-- add last_login for users and students
ALTER TABLE staff ADD column last_login timestamp(0) without time zone;
ALTER TABLE students ADD column last_login timestamp(0) without time zone;


-- add student report card comments table
CREATE TABLE student_report_card_comments (
    syear numeric(4,0) not null,
    school_id numeric,
    student_id numeric not null,
    course_period_id numeric not null,
    report_card_comment_id numeric not null,
    comment character varying(1),
    marking_period_id character varying(10) not null
);

alter table student_report_card_comments add primary key (syear,student_id,course_period_id,marking_period_id,report_card_comment_id);
create index student_report_card_comments_ind1 on student_report_card_comments using btree (school_id);


-- add columns for enhanced report_card_comments
-- course_id is so comment can be associated with a course number
ALTER TABLE report_card_comments ADD column course_id numeric;
ALTER TABLE report_card_comments ADD column sort_order numeric;


-- add columns for standard grade scale
ALTER TABLE report_card_grades ADD column break_off numeric;


-- add columns for grade system comments
ALTER TABLE report_card_grades ADD column comment character varying(100);


-- add table for school years
CREATE TABLE school_years (
    marking_period_id numeric not null,
    syear numeric(4,0),
    school_id numeric,
    title character varying(50),
    short_name character varying(10),
    sort_order numeric,
    start_date date,
    end_date date,
    post_start_date date,
    post_end_date date,
    does_grades character varying(1),
    does_exam character varying(1),
    does_comments character varying(1),
    rollover_id numeric
);

ALTER TABLE school_years ADD PRIMARY KEY (marking_period_id);
CREATE INDEX school_years_ind2 ON school_years (syear, school_id, start_date, end_date);

-- add a year_id as the parent marking_period_id for semesters
ALTER TABLE school_semesters ADD COLUMN year_id numeric;

-- create a year marking period for each syear and school found
INSERT INTO school_years (marking_period_id,syear,school_id,title,short_name) SELECT DISTINCT ON (syear, school_id) nextval('marking_period_seq'),syear,school_id,'FullYear','FY' FROM school_semesters;

UPDATE school_semesters SET year_id=(SELECT marking_period_id from school_years WHERE school_years.syear=school_semesters.syear AND school_years.school_id=school_semesters.school_id);
CREATE INDEX school_semesters_ind1 ON school_semesters (year_id);

-- associate FY courses with their respective FY marking period
UPDATE course_periods SET marking_period_id=(SELECT marking_period_id from school_years WHERE school_years.syear=course_periods.syear AND school_years.school_id=course_periods.school_id) WHERE mp='FY';

-- associate FY schedules with their respective FY marking period
UPDATE schedule SET marking_period_id=(SELECT marking_period_id from school_years WHERE school_years.syear=schedule.syear AND school_years.school_id=schedule.school_id) WHERE mp='FY';

-- add column for half day flag
ALTER TABLE course_periods ADD COLUMN half_day character varying(1);


-- add column to allow custom gradescale for course periods
ALTER TABLE course_periods ADD COLUMN does_breakoff character varying(1);
UPDATE course_periods SET does_breakoff='Y';


-- add columns to allow grades, exams, and comments for marking periods
ALTER TABLE school_semesters ADD COLUMN does_grades character varying(1);
ALTER TABLE school_semesters ADD COLUMN does_exam character varying(1);
ALTER TABLE school_semesters ADD COLUMN does_comments character varying(1);
UPDATE school_semesters SET does_grades='Y',does_exam='Y';
ALTER TABLE school_quarters ADD COLUMN does_grades character varying(1);
ALTER TABLE school_quarters ADD COLUMN does_exam character varying(1);
ALTER TABLE school_quarters ADD COLUMN does_comments character varying(1);
UPDATE school_quarters SET does_grades='Y',does_exam='Y';
ALTER TABLE school_progress_periods ADD COLUMN does_grades character varying(1);
ALTER TABLE school_progress_periods ADD COLUMN does_exam character varying(1);
ALTER TABLE school_progress_periods ADD COLUMN does_comments character varying(1);
UPDATE school_progress_periods SET does_grades='Y',does_exam='Y';


-- add column for grade percents with final grades
ALTER TABLE student_report_card_grades ADD column grade_percent numeric(4,1);


-- add column for teacher comments in attendance
ALTER TABLE attendance_period ADD column comment character varying(100);
ALTER TABLE lunch_period ADD column comment character varying(100);
