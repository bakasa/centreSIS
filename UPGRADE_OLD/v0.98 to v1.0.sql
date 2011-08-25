alter table students_join_people rename column can_pick_up_stu to emergency;
alter table students add column username varchar(100);
alter table students add column password varchar(100);

alter table students drop column marital_status;
alter table students drop column add_completed;
alter table students drop column original_entry_date;
alter table students drop column feeder_school;
alter table students drop column previous_school_id;
alter table students drop column current_school_id;
alter table students drop column next_school_id;
alter table students drop column grad_date;
alter table students drop column retain;
alter table students drop column cell_phone;
alter table students drop column gifted;
alter table students drop column food_service_code;
alter table students drop column ethnicity2;
alter table students drop column nickname;

alter table student_enrollment drop column homeroom;
alter table student_enrollment drop column house;
alter table student_enrollment drop column calendar;

alter table students_join_people drop column relation;

alter table config drop column marking_period_id;

alter table students add column physician varchar(100);
alter table students add column physician_phone varchar(20);
alter table students add column hospital varchar(100);
alter table students add column medical_comments varchar(500);
alter table students add column doctors_note varchar(1);
alter table students add column doctors_note_comments varchar(500);

create table student_medical
(
	id numeric,
	student_id numeric,
	type varchar(25),
	medical_date date,
	comments varchar(100)
);

create sequence student_medical_seq start 1 increment 1;

create table student_medical_alerts
(
	id numeric,
	student_id numeric,
	title varchar(100)
);

create sequence student_medical_alerts_seq start 1 increment 1;

update course_periods set gender_restriction='N';

alter table custom_fields add column select_options varchar(500);