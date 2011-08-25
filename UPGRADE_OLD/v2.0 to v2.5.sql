alter table attendance_calendar add column calendar_id numeric;
create sequence calendars_seq start 1 increment 1;

create table attendance_calendars as select distinct school_id from attendance_calendar;
alter table attendance_calendars add column title varchar(100);
alter table attendance_calendars add column syear numeric(4);
update attendance_calendars set syear='2005';
alter table attendance_calendars add column calendar_id numeric;
alter table attendance_calendar add column calendar_id numeric;
update attendance_calendars set calendar_id = nextval('calendars_seq');
update attendance_calendar set calendar_id=(select calendar_id from attendance_calendars where attendance_calendars.school_id=attendance_calendar.school_id);
alter table attendance_calendars alter column calendar_id set not null;
alter table attendance_calendar alter column calendar_id set not null;

update attendance_calendars set title='Main';
alter table attendance_calendars add column default_calendar varchar(1);
update attendance_calendars set default_calendar='Y';

alter table attendance_calendar drop constraint attendance_calendar_pkey; 
alter table attendance_calendar add primary key (syear,school_id,school_date,calendar_id);

alter table course_periods add column calendar_id numeric;
update course_periods set calendar_id = (select calendar_id from attendance_calendars where course_periods.school_id=attendance_calendars.school_id and course_periods.syear=attendance_calendars.syear);

alter table attendance_codes add column table_name numeric;
update attendance_codes set table_name='0';

create table lunch_period as select * from attendance_period;
delete from lunch_period;
alter table lunch_period add column TABLE_NAME NUMERIC;

alter table attendance_codes add column sort_order numeric;

--new

CREATE TABLE ATTENDANCE_CODE_CATEGORIES
(
	ID NUMERIC,
	SYEAR NUMERIC(4),
	SCHOOL_ID NUMERIC,
	TITLE VARCHAR(255)
);

CREATE INDEX ATTENDANCE_CODE_CATEGORIES_IND1 ON ATTENDANCE_CODE_CATEGORIES (ID);
CREATE INDEX ATTENDANCE_CODE_CATEGORIES_IND2 ON ATTENDANCE_CODE_CATEGORIES (SYEAR,SCHOOL_ID);
CREATE SEQUENCE ATTENDANCE_CODE_CATEGORIES_SEQ START 1 INCREMENT 1;

--new

-- troy, hudsonville, centralacs, msad71

alter table gradebook_grades drop CONSTRAINT gradebook_grades_pkey;
alter table gradebook_grades alter column course_period_id set NOT NULL;
alter table gradebook_grades add primary key  (student_id,assignment_id,course_period_id);

alter table student_enrollment add column calendar_id numeric;
update student_enrollment set calendar_id = (select calendar_id from attendance_calendars where student_enrollment.school_id=attendance_calendars.school_id and student_enrollment.syear=attendance_calendars.syear and attendance_calendars.default_calendar='Y');

-- msad71, victory

alter table student_field_categories add column allow_teacher_modify varchar(1);

-- extremelearning, union90, glenburn, afcschools

alter table attendance_day add column comment varchar(255);
update attendance_day set comment = (select attendance_reason from attendance_period where attendance_day.school_date=attendance_period.school_date and attendance_day.student_id=attendance_period.student_id and attendance_period.attendance_reason IS NOT NULL and attendance_period.attendance_reason!='' LIMIT 1);


-- extreme learning, troy

update students set middle_name='' where middle_name=' &nbsp; ';
update staff set middle_name = '' where middle_name=' &nbsp; ';
update course_periods set title=replace(title,' &nbsp; ',' ');
update course_periods set title=replace(title,'  ',' ');

-- avance, bolinas, bethel 

alter table school_periods add column attendance varchar(1);
update school_periods set attendance='Y';

ALTER TABLE STAFF ADD COLUMN PROFILE_ID NUMERIC;
CREATE TABLE USER_PROFILES
(
	ID NUMERIC,
	PROFILE VARCHAR(30),
	TITLE VARCHAR(100)
);

CREATE SEQUENCE USER_PROFILES_SEQ START 1 INCREMENT 1;

CREATE TABLE PROFILE_EXCEPTIONS
(
	PROFILE_ID NUMERIC,
	MODNAME VARCHAR(255),
	CAN_USE VARCHAR(1),
	CAN_EDIT VARCHAR(1)
);

alter table schedule add column id numeric;
create sequence schedule_seq start 1 increment 1;
update schedule set id=nextval('schedule_seq');

create table student_medical_visits
(
	ID NUMERIC PRIMARY KEY,
	STUDENT_ID NUMERIC,
	SCHOOL_DATE DATE,
	TIME_IN VARCHAR(20),
	TIME_OUT VARCHAR(20),
	REASON VARCHAR(100),
	RESULT VARCHAR(100),
	COMMENTS VARCHAR(255)
);
CREATE SEQUENCE STUDENT_MEDICAL_VISITS_SEQ START 1 INCREMENT 1;
CREATE INDEX STUDENT_MEDICAL_VISITS_IND1 ON STUDENT_MEDICAL_VISITS (STUDENT_ID);

ALTER TABLE COURSE_WEIGHTS ADD COLUMN YEAR_FRACTION NUMERIC;
UPDATE COURSE_WEIGHTS SET YEAR_FRACTION='1.0';
drop table student_gpa_running;
CREATE TABLE STUDENT_GPA_RUNNING
(
	STUDENT_ID NUMERIC,
	MARKING_PERIOD_ID NUMERIC,
	GPA_POINTS NUMERIC,
	GPA_POINTS_WEIGHTED NUMERIC,
	DIVISOR NUMERIC
);

CREATE INDEX STUDENT_GPA_RUNNING_IND1 ON STUDENT_GPA_RUNNING (MARKING_PERIOD_ID,STUDENT_ID);

drop table student_gpa_calculated;
CREATE TABLE STUDENT_GPA_CALCULATED
(
	STUDENT_ID NUMERIC,
	MARKING_PERIOD_ID NUMERIC,
	MP VARCHAR(4),
	GPA NUMERIC,
	WEIGHTED_GPA NUMERIC,
	CLASS_RANK NUMERIC
);

CREATE INDEX STUDENT_GPA_CALCULATED_IND1 ON STUDENT_GPA_CALCULATED (MARKING_PERIOD_ID,STUDENT_ID);
delete from school_quarters where not exists (select * from school_semesters where school_semesters.marking_period_id=school_quarters.semester_id);
delete from student_enrollment where school_id IS NULL;