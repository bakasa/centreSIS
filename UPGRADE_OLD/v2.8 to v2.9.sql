-- remove columns deprecated in C2.8
ALTER TABLE  student_field_categories DROP COLUMN allow_teacher_modify;



-- change portal notes student profile from 'student' to '0' consistent with student profile introduced in 2.8
UPDATE portal_notes SET published_profiles=replace(published_profiles,',student,',',0,') WHERE position(',student,' IN published_profiles)>0;

-- create grade scales table
CREATE TABLE report_card_grade_scales (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric NOT NULL,
    title character varying(25),
    comment character varying(100),
    sort_order numeric
);

ALTER TABLE ONLY report_card_grade_scales
    ADD CONSTRAINT report_card_grade_scales_pkey PRIMARY KEY (id);

CREATE SEQUENCE report_card_grade_scales_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

SELECT pg_catalog.setval('report_card_grade_scales_seq', 1, true);

-- create default category for all schools & years for all existing grade codes
INSERT INTO report_card_grade_scales SELECT nextval('REPORT_CARD_GRADE_SCALES_SEQ'),SYEAR,SCHOOL_ID,'Main',NULL,1 FROM school_years;

-- assign existing grade codes to default category
ALTER TABLE report_card_grades ADD COLUMN grade_scale_id numeric;
UPDATE report_card_grades SET grade_scale_id=(SELECT id FROM report_card_grade_scales WHERE SCHOOL_ID=report_card_grades.school_id AND SYEAR=report_card_grades.SYEAR);

-- add grade scale category to course_periods
ALTER TABLE course_periods ADD COLUMN grade_scale_id numeric;

-- assign default category to all courses which are graded
UPDATE course_periods SET grade_scale_id=(SELECT id FROM report_card_grade_scales WHERE SCHOOL_ID=course_periods.school_id AND SYEAR=course_periods.SYEAR) WHERE does_grades='Y';

-- add profile/permission for new report card comments program
INSERT INTO profile_exceptions (profile_id,modname,can_use,can_edit) SELECT profile_id,'Grades/ReportCardComments.php',can_use,can_edit FROM profile_exceptions WHERE modname='Grades/ReportCardCodes.php';
INSERT INTO staff_exceptions (user_id,modname,can_use,can_edit) SELECT user_id,'Grades/ReportCardComments.php',can_use,can_edit FROM staff_exceptions WHERE modname='Grades/ReportCardCodes.php';
-- convert report card codes profile/permission to report card grades
UPDATE profile_exceptions SET modname='Grades/ReportCardGrades.php' WHERE modname='Grades/ReportCardCodes.php';
UPDATE staff_exceptions SET modname='Grades/ReportCardGrades.php' WHERE modname='Grades/ReportCardCodes.php';

-- add grade rounding value of NORMAL for existing teachers using the old default since the new default has changed to 'no rounding'
INSERT INTO program_user_config (program,title,value,user_id) SELECT 'Gradebook','ROUNDING','NORMAL',staff_id FROM staff WHERE profile='teacher' AND NOT EXISTS (SELECT * FROM program_user_config WHERE program='Gradebook' AND title='ROUNDING' AND user_id=staff_id);

-- add teacher permissions for PrintSchedules to teachers that have permission to PrintClassLists
INSERT INTO profile_exceptions (profile_id,modname,can_use) SELECT e.profile_id,'Scheduling/PrintSchedules.php','Y' FROM profile_exceptions e,user_profiles u WHERE u.profile='teacher' AND e.profile_id=u.id AND e.modname='Scheduling/PrintClassLists.php' AND e.can_use='Y';
INSERT INTO staff_exceptions (user_id,modname,can_use) SELECT e.user_id,'Scheduling/PrintSchedules.php','Y' FROM staff_exceptions e,staff s WHERE s.profile='teacher' AND s.profile_id IS NULL AND e.user_id=s.staff_id AND e.modname='Scheduling/PrintClassLists.php' AND e.can_use='Y';

-- add prefixes to final grade percentages
-- semester percentages...
UPDATE program_user_config SET title='SEM-'||title WHERE program='Gradebook' AND title IN (SELECT marking_period_id FROM school_quarters);
UPDATE program_user_config SET title='SEM-'||title WHERE program='Gradebook' AND title IN (SELECT 'E'||marking_period_id FROM school_semesters);
-- year percentages
UPDATE program_user_config SET title='FY-'||title WHERE program='Gradebook' AND title IN (SELECT marking_period_id FROM school_semesters);
UPDATE program_user_config SET title='FY-'||title WHERE program='Gradebook' AND title IN (SELECT 'E'||marking_period_id FROM school_years);


