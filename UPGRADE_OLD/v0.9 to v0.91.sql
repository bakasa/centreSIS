alter table course_periods add column course_weight2 varchar(10);
update course_periods set course_weight2 = course_weight;
alter table course_periods drop column course_weight;
alter table course_periods rename course_weight2 to course_weight;

alter table schedule add column course_weight2 varchar(10);
update schedule set course_weight2 = course_weight;
alter table schedule drop column course_weight;
alter table schedule rename course_weight2 to course_weight;

alter table schedule_requests add column course_weight2 varchar(10);
update schedule_requests set course_weight2 = course_weight;
alter table schedule_requests drop column course_weight;
alter table schedule_requests rename course_weight2 to course_weight;

alter table report_card_grades add column syear numeric(4);
update report_card_grades set syear='2003';
alter table report_card_comments add column syear numeric(4);
update report_card_comments set syear='2003';

alter table course_subjects drop column department_id;
alter table courses drop column department_id;

alter table course_subjects add column rollover_id numeric;
alter table courses add column rollover_id numeric;
alter table course_weights add column rollover_id numeric;
alter table course_periods add column rollover_id numeric;
alter table school_semesters add column rollover_id numeric;
alter table school_quarters add column rollover_id numeric;
alter table school_progress_periods add column rollover_id numeric;
alter table school_periods add column rollover_id numeric;
alter table staff add column rollover_id numeric;

alter table students_join_users drop column syear;

alter table school_gradelevels drop column next_grade;
alter table school_gradelevels add column next_grade_id numeric;
