-- promote student_report_card_comments:comment to 5 characters
ALTER TABLE student_report_card_comments ADD COLUMN commentx character varying(1);
UPDATE student_report_card_comments SET commentx=comment;
ALTER TABLE student_report_card_comments drop COLUMN comment;
ALTER TABLE student_report_card_comments ADD COLUMN comment character varying(5);
UPDATE student_report_card_comments SET comment=commentx;
ALTER TABLE student_report_card_comments drop COLUMN commentx;

--
ALTER TABLE report_card_comments ADD COLUMN scale_id numeric;


CREATE TABLE report_card_comment_code_scales (
    id numeric NOT NULL,
    school_id numeric NOT NULL,
    title character varying(25),
    "comment" character varying(100),
    sort_order numeric,
    rollover_id numeric
);


ALTER TABLE ONLY report_card_comment_code_scales
    ADD CONSTRAINT report_card_card_comment_code_scales_pkey PRIMARY KEY (id);


CREATE SEQUENCE report_card_comment_code_scales_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;



CREATE TABLE report_card_comment_codes (
    id numeric NOT NULL,
    school_id numeric NOT NULL,
    scale_id numeric NOT NULL,
    title character varying(5) NOT NULL,
    short_name character varying(100),
    "comment" character varying(100),
    sort_order numeric
);


ALTER TABLE ONLY report_card_comment_codes
    ADD CONSTRAINT report_card_comment_codes_pkey PRIMARY KEY (id);
CREATE INDEX report_card_comment_codes_ind1 ON report_card_comment_codes USING btree (school_id);


CREATE SEQUENCE report_card_comment_codes_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

ALTER TABLE schools ADD COLUMN short_name character varying(25);

INSERT INTO profile_exceptions (profile_id,modname,can_use,can_edit) SELECT profile_id,'Grades/ReportCardCommentCodes.php',can_use,can_edit FROM profile_exceptions WHERE modname='Grades/ReportCardComments.php';
INSERT INTO   staff_exceptions (   user_id,modname,can_use,can_edit) SELECT    user_id,'Grades/ReportCardCommentCodes.php',can_use,can_edit FROM   staff_exceptions WHERE modname='Grades/ReportCardComments.php';

--END 2.15 changes, BEGIN 3.0 changes

CREATE PROCEDURAL LANGUAGE plpgsql;

--CREATE new column for schools and fill with default grade scale
ALTER TABLE schools
  ADD COLUMN reporting_gp_scale numeric(10,3);
UPDATE schools
  SET reporting_gp_scale = 4;

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/EditHistoryMarkingPeriods.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/EditReportCardGrades.php', 'Y', 'Y');

ALTER TABLE schedule DROP CONSTRAINT schedule_pkey;
--ALTER TABLE schedule ADD CONSTRAINT schedule_pkey PRIMARY KEY (syear, student_id, course_id, course_period_id, start_date);

ALTER TABLE schedule
   ALTER COLUMN course_weight DROP NOT NULL;
   
--Adding/altering columns in student_report_card_grades
ALTER TABLE student_report_card_grades 
  DROP CONSTRAINT student_report_card_grades_pkey;
ALTER TABLE student_report_card_grades
  ALTER COLUMN syear DROP NOT NULL;
ALTER TABLE student_report_card_grades
  ALTER COLUMN syear SET STATISTICS -1;
ALTER TABLE student_report_card_grades
  ALTER COLUMN course_period_id DROP NOT NULL;
ALTER TABLE student_report_card_grades
  ADD COLUMN grade_letter            character varying(5);
ALTER TABLE student_report_card_grades
  ADD COLUMN weighted_gp             numeric;
ALTER TABLE student_report_card_grades
  ADD COLUMN unweighted_gp           numeric;
ALTER TABLE student_report_card_grades
  ADD COLUMN gp_scale                numeric;
ALTER TABLE student_report_card_grades
  ADD COLUMN credit_attempted        numeric;
ALTER TABLE student_report_card_grades  ADD COLUMN credit_earned           numeric;
ALTER TABLE student_report_card_grades  ADD COLUMN credit_category         character varying(10);
ALTER TABLE student_report_card_grades  ADD COLUMN course_title            character varying(100);
ALTER TABLE student_report_card_grades  ADD COLUMN id                      int4 unique;
ALTER TABLE student_report_card_grades  ADD COLUMN school 		     character varying(255);
ALTER TABLE student_report_card_grades  ADD COLUMN class_rank 	     character varying(1);
  
CREATE SEQUENCE student_report_card_grades_seq;
ALTER TABLE student_report_card_grades_seq OWNER TO postgres;
ALTER TABLE student_report_card_grades
  ALTER COLUMN id SET DEFAULT nextval('student_report_card_grades_seq');
UPDATE student_report_card_grades
  SET id=DEFAULT;

--ALTER TABLE student_report_card_grades DROP CONSTRAINT student_report_card_grades_id_key;
ALTER TABLE student_report_card_grades ADD CONSTRAINT student_report_card_grades_pkey PRIMARY KEY (id);
-- DROP INDEX student_report_card_grades_ind2;

CREATE INDEX student_report_card_grades_ind2
  ON student_report_card_grades
  USING btree
  (student_id);

