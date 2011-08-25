alter table student_enrollment_codes drop column school_id;

alter table students add column middle_name2 varchar(50);
update students set middle_name2=middle_name;
alter table students drop column middle_name;
alter table students rename column middle_name2 to middle_name;