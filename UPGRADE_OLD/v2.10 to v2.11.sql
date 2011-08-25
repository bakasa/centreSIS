-- remove columns deprecated in v2.10
-- (none)


-- add not null contraints to marking period start and end dates
-- force nulls non-null so non-null constraint succeeds
UPDATE school_years SET start_date=CURRENT_DATE WHERE start_date IS NULL;
UPDATE school_years SET end_date=CURRENT_DATE WHERE end_date IS NULL;
ALTER TABLE school_years ALTER COLUMN start_date SET NOT NULL;
ALTER TABLE school_years ALTER COLUMN end_date SET NOT NULL;

UPDATE school_semesters SET start_date=CURRENT_DATE WHERE start_date IS NULL;
UPDATE school_semesters SET end_date=CURRENT_DATE WHERE end_date IS NULL;
ALTER TABLE school_semesters ALTER COLUMN start_date SET NOT NULL;
ALTER TABLE school_semesters ALTER COLUMN end_date SET NOT NULL;

UPDATE school_quarters SET start_date=CURRENT_DATE WHERE start_date IS NULL;
UPDATE school_quarters SET end_date=CURRENT_DATE WHERE end_date IS NULL;
ALTER TABLE school_quarters ALTER COLUMN start_date SET NOT NULL;
ALTER TABLE school_quarters ALTER COLUMN end_date SET NOT NULL;

UPDATE school_progress_periods SET start_date=CURRENT_DATE WHERE start_date IS NULL;
UPDATE school_progress_periods SET end_date=CURRENT_DATE WHERE end_date IS NULL;
ALTER TABLE school_progress_periods ALTER COLUMN start_date SET NOT NULL;
ALTER TABLE school_progress_periods ALTER COLUMN end_date SET NOT NULL;

-- add honor roll cutoffs to grade scales
ALTER TABLE report_card_grade_scales ADD COLUMN hhr_gpa_value numeric(4,2);
ALTER TABLE report_card_grade_scales ADD COLUMN hr_gpa_value numeric(4,2);

-- add report card comment categories
CREATE TABLE report_card_comment_categories (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    course_id numeric,
    sort_order numeric,
    title character varying(100),
    rollover_id numeric
);

CREATE INDEX report_card_comment_categories_ind1 ON report_card_comment_categories USING btree (syear, school_id);

ALTER TABLE ONLY report_card_comment_categories
    ADD CONSTRAINT report_card_comment_categories_pkey PRIMARY KEY (id);

CREATE SEQUENCE report_card_comment_categories_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

SELECT pg_catalog.setval('report_card_comment_categories_seq', 1, true);

ALTER TABLE report_card_comments ADD COLUMN category_id numeric;

INSERT INTO report_card_comment_categories (id, syear, school_id, course_id, sort_order, title) (SELECT DISTINCT ON (syear, school_id, course_id) nextval('REPORT_CARD_COMMENT_CATEGORIES_SEQ'), syear, school_id, course_id, 1, 'This Course' FROM report_card_comments);

UPDATE report_card_comments SET category_id=(SELECT id FROM report_card_comment_categories WHERE syear=report_card_comments.syear AND school_id=report_card_comments.school_id AND course_id=report_card_comments.course_id);

-- add sort order to subjects
ALTER TABLE course_subjects ADD column sort_order numeric;

-- change filled seats of null to zero
UPDATE course_periods SET filled_seats='0' WHERE filled_seats IS NULL;

-- change report_card_comments title column to 200 characters
ALTER TABLE report_card_comments RENAME COLUMN title TO titlex ;
ALTER TABLE report_card_comments ADD COLUMN title varchar(200);
UPDATE report_card_comments SET title=titlex;
ALTER TABLE report_card_comments DROP COLUMN titlex;


-- change grades_completed from period_id based to course_period_id based
ALTER TABLE grades_completed ADD COLUMN course_period_id numeric;
UPDATE grades_completed SET course_period_id=(SELECT course_period_id FROM course_periods WHERE teacher_id=staff_id AND period_id=grades_completed.period_id AND EXISTS (SELECT '' FROM student_report_card_grades WHERE course_period_id=course_periods.course_period_id) LIMIT 1);
DELETE FROM grades_completed WHERE course_period_id IS NULL;
ALTER TABLE grades_completed ALTER COLUMN course_period_id SET NOT NULL;
ALTER TABLE grades_completed DROP CONSTRAINT grades_completed_pkey;
ALTER TABLE grades_completed DROP COLUMN period_id;
ALTER TABLE ONLY grades_completed ADD CONSTRAINT grades_completed_pkey PRIMARY KEY (staff_id, marking_period_id, course_period_id);


-- convert birthdate column to date
ALTER TABLE students ADD column temp_200000004 date;
--UPDATE students SET temp_200000004=cast(custom_200000004 as date);
UPDATE students SET temp_200000004=to_date(custom_200000004,'DD-MON-YY') where length(custom_200000004)=9;
UPDATE students SET temp_200000004=to_date(custom_200000004,'YYYY-MM-DD') where length(custom_200000004)=10;
ALTER TABLE students DROP column custom_200000004;
ALTER TABLE students ADD column custom_200000004 date;
UPDATE students SET custom_200000004=temp_200000004;
ALTER TABLE students DROP column temp_200000004;