-- Index: student_report_card_grades_ind3

-- DROP INDEX student_report_card_grades_ind3;

CREATE INDEX student_report_card_grades_ind3
  ON student_report_card_grades
  USING btree
  (course_period_id);

-- Index: student_report_card_grades_ind4

-- DROP INDEX student_report_card_grades_ind4;

CREATE INDEX student_report_card_grades_ind4
  ON student_report_card_grades
  USING btree
  (marking_period_id);

--Adding column to report_card_grade_scales
ALTER TABLE report_card_grade_scales
  ADD COLUMN gp_scale     numeric(10,3);
UPDATE report_card_grade_scales
  SET gp_scale = 4;
  
--Adding column to report_card_grades
ALTER TABLE report_card_grades
  ADD COLUMN unweighted_gp  numeric(4,2);

--Changing column in course_periods
ALTER TABLE course_periods
	DROP COLUMN credits;
ALTER TABLE course_periods
	ADD COLUMN credits numeric;
ALTER TABLE course_periods 
	ALTER COLUMN course_weight DROP NOT NULL;


--Add new tables

-- Table: student_mp_stats

-- DROP TABLE student_mp_stats;

CREATE TABLE student_mp_stats
(
  student_id integer NOT NULL,
  marking_period_id integer NOT NULL,
  cum_weighted_factor numeric,
  cum_unweighted_factor numeric,
  cum_rank integer,
  mp_rank integer,
  class_size integer,
  sum_weighted_factors numeric,
  sum_unweighted_factors numeric,
  count_weighted_factors numeric,
  count_unweighted_factors numeric,
  grade_level_short character varying(3),
  cr_weighted_factors numeric,
  cr_unweighted_factors numeric,
  count_cr_factors integer,
  cum_cr_weighted_factor numeric,
  cum_cr_unweighted_factor numeric,
  credit_attempted numeric,
  credit_earned numeric,
  gp_credits numeric,
  cr_credits numeric,
  comments character varying(75),
  CONSTRAINT student_mp_stats_pkey PRIMARY KEY (student_id, marking_period_id)
)
WITHOUT OIDS;
ALTER TABLE student_mp_stats OWNER TO postgres;

-- Table: history_marking_periods

-- DROP TABLE history_marking_periods;

CREATE TABLE history_marking_periods
(
  parent_id integer,
  mp_type character(20),
  name character(30),
  short_name character varying(10),
  post_end_date date,
  school_id integer,
  syear integer,
  marking_period_id integer NOT NULL DEFAULT nextval('marking_period_seq'::text),
  CONSTRAINT history_marking_periods_pkey PRIMARY KEY (marking_period_id)
) 
WITHOUT OIDS;
ALTER TABLE history_marking_periods OWNER TO postgres;


-- Index: history_marking_period_ind1

-- DROP INDEX history_marking_period_ind1;

CREATE INDEX history_marking_period_ind1
  ON history_marking_periods
  USING btree
  (school_id);

-- Index: history_marking_period_ind2

-- DROP INDEX history_marking_period_ind2;

CREATE INDEX history_marking_period_ind2
  ON history_marking_periods
  USING btree
  (syear);

-- Index: history_marking_period_ind3

-- DROP INDEX history_marking_period_ind3;

CREATE INDEX history_marking_period_ind3
  ON history_marking_periods
  USING btree
  (mp_type);

-- Table: student_test_categories

-- DROP TABLE student_test_categories;

CREATE TABLE student_test_categories
(
  id serial NOT NULL,
  test character varying(25),
  category character varying(40),
  CONSTRAINT student_test_categories_pkey PRIMARY KEY (id)
)
WITHOUT OIDS;
ALTER TABLE student_test_categories OWNER TO postgres;

-- Table: student_test_scores

-- DROP TABLE student_test_scores;

CREATE TABLE student_test_scores
(
  id serial NOT NULL,
  student_id integer,
  test_category_id integer,
  score character varying(25),
  test_date date,
  CONSTRAINT student_test_scores_pkey PRIMARY KEY (id)
)
WITHOUT OIDS;
ALTER TABLE student_test_scores OWNER TO postgres;

--new views

-- View: "marking_periods"

-- DROP VIEW marking_periods;
/*---  OLD marking_periods view using separate marking period tables
CREATE OR REPLACE VIEW marking_periods AS 
(( SELECT school_quarters.marking_period_id, 'Centre' AS mp_source, school_quarters.syear, school_quarters.school_id, 'quarter' AS mp_type, school_quarters.title, school_quarters.short_name, school_quarters.sort_order, school_quarters.semester_id AS parent_id, school_semesters.year_id AS grandparent_id, school_quarters.start_date, school_quarters.end_date, school_quarters.post_start_date, school_quarters.post_end_date, school_quarters.does_grades, school_quarters.does_exam, school_quarters.does_comments
   FROM school_quarters
   JOIN school_semesters ON school_quarters.semester_id = school_semesters.marking_period_id
UNION 
 SELECT school_semesters.marking_period_id, 'Centre' AS mp_source, school_semesters.syear, school_semesters.school_id, 'semester' AS mp_type, school_semesters.title, school_semesters.short_name, school_semesters.sort_order, school_semesters.year_id AS parent_id, -1 AS grandparent_id, school_semesters.start_date, school_semesters.end_date, school_semesters.post_start_date, school_semesters.post_end_date, school_semesters.does_grades, school_semesters.does_exam, school_semesters.does_comments
   FROM school_semesters)
UNION 
 SELECT school_years.marking_period_id, 'Centre' AS mp_source, school_years.syear, school_years.school_id, 'year' AS mp_type, school_years.title, school_years.short_name, school_years.sort_order, -1 AS parent_id, -1 AS grandparent_id, school_years.start_date, school_years.end_date, school_years.post_start_date, school_years.post_end_date, school_years.does_grades, school_years.does_exam, school_years.does_comments
   FROM school_years)
 SELECT school_marking_periods.marking_period_id, 'Centre' AS mp_source, school
UNION 
 SELECT history_marking_periods.marking_period_id, 'History' AS mp_source, history_marking_periods.syear, history_marking_periods.school_id, history_marking_periods.mp_type, history_marking_periods.name AS title, NULL::"unknown" AS short_name, NULL::"unknown" AS sort_order, history_marking_periods.parent_id, -1 AS grandparent_id, NULL::"unknown" AS start_date, history_marking_periods.post_end_date AS end_date, NULL::"unknown" AS post_start_date, history_marking_periods.post_end_date, 'Y' AS does_grades, NULL::"unknown" AS does_exam, NULL::"unknown" AS does_comments
   FROM history_marking_periods;

ALTER TABLE marking_periods OWNER TO postgres;
*/


CREATE OR REPLACE VIEW marking_periods AS 
 SELECT school_marking_periods.marking_period_id, 'Centre' AS mp_source, school_marking_periods.syear, school_marking_periods.school_id, 
CASE WHEN school_marking_periods.mp = 'FY' THEN 'year'
	WHEN school_marking_periods.mp = 'SEM' THEN 'semester'
	WHEN school_marking_periods.mp = 'QTR' THEN 'quarter'
END AS mp_type,
 school_marking_periods.title, school_marking_periods.short_name, school_marking_periods.sort_order,
case when school_marking_periods.parent_id > 0
then school_marking_periods.parent_id 
else -1
end as parent_id,
case when (select smp.parent_id from school_marking_periods smp where smp.marking_period_id = school_marking_periods.parent_id) > 0 then (select smp.parent_id from school_marking_periods smp where smp.marking_period_id = school_marking_periods.parent_id)ELSE
-1
END
 AS grandparent_id, 
school_marking_periods.start_date, school_marking_periods.end_date, school_marking_periods.post_start_date, school_marking_periods.post_end_date, school_marking_periods.does_grades, school_marking_periods.does_exam, school_marking_periods.does_comments
   FROM school_marking_periods

UNION 
 SELECT history_marking_periods.marking_period_id, 'History' AS mp_source, history_marking_periods.syear, history_marking_periods.school_id, history_marking_periods.mp_type, history_marking_periods.name AS title, history_marking_periods.short_name AS short_name, NULL::"unknown" AS sort_order, history_marking_periods.parent_id, -1 AS grandparent_id, NULL::"unknown" AS start_date, history_marking_periods.post_end_date AS end_date, NULL::"unknown" AS post_start_date, history_marking_periods.post_end_date, 'Y' AS does_grades, NULL::"unknown" AS does_exam, NULL::"unknown" AS does_comments
   FROM history_marking_periods;

ALTER TABLE marking_periods OWNER TO postgres;

--New view  requires view marking_periods and data in student_mp_stats
-- View: transcript_grades

--DROP VIEW transcript_grades;

CREATE OR REPLACE VIEW transcript_grades AS 
 SELECT mp.syear, mp.school_id, mp.marking_period_id, mp.mp_type, mp.short_name, mp.parent_id, mp.grandparent_id, ( SELECT mp2.end_date
           FROM student_report_card_grades
      JOIN marking_periods mp2 ON mp2.marking_period_id::text = student_report_card_grades.marking_period_id::text
     WHERE student_report_card_grades.student_id = sms.student_id::numeric AND (student_report_card_grades.marking_period_id::text = mp.parent_id::text OR student_report_card_grades.marking_period_id::text = mp.grandparent_id::text) AND student_report_card_grades.course_title::text = srcg.course_title::text
     ORDER BY mp2.end_date
    LIMIT 1) AS parent_end_date, mp.end_date, sms.student_id, sms.cum_weighted_factor * schools.reporting_gp_scale AS cum_weighted_gpa, sms.cum_unweighted_factor * schools.reporting_gp_scale AS cum_unweighted_gpa, sms.cum_rank, sms.mp_rank, sms.class_size, sms.sum_weighted_factors / sms.count_weighted_factors * schools.reporting_gp_scale AS weighted_gpa, sms.sum_unweighted_factors / sms.count_unweighted_factors * schools.reporting_gp_scale AS unweighted_gpa, sms.grade_level_short, srcg."comment", srcg.grade_percent, srcg.grade_letter, srcg.weighted_gp, srcg.unweighted_gp, srcg.gp_scale, srcg.credit_attempted, srcg.credit_earned, srcg.course_title, srcg.school AS school_name, schools.reporting_gp_scale AS school_scale, sms.cr_weighted_factors / sms.count_cr_factors::numeric * schools.reporting_gp_scale AS cr_weighted_gpa, sms.cr_unweighted_factors / sms.count_cr_factors::numeric * schools.reporting_gp_scale AS cr_unweighted_gpa, sms.cum_cr_weighted_factor * schools.reporting_gp_scale AS cum_cr_weighted_gpa, sms.cum_cr_unweighted_factor * schools.reporting_gp_scale AS cum_cr_unweighted_gpa, srcg.class_rank
   FROM marking_periods mp
   JOIN student_report_card_grades srcg ON mp.marking_period_id::text = srcg.marking_period_id::text
   JOIN student_mp_stats sms ON sms.marking_period_id::numeric = mp.marking_period_id AND sms.student_id::numeric = srcg.student_id
   JOIN schools ON mp.school_id = schools.id;

ALTER TABLE transcript_grades OWNER TO postgres;

-- View: "course_details"

-- DROP VIEW course_details;

CREATE OR REPLACE VIEW course_details AS 
 SELECT cp.school_id, cp.syear, cp.marking_period_id, cp.period_id, c.subject_id, cp.course_id, cp.course_period_id, cp.course_weight, cp.teacher_id, c.title AS course_title, cp.title AS cp_title, cp.grade_scale_id, cw.gpa_multiplier, cp.mp, cp.credits, cw.year_fraction
   FROM course_periods cp, courses c, course_weights cw
  WHERE cp.course_id = c.course_id AND cp.course_id = cw.course_id AND cp.course_weight::text = cw.course_weight::text;

ALTER TABLE course_details OWNER TO postgres;



-- View: "enroll_grade"

-- DROP VIEW enroll_grade;

CREATE OR REPLACE VIEW enroll_grade AS 
 SELECT e.id, e.syear, e.school_id, e.student_id, e.start_date, e.end_date, sg.short_name, sg.title
   FROM student_enrollment e, school_gradelevels sg
  WHERE e.grade_id = sg.id;

ALTER TABLE enroll_grade OWNER TO postgres;
COMMENT ON VIEW enroll_grade IS 'Provides enrollment dates and grade levels';

--new functions

 -- Function: calc_cum_gpa(character varying, integer)

-- DROP FUNCTION calc_cum_gpa(character varying, integer);

CREATE OR REPLACE FUNCTION calc_cum_gpa(character varying, integer)
  RETURNS integer AS
'DECLARE
  mp_id ALIAS for $1;
  s_id ALIAS for $2;
  mpinfo marking_periods%ROWTYPE;
  s student_mp_stats%ROWTYPE;
BEGIN
  SELECT * INTO mpinfo FROM marking_periods WHERE marking_period_id = mp_id;
    UPDATE student_mp_stats
    SET cum_weighted_factor = sms1.weighted_gpa,
        cum_unweighted_factor = sms1.unweighted_gpa FROM (

select (sum((weighted_gp/gp_scale)*credit_attempted)/sum(credit_attempted)) as weighted_gpa,
(sum((unweighted_gp/gp_scale)*credit_attempted)/sum(credit_attempted)) as unweighted_gpa

from (
  SELECT weighted_gp, unweighted_gp, gp_scale, credit_attempted, credit_earned, school_scale 
  FROM transcript_grades where student_id = s_id
  and (end_date <= mpinfo.end_date and (parent_end_date is null or parent_end_date >  mpinfo.end_date) or marking_period_id = mp_id)
  and gp_scale > 0 and credit_attempted > 0 ) as x group by school_scale) as sms1
    WHERE student_mp_stats.student_id = s_id and student_mp_stats.marking_period_id = mp_id;
  RETURN 1;
END;
'
  LANGUAGE 'plpgsql' VOLATILE;
ALTER FUNCTION calc_cum_gpa(character varying, integer) OWNER TO postgres;

-- Function: calc_cum_cr_gpa(character varying, integer)

-- DROP FUNCTION calc_cum_cr_gpa(character varying, integer);

CREATE OR REPLACE FUNCTION calc_cum_cr_gpa(character varying, integer)
  RETURNS integer AS
'DECLARE
  mp_id ALIAS for $1;
  s_id ALIAS for $2;
  mpinfo marking_periods%ROWTYPE;
  s student_mp_stats%ROWTYPE;
BEGIN
  SELECT * INTO mpinfo FROM marking_periods WHERE marking_period_id = mp_id;
    UPDATE student_mp_stats
    SET cum_cr_weighted_factor = sms1.weighted_gpa,
        cum_cr_unweighted_factor = sms1.unweighted_gpa FROM (

select (sum((weighted_gp/gp_scale)*credit_attempted)/sum(credit_attempted)) as weighted_gpa,
(sum((unweighted_gp/gp_scale)*credit_attempted)/sum(credit_attempted)) as unweighted_gpa

from (
  SELECT weighted_gp, unweighted_gp, gp_scale, credit_attempted, credit_earned, school_scale 
  FROM transcript_grades where student_id = s_id
  and (end_date <= mpinfo.end_date and (parent_end_date is null or parent_end_date >  mpinfo.end_date) or marking_period_id = mp_id)
  and gp_scale > 0 and credit_attempted > 0 and class_rank = ''Y'' ) as x group by school_scale) as sms1
    WHERE student_mp_stats.student_id = s_id and student_mp_stats.marking_period_id = mp_id;
  RETURN 1;
END;

'
  LANGUAGE 'plpgsql' VOLATILE;
ALTER FUNCTION calc_cum_cr_gpa(character varying, integer) OWNER TO postgres;

-- Function: calc_cum_gpa_mp(character varying)

-- DROP FUNCTION calc_cum_gpa_mp(character varying);

CREATE OR REPLACE FUNCTION calc_cum_gpa_mp(character varying)
  RETURNS integer AS
'DECLARE
  mp_id ALIAS for $1;
  mpinfo marking_periods%ROWTYPE;
  s student_mp_stats%ROWTYPE;
BEGIN
  FOR s in select student_id from student_mp_stats where marking_period_id = mp_id LOOP
   
    PERFORM calc_cum_gpa(mp_id, s.student_id);
    PERFORM calc_cum_cr_gpa(mp_id, s.student_id);
  END LOOP;
  RETURN 1;
END;

'
  LANGUAGE 'plpgsql' VOLATILE;
ALTER FUNCTION calc_cum_gpa_mp(character varying) OWNER TO postgres;

-- Function: calc_gpa_mp(integer, character varying)

-- DROP FUNCTION calc_gpa_mp(integer, character varying);

CREATE OR REPLACE FUNCTION calc_gpa_mp(integer, character varying)
  RETURNS integer AS
'
DECLARE
  s_id ALIAS for $1;
  mp_id ALIAS for $2;
  oldrec student_mp_stats%ROWTYPE;
BEGIN
  SELECT * INTO oldrec FROM student_mp_stats WHERE student_id = s_id and marking_period_id = mp_id;

  IF FOUND THEN
    UPDATE STUDENT_MP_STATS SET 
        sum_weighted_factors = rcg.sum_weighted_factors, 
        sum_unweighted_factors = rcg.sum_unweighted_factors, 
        cr_weighted_factors = rcg.cr_weighted,
        cr_unweighted_factors = rcg.cr_unweighted,
        gp_credits = rcg.gp_credits,
        cr_credits = rcg.cr_credits
        
      FROM (
      select 
        sum(weighted_gp*credit_attempted/gp_scale) as sum_weighted_factors, 
        sum(unweighted_gp*credit_attempted/gp_scale) as sum_unweighted_factors, 
        sum(credit_attempted) as gp_credits,
        sum( case when class_rank = ''Y'' THEN weighted_gp*credit_attempted/gp_scale END ) as cr_weighted,
        sum( case when class_rank = ''Y'' THEN unweighted_gp*credit_attempted/gp_scale END ) as cr_unweighted,
        sum( case when class_rank = ''Y'' THEN credit_attempted END) as cr_credits

        from student_report_card_grades where student_id = s_id
        and marking_period_id = mp_id
         and not gp_scale = 0 and not marking_period_id LIKE ''E%'' group by student_id, marking_period_id
        ) as rcg
WHERE student_id = s_id and marking_period_id = mp_id;
    RETURN 1;
  ELSE
    INSERT INTO STUDENT_MP_STATS (student_id, marking_period_id, sum_weighted_factors, sum_unweighted_factors, grade_level_short, cr_weighted_factors, cr_unweighted_factors, gp_credits, cr_credits)

        select 
            srcg.student_id, (srcg.marking_period_id::text)::int, 
            sum(weighted_gp*credit_attempted/gp_scale) as sum_weighted_factors, 
            sum(unweighted_gp*credit_attempted/gp_scale) as sum_unweighted_factors, 
            eg.short_name,
            sum( case when class_rank = ''Y'' THEN weighted_gp*credit_attempted/gp_scale END ) as cr_weighted,
	    sum( case when class_rank = ''Y'' THEN unweighted_gp*credit_attempted/gp_scale END ) as cr_unweighted,
            sum(credit_attempted) as gp_credits,
            sum(case when class_rank = ''Y'' THEN credit_attempted END) as cr_credits
        from student_report_card_grades srcg join marking_periods mp on (mp.marking_period_id = srcg.marking_period_id) left outer join enroll_grade eg on (eg.student_id = srcg.student_id and eg.syear = mp.syear and eg.school_id = mp.school_id)
        where srcg.student_id = s_id and srcg.marking_period_id = mp_id and not srcg.gp_scale = 0 
        and not srcg.marking_period_id LIKE ''E%'' group by srcg.student_id, srcg.marking_period_id, eg.short_name;
  END IF;
  RETURN 0;
END
'
  LANGUAGE 'plpgsql' VOLATILE;


-- Function: credit(integer, character varying)

-- DROP FUNCTION credit(integer, character varying);

CREATE OR REPLACE FUNCTION credit(integer, character varying)
  RETURNS numeric AS
'
DECLARE
	course_detail RECORD;
	mp_detail RECORD;
	values RECORD;
	
BEGIN
select * into course_detail from course_periods where course_period_id = $1;
select * into mp_detail from marking_periods where marking_period_id = $2;

IF course_detail.marking_period_id = mp_detail.marking_period_id THEN
	return course_detail.credits;
ELSIF course_detail.mp = ''FY'' AND mp_detail.mp_type = ''semester'' THEN
	select into values count(*) as mp_count from marking_periods where parent_id = course_detail.marking_period_id group by parent_id;
ELSIF course_detail.mp = ''FY'' and mp_detail.mp_type = ''quarter'' THEN
	select into values count(*) as mp_count from marking_periods where grandparent_id = course_detail.marking_period_id group by grandparent_id;
ELSIF course_detail.mp = ''SEM'' and mp_detail.mp_type = ''quarter'' THEN
	select into values count(*) as mp_count from marking_periods where parent_id = course_detail.marking_period_id group by parent_id;
ELSE
	return 0;
END IF;

IF values.mp_count > 0 THEN
	return course_detail.credits/values.mp_count;
ELSE
	return 0;
END IF;

END'
  LANGUAGE 'plpgsql' VOLATILE;


-- Function: t_update_mp_stats()

-- DROP FUNCTION t_update_mp_stats();

-- DROP FUNCTION set_class_rank_mp(character varying);

CREATE OR REPLACE FUNCTION set_class_rank_mp(character varying)
  RETURNS integer AS
'
DECLARE 
	mp_id alias for $1;
BEGIN
update student_mp_stats set cum_rank = rank.rank, class_size = rank.class_size  from
(
select 
mp.syear, mp.marking_period_id, sgm.student_id, se.grade_id, sgm.cum_cr_weighted_factor
,
 (select count(*)+1 
   from student_mp_stats sgm3
   where sgm3.cum_cr_weighted_factor > sgm.cum_cr_weighted_factor
     and sgm3.marking_period_id = mp.marking_period_id 
     and sgm3.student_id in (select distinct sgm2.student_id 
                            from student_mp_stats sgm2, student_enrollment se2
                            where sgm2.student_id = se2.student_id 
                              and sgm2.marking_period_id = mp.marking_period_id 
				and se2.grade_id = se.grade_id
				and se2.syear = se.syear)
) as rank,

 (select count(*) 
   from student_mp_stats sgm4
   where
     sgm4.marking_period_id = mp.marking_period_id 
     and sgm4.student_id in (select distinct sgm5.student_id 
                            from student_mp_stats sgm5, student_enrollment se3
                            where sgm5.student_id = se3.student_id 
                              and sgm5.marking_period_id = mp.marking_period_id 
				and se3.grade_id = se.grade_id
				and se3.syear = se.syear)
) as class_size

  
from student_enrollment se, student_mp_stats sgm, marking_periods mp
 
where 
se.student_id = sgm.student_id
and sgm.marking_period_id = mp.marking_period_id
and mp.marking_period_id = mp_id
and se.syear = mp.syear
and not sgm.cum_cr_weighted_factor is null
order by grade_id, rank ) as rank



where student_mp_stats.marking_period_id = rank.marking_period_id
and student_mp_stats.student_id = rank.student_id;
RETURN 1;
END;
'
  LANGUAGE 'plpgsql' VOLATILE;
ALTER FUNCTION set_class_rank_mp(character varying) OWNER TO postgres;


CREATE OR REPLACE FUNCTION t_update_mp_stats()
  RETURNS "trigger" AS
'
begin

  IF tg_op = ''DELETE'' THEN
	perform calc_gpa_mp(OLD.student_id::int, OLD.marking_period_id::varchar);
  ELSE
	--IF tg_op = ''INSERT'' THEN
		--we need to do stuff here to gather other information since it''s a new record.
	--ELSE
		--if report_card_grade_id changes, then we need to reset gp values
	--	IF NOT NEW.report_card_grade_id = OLD.report_card_grade_id THEN
			--
	perform calc_gpa_mp(NEW.student_id::int, NEW.marking_period_id::varchar);
  END IF;
  return NULL;
end
'
  LANGUAGE 'plpgsql' VOLATILE;
--
--
--
--STAGE 2
--set unweighted grade points = grade points initially.

UPDATE report_card_grades SET unweighted_gp = gpa_value WHERE unweighted_gp IS NULL;


--create new grading scales from course_weights

-- Function: create_new_scales()

-- DROP FUNCTION create_new_scales();

CREATE OR REPLACE FUNCTION create_new_scales()
  RETURNS integer AS
'
DECLARE
	new_gs_id integer;
	new_gs_name character varying;
	ws RECORD;
	
BEGIN 

FOR ws in select distinct syear, school_id, grade_scale_id, gpa_multiplier, ''MULT '' || gpa_multiplier as newname from course_details 
where not grade_scale_id is null and not gpa_multiplier = 1 LOOP
	new_gs_id := nextval(''report_card_grade_scales_seq'');
	insert into report_card_grade_scales 
		(id, syear, school_id, title, gp_scale) 
	values
		(new_gs_id, ws.syear, ws.school_id, ws.newname, 4);
        insert into report_card_grades (id, syear, school_id, title, sort_order, gpa_value, break_off, comment, grade_scale_id, unweighted_gp)
	select nextval(''report_card_grades_seq'') as id, syear, school_id, title, sort_order, gpa_value*ws.gpa_multiplier as gpa_value, break_off, comment, new_gs_id as grade_scale_id, gpa_value as unweighted_gp  from report_card_grades where grade_scale_id = ws.grade_scale_id;
	update course_periods 
	set grade_scale_id = new_gs_id
	where course_period_id in (select course_period_id from course_details where grade_scale_id = ws.grade_scale_id and gpa_multiplier = ws.gpa_multiplier);

	
END LOOP;
RETURN 1;
END'
  LANGUAGE 'plpgsql' VOLATILE;

SELECT create_new_scales();

DROP FUNCTION create_new_scales();


--Move credits into course_periods from course_weights
UPDATE course_periods SET credits = w.credits
FROM
(
SELECT 
cp.course_period_id, cp.course_weight, cp.course_title, cp.mp, cp.marking_period_id, cp.grade_scale_id, cp.year_fraction,
case WHEN (cp.mp = 'SEM' AND NOT childcount = 0) then year_fraction/(select count(*) FROM marking_periods mp1 where mp.parent_id = mp1.parent_id and not mp.parent_id = -1) WHEN (cp.mp = 'QTR' AND not grandchildcount = 0) THEN year_fraction/(select count(*) FROM marking_periods mp1 WHERE mp.grandparent_id = mp1.grandparent_id AND NOT mp.grandparent_id = -1) ELSE    year_fraction END as credits 
FROM course_details cp JOIN
(SELECT 
 mp1.marking_period_id, mp1.parent_id, mp1.grandparent_id, 
(SELECT count(*) FROM marking_periods mp2 WHERE mp1.parent_id = mp2.parent_id AND NOT mp1.parent_id = -1) as childcount, 
(select count(*) FROM marking_periods mp2 WHERE mp1.grandparent_id = mp2.grandparent_id AND NOT mp1.grandparent_id = -1) as grandchildcount
FROM marking_periods mp1) as mp ON mp.marking_period_id = cp.marking_period_id) as w WHERE w.course_period_id = course_periods.course_period_id;


--change view to reflect changes to course_period

-- View: "course_details"

DROP VIEW course_details;

CREATE OR REPLACE VIEW course_details AS 
 SELECT cp.school_id, cp.syear, cp.marking_period_id, cp.period_id, c.subject_id, cp.course_id, cp.course_period_id, cp.teacher_id, c.title AS course_title, cp.title AS cp_title, cp.grade_scale_id, cp.mp, cp.credits
   FROM course_periods cp, courses c
  WHERE cp.course_id = c.course_id;

ALTER TABLE course_details OWNER TO postgres;

--copy off course_weights and drop it.

SELECT * INTO old_course_weights FROM course_weights;
DROP TABLE course_weights;


--move existing grade_id over to new grade_id

update student_report_card_grades
set report_card_grade_id = found.new_id
from
(
select srcg.syear, srcg.student_id, srcg.school_id, srcg.course_period_id, srcg.marking_period_id, 
orcg.id as old_id, orcg.title as old_grade, orcg.grade_scale_id as old_scale_id, 
nrcg.title as new_grade, nrcg.id as new_id, nrcg.grade_scale_id as new_scale_id 
from 
student_report_card_grades srcg, 
course_periods cp, report_card_grades orcg, report_card_grades nrcg 
where 
orcg.id = srcg.report_card_grade_id
and cp.course_period_id = srcg.course_period_id
and orcg.title = nrcg.title
and orcg.syear = nrcg.syear
and orcg.school_id = nrcg.school_id
and nrcg.grade_scale_id = cp.grade_scale_id
and not nrcg.grade_scale_id = orcg.grade_scale_id

) as found
where found.student_id = student_report_card_grades.student_id
and found.course_period_id = student_report_card_grades.course_period_id
and found.marking_period_id = student_report_card_grades.marking_period_id
and found.syear = student_report_card_grades.syear
and found.school_id = student_report_card_grades.school_id;


--add extra information into grade records

UPDATE student_report_card_grades
SET weighted_gp = more.weighted_gp,
unweighted_gp = more.unweighted_gp,
grade_letter = more.grade_letter,
course_title = more.course_title,
gp_scale = more.gp_scale,
class_rank = more.does_class_rank

FROM (

SELECT 
  student_report_card_grades.student_id,
  student_report_card_grades.course_period_id,
  student_report_card_grades.marking_period_id,
  report_card_grades.gpa_value AS weighted_gp,
  report_card_grades.unweighted_gp,
  report_card_grades.title AS grade_letter,
  student_report_card_grades.report_card_grade_id,
  courses.title AS course_title,
  course_periods.does_class_rank,
  report_card_grade_scales.gp_scale
FROM
 student_report_card_grades
 INNER JOIN report_card_grades ON (student_report_card_grades.report_card_grade_id=report_card_grades.id)
 LEFT OUTER JOIN report_card_grade_scales ON (report_card_grades.grade_scale_id=report_card_grade_scales.id)
 INNER JOIN course_periods ON (student_report_card_grades.course_period_id=course_periods.course_period_id)
 INNER JOIN courses ON (course_periods.course_id=courses.course_id)
 ) as more
 where student_report_card_grades.student_id = more.student_id
 and student_report_card_grades.course_period_id = more.course_period_id
 and student_report_card_grades.marking_period_id = more.marking_period_id;

--UPDATE student_report_card_grades SET gp_scale = 4 WHERE gp_scale IS NULL;

--update student_report_card_grades with credit information

UPDATE student_report_card_grades set credit_attempted = c.credit
from
(select rcg.student_id, rcg.course_period_id, rcg.marking_period_id, credit.credit from student_report_card_grades rcg

JOIN (select course_period_id, marking_period_id, credit(course_period_id::int, marking_period_id) from student_report_card_grades group by course_period_id, marking_period_id) as credit ON (credit.course_period_id = rcg.course_period_id and credit.marking_period_id = rcg.marking_period_id)) as c
where student_report_card_grades.student_id = c.student_id
and student_report_card_grades.course_period_id = c.course_period_id
and student_report_card_grades.marking_period_id = c.marking_period_id;

UPDATE student_report_card_grades SET credit_earned = credit_attempted WHERE weighted_gp > 0;

--set up student_mp_stats table with all the necessary records.
--TODO:  Update class rank data?
INSERT INTO student_mp_stats (student_id, marking_period_id, sum_weighted_factors, count_weighted_factors, sum_unweighted_factors, count_unweighted_factors)
SELECT student_id, (marking_period_id::text)::int, 
sum(weighted_gp/gp_scale) as sum_weighted_factors, count(course_period_id) as count_weighted_factors, 
sum(unweighted_gp/gp_scale) as sum_unweighted_factors, count(course_period_id) as count_unweighted_factors 
FROM student_report_card_grades where not marking_period_id LIKE 'E%' group by student_id, marking_period_id
;

SELECT calc_gpa_mp(student_id, marking_period_id::text)from student_mp_stats;

SELECT calc_cum_gpa_mp(mp.marking_period_id::text) FROM (SELECT DISTINCT marking_period_id FROM student_mp_stats) as mp;

SELECT set_class_rank_mp(mp.marking_period_id::text) FROM (SELECT DISTINCT marking_period_id FROM student_mp_stats) as mp;


--update student_mp_stats with grade level

UPDATE student_mp_stats SET grade_level_short = eg.short_name

FROM (SELECT sms.student_id, 
	(SELECT short_name FROM enroll_grade WHERE syear = mp.syear AND student_id = sms.student_id ORDER BY start_date DESC LIMIT 1) as short_name, 	     mp.marking_period_id 
	FROM student_mp_stats sms, marking_periods mp 
	WHERE mp.marking_period_id = sms.marking_period_id
	) as eg
WHERE eg.student_id = student_mp_stats.student_id
AND eg.marking_period_id = student_mp_stats.marking_period_id;

--add new trigger

-- Trigger: srcg_mp_stats_update on student_report_card_grades

-- DROP TRIGGER srcg_mp_stats_update ON student_report_card_grades;

CREATE TRIGGER srcg_mp_stats_update
  AFTER INSERT OR UPDATE OR DELETE
  ON student_report_card_grades
  FOR EACH ROW
  EXECUTE PROCEDURE t_update_mp_stats();

--         INTERNATIONALIZATION related changes
-- we need to increase the size of all multi-lingual fields ...
-- suggested increased size below should be enough to support
-- UTF-8 encoding and 3-5 additional languages - but this can
-- be increased further if you need to support more languages!
ALTER TABLE address_field_categories
    ALTER COLUMN title TYPE character varying(1000);
ALTER TABLE address_fields
    ALTER COLUMN title TYPE character varying(1000);
ALTER TABLE student_field_categories
    ALTER COLUMN title TYPE character varying(1000);
ALTER TABLE custom_fields
    ALTER COLUMN title TYPE character varying(1000);
ALTER TABLE people_field_categories
    ALTER COLUMN title TYPE character varying(1000);
ALTER TABLE people_fields
    ALTER COLUMN title TYPE character varying(1000);
ALTER TABLE staff_field_categories
    ALTER COLUMN title TYPE character varying(1000);
ALTER TABLE staff_fields
    ALTER COLUMN title TYPE character varying(1000);
ALTER TABLE report_card_comment_categories
    ALTER COLUMN title TYPE character varying(1000);
ALTER TABLE report_card_comments
    ALTER COLUMN title TYPE character varying(5000);
ALTER TABLE report_card_grades
    ALTER COLUMN title TYPE character varying(100);
ALTER TABLE report_card_grades
    ALTER COLUMN comment TYPE character varying(1000);
ALTER TABLE report_card_grade_scales
    ALTER COLUMN title TYPE character varying(300);
ALTER TABLE report_card_grade_scales
    ALTER COLUMN comment TYPE character varying(1000);

-- Additional profile-exceptions for Resources module
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Resources/Redirect.php?to=doc', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Resources/Redirect.php?to=videohelp', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Resources/Redirect.php?to=forums', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Resources/Redirect.php?to=translate', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Resources/Redirect.php?to=doc', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Resources/Redirect.php?to=videohelp', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Resources/Redirect.php?to=forums', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Resources/Redirect.php?to=translate', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Resources/Redirect.php?to=doc', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Resources/Redirect.php?to=videohelp', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Resources/Redirect.php?to=forums', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Resources/Redirect.php?to=translate', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Resources/Redirect.php?to=doc', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Resources/Redirect.php?to=videohelp', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Resources/Redirect.php?to=forums', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Resources/Redirect.php?to=translate', 'Y', 'Y');