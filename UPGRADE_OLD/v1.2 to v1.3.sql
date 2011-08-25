ALTER TABLE COURSE_PERIODS DROP COLUMN ROOM_ID;
ALTER TABLE COURSE_PERIODS ADD COLUMN ROOM VARCHAR(10);

alter table gradebook_assignment_types add column final_grade_percent2 numeric(6,5);
update gradebook_assignment_types set final_grade_percent2 = final_grade_percent / 100;
alter table gradebook_assignment_types drop column final_grade_percent;
alter table gradebook_assignment_types add column final_grade_percent numeric(6,5);
update gradebook_assignment_types set final_grade_percent = final_grade_percent2;
alter table gradebook_assignment_types drop column final_grade_percent2;
alter table student_report_card_grades add column comment varchar(255);

CREATE TABLE STUDENT_MP_COMMENTS
(
	STUDENT_ID NUMERIC NOT NULL,
	SYEAR NUMERIC(4) NOT NULL,
	MARKING_PERIOD_ID NUMERIC NOT NULL,
	COMMENT TEXT
);
ALTER TABLE STUDENT_MP_COMMENTS ADD PRIMARY KEY (STUDENT_ID,SYEAR,MARKING_PERIOD_ID);