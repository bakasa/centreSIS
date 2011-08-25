select setval('gradebook_assignment_types_seq',nextval('gradebook_assignments_seq'));

alter table schedule drop constraint schedule_pkey;
alter table schedule alter column start_date set not NULL;
alter table schedule alter column course_weight set not NULL;
alter table schedule add primary key (syear,student_id,course_id,course_weight,course_period_id,start_date);

ALTER TABLE STAFF ADD COLUMN ROLLOVER_ID NUMERIC;
ALTER TABLE SCHOOL_PERIODS ADD COLUMN ROLLOVER_ID NUMERIC;
ALTER TABLE SCHOOL_SEMESTERS ADD COLUMN ROLLOVER_ID NUMERIC;
ALTER TABLE SCHOOL_QUARTERS ADD COLUMN ROLLOVER_ID NUMERIC;
ALTER TABLE SCHOOL_PROGRESS_PERIODS ADD COLUMN ROLLOVER_ID NUMERIC;
ALTER TABLE COURSE_SUBJECTS ADD COLUMN ROLLOVER_ID NUMERIC;
ALTER TABLE COURSES ADD COLUMN ROLLOVER_ID NUMERIC;
ALTER TABLE COURSE_PERIODS ADD COLUMN ROLLOVER_ID NUMERIC;

alter table student_enrollment add column next_school numeric;
update student_enrollment set next_school=school_id where next_school IS NULL AND END_DATE IS NULL;

alter table students_join_address add column student_id_new numeric;
update students_join_address set student_id_new = student_id::text::int::numeric;
alter table students_join_address drop column student_id;
alter table students_join_address rename student_id_new to student_id;

alter table students_join_people add column student_id_new numeric;
update students_join_people set student_id_new = student_id::text::int::numeric;
alter table students_join_people drop column student_id;
alter table students_join_people rename student_id_new to student_id;

VACUUM;
ANALYZE;