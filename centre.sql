--
-- PostgreSQL database dump
--

SET client_encoding = 'UTF8';
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: SCHEMA public; Type: COMMENT; Schema: -; Owner: postgres
--

COMMENT ON SCHEMA public IS 'Standard public schema';


--
-- Name: plpgsql; Type: PROCEDURAL LANGUAGE; Schema: -; Owner: 
--

CREATE PROCEDURAL LANGUAGE plpgsql;


SET search_path = public, pg_catalog;

--
-- Name: calc_cum_cr_gpa(character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION calc_cum_cr_gpa(character varying, integer) RETURNS integer
    AS $_$DECLARE
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
  and gp_scale > 0 and credit_attempted > 0 and class_rank = 'Y' ) as x group by school_scale) as sms1
    WHERE student_mp_stats.student_id = s_id and student_mp_stats.marking_period_id = mp_id;
  RETURN 1;
END;

$_$
    LANGUAGE plpgsql;


--
-- Name: calc_cum_gpa(character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION calc_cum_gpa(character varying, integer) RETURNS integer
    AS $_$DECLARE
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
$_$
    LANGUAGE plpgsql;


--
-- Name: calc_cum_gpa_mp(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION calc_cum_gpa_mp(character varying) RETURNS integer
    AS $_$DECLARE
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

$_$
    LANGUAGE plpgsql;


--
-- Name: calc_gpa_mp(integer, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION calc_gpa_mp(integer, character varying) RETURNS integer
    AS $_$
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
        sum( case when class_rank = 'Y' THEN weighted_gp*credit_attempted/gp_scale END ) as cr_weighted,
        sum( case when class_rank = 'Y' THEN unweighted_gp*credit_attempted/gp_scale END ) as cr_unweighted,
        sum( case when class_rank = 'Y' THEN credit_attempted END) as cr_credits

        from student_report_card_grades where student_id = s_id
        and marking_period_id = mp_id
         and not gp_scale = 0 and not marking_period_id LIKE 'E%' group by student_id, marking_period_id
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
            sum( case when class_rank = 'Y' THEN weighted_gp*credit_attempted/gp_scale END ) as cr_weighted,
	    sum( case when class_rank = 'Y' THEN unweighted_gp*credit_attempted/gp_scale END ) as cr_unweighted,
            sum(credit_attempted) as gp_credits,
            sum(case when class_rank = 'Y' THEN credit_attempted END) as cr_credits
        from student_report_card_grades srcg join marking_periods mp on (mp.marking_period_id = srcg.marking_period_id) left outer join enroll_grade eg on (eg.student_id = srcg.student_id and eg.syear = mp.syear and eg.school_id = mp.school_id)
        where srcg.student_id = s_id and srcg.marking_period_id = mp_id and not srcg.gp_scale = 0 
        and not srcg.marking_period_id LIKE 'E%' group by srcg.student_id, srcg.marking_period_id, eg.short_name;
  END IF;
  RETURN 0;
END
$_$
    LANGUAGE plpgsql;


--
-- Name: credit(integer, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION credit(integer, character varying) RETURNS numeric
    AS $_$
DECLARE
	course_detail RECORD;
	mp_detail RECORD;
	values RECORD;
	
BEGIN
select * into course_detail from course_periods where course_period_id = $1;
select * into mp_detail from marking_periods where marking_period_id = $2;

IF course_detail.marking_period_id = mp_detail.marking_period_id THEN
	return course_detail.credits;
ELSIF course_detail.mp = 'FY' AND mp_detail.mp_type = 'semester' THEN
	select into values count(*) as mp_count from marking_periods where parent_id = course_detail.marking_period_id group by parent_id;
ELSIF course_detail.mp = 'FY' and mp_detail.mp_type = 'quarter' THEN
	select into values count(*) as mp_count from marking_periods where grandparent_id = course_detail.marking_period_id group by grandparent_id;
ELSIF course_detail.mp = 'SEM' and mp_detail.mp_type = 'quarter' THEN
	select into values count(*) as mp_count from marking_periods where parent_id = course_detail.marking_period_id group by parent_id;
ELSE
	return 0;
END IF;

IF values.mp_count > 0 THEN
	return course_detail.credits/values.mp_count;
ELSE
	return 0;
END IF;

END$_$
    LANGUAGE plpgsql;


--
-- Name: plpgsql_call_handler(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION plpgsql_call_handler() RETURNS language_handler
    AS '$libdir/plpgsql', 'plpgsql_call_handler'
    LANGUAGE c;


--
-- Name: set_class_rank_mp(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION set_class_rank_mp(character varying) RETURNS integer
    AS $_$
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
$_$
    LANGUAGE plpgsql;


--
-- Name: t_update_mp_stats(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION t_update_mp_stats() RETURNS "trigger"
    AS $$
begin

  IF tg_op = 'DELETE' THEN
	perform calc_gpa_mp(OLD.student_id::int, OLD.marking_period_id::varchar);
  ELSE
	--IF tg_op = 'INSERT' THEN
		--we need to do stuff here to gather other information since it's a new record.
	--ELSE
		--if report_card_grade_id changes, then we need to reset gp values
	--	IF NOT NEW.report_card_grade_id = OLD.report_card_grade_id THEN
			--
	perform calc_gpa_mp(NEW.student_id::int, NEW.marking_period_id::varchar);
  END IF;
  return NULL;
end
$$
    LANGUAGE plpgsql;


SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: address; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE address (
    address_id numeric(10,0) NOT NULL,
    house_no numeric(5,0),
    fraction character varying(3),
    letter character varying(2),
    direction character varying(2),
    street character varying(30),
    apt character varying(5),
    zipcode character varying(10),
    plus4 character varying(4),
    city character varying(60),
    state character varying(10),
    mail_street character varying(30),
    mail_city character varying(60),
    mail_state character varying(10),
    mail_zipcode character varying(10),
    address character varying(255),
    mail_address character varying(255),
    phone character varying(30)
);


--
-- Name: address_field_categories; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE address_field_categories (
    id numeric NOT NULL,
    title character varying(1000),
    sort_order numeric,
    residence character(1),
    mailing character(1),
    bus character(1)
);


--
-- Name: address_field_categories_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE address_field_categories_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: address_field_categories_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('address_field_categories_seq', 1, false);


--
-- Name: address_fields; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE address_fields (
    id numeric NOT NULL,
    "type" character varying(10),
    search character varying(1),
    title character varying(1000),
    sort_order numeric,
    select_options character varying(10000),
    category_id numeric,
    system_field character(1),
    required character varying(1),
    default_selection character varying(255)
);


--
-- Name: address_fields_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE address_fields_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: address_fields_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('address_fields_seq', 1, true);


--
-- Name: address_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE address_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: address_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('address_seq', 1, true);


--
-- Name: attendance_calendar; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE attendance_calendar (
    syear numeric(4,0) NOT NULL,
    school_id numeric NOT NULL,
    school_date date NOT NULL,
    minutes numeric,
    block character varying(10),
    calendar_id numeric NOT NULL
);


--
-- Name: attendance_calendars; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE attendance_calendars (
    school_id numeric,
    title character varying(100),
    syear numeric(4,0),
    calendar_id numeric NOT NULL,
    default_calendar character varying(1),
    rollover_id numeric
);


--
-- Name: attendance_code_categories; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE attendance_code_categories (
    id numeric,
    syear numeric(4,0),
    school_id numeric,
    title character varying(255),
    sort_order numeric,
    rollover_id numeric
);


--
-- Name: attendance_code_categories_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE attendance_code_categories_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: attendance_code_categories_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('attendance_code_categories_seq', 1, false);


--
-- Name: attendance_codes; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE attendance_codes (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    title character varying(100),
    short_name character varying(10),
    "type" character varying(10),
    state_code character varying(1),
    default_code character varying(1),
    table_name numeric,
    sort_order numeric
);


--
-- Name: attendance_codes_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE attendance_codes_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: attendance_codes_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('attendance_codes_seq', 21, true);


--
-- Name: attendance_completed; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE attendance_completed (
    staff_id numeric NOT NULL,
    school_date date NOT NULL,
    period_id numeric NOT NULL,
    table_name numeric NOT NULL
);


--
-- Name: attendance_day; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE attendance_day (
    student_id numeric NOT NULL,
    school_date date NOT NULL,
    minutes_present numeric,
    state_value numeric(2,1),
    syear numeric(4,0),
    marking_period_id numeric,
    "comment" character varying(255)
);


--
-- Name: attendance_period; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE attendance_period (
    student_id numeric NOT NULL,
    school_date date NOT NULL,
    period_id numeric NOT NULL,
    attendance_code numeric,
    attendance_teacher_code numeric,
    attendance_reason character varying(100),
    "admin" character varying(1),
    course_period_id numeric,
    marking_period_id numeric,
    "comment" character varying(100)
);


--
-- Name: calendar_events; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE calendar_events (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    school_date date,
    title character varying(50),
    description character varying(500)
);


--
-- Name: calendar_events_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE calendar_events_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: calendar_events_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('calendar_events_seq', 25, true);


--
-- Name: calendars_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE calendars_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: calendars_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('calendars_seq', 4, true);


--
-- Name: config; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE config (
    title character varying(100),
    syear numeric(4,0),
    "login" character varying(3)
);


--
-- Name: course_periods; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE course_periods (
    syear numeric(4,0) NOT NULL,
    school_id numeric NOT NULL,
    course_period_id numeric NOT NULL,
    course_id numeric NOT NULL,
    title character varying(100),
    short_name character varying(25),
    period_id numeric,
    mp character varying(3),
    marking_period_id numeric,
    teacher_id numeric,
    room character varying(10),
    total_seats numeric,
    filled_seats numeric,
    does_attendance character varying(255),
    does_honor_roll character varying(1),
    does_class_rank character varying(1),
    gender_restriction character varying(1),
    house_restriction character varying(1),
    availability numeric,
    parent_id numeric,
    days character varying(7),
    calendar_id numeric,
    half_day character varying(1),
    does_breakoff character varying(1),
    rollover_id numeric,
    grade_scale_id numeric,
    credits numeric
);


--
-- Name: courses; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE courses (
    syear numeric(4,0) NOT NULL,
    course_id numeric NOT NULL,
    subject_id numeric NOT NULL,
    school_id numeric NOT NULL,
    grade_level numeric,
    title character varying(100),
    short_name character varying(25),
    rollover_id numeric
);


--
-- Name: course_details; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW course_details AS
    SELECT cp.school_id, cp.syear, cp.marking_period_id, cp.period_id, c.subject_id, cp.course_id, cp.course_period_id, cp.teacher_id, c.title AS course_title, cp.title AS cp_title, cp.grade_scale_id, cp.mp, cp.credits FROM course_periods cp, courses c WHERE (cp.course_id = c.course_id);


--
-- Name: course_periods_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE course_periods_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: course_periods_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('course_periods_seq', 1, true);


--
-- Name: course_subjects; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE course_subjects (
    syear numeric(4,0),
    school_id numeric,
    subject_id numeric NOT NULL,
    title character varying(100),
    short_name character varying(25),
    sort_order numeric,
    rollover_id numeric
);


--
-- Name: course_subjects_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE course_subjects_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: course_subjects_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('course_subjects_seq', 1, true);


--
-- Name: courses_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE courses_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: courses_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('courses_seq', 1, true);


--
-- Name: custom; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE custom (
    student_id numeric NOT NULL
);


--
-- Name: custom_fields; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE custom_fields (
    id numeric NOT NULL,
    "type" character varying(10),
    search character varying(1),
    title character varying(1000),
    sort_order numeric,
    select_options character varying(10000),
    category_id numeric,
    system_field character(1),
    required character varying(1),
    default_selection character varying(255)
);


--
-- Name: custom_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE custom_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: custom_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('custom_seq', 1, true);


--
-- Name: eligibility; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE eligibility (
    student_id numeric,
    syear numeric(4,0),
    school_date date,
    period_id numeric,
    eligibility_code character varying(20),
    course_period_id numeric
);


--
-- Name: eligibility_activities; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE eligibility_activities (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    title character varying(100),
    start_date date,
    end_date date
);


--
-- Name: eligibility_activities_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE eligibility_activities_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: eligibility_activities_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('eligibility_activities_seq', 9, true);


--
-- Name: eligibility_completed; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE eligibility_completed (
    staff_id numeric NOT NULL,
    school_date date NOT NULL,
    period_id numeric NOT NULL
);


--
-- Name: school_gradelevels; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE school_gradelevels (
    id numeric NOT NULL,
    school_id numeric NOT NULL,
    short_name character varying(2),
    title character varying(50),
    next_grade_id numeric,
    sort_order numeric
);


--
-- Name: student_enrollment; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE student_enrollment (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    student_id numeric,
    grade_id numeric,
    start_date date,
    end_date date,
    enrollment_code numeric,
    drop_code numeric,
    next_school numeric,
    calendar_id numeric,
    last_school numeric
);


--
-- Name: enroll_grade; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW enroll_grade AS
    SELECT e.id, e.syear, e.school_id, e.student_id, e.start_date, e.end_date, sg.short_name, sg.title FROM student_enrollment e, school_gradelevels sg WHERE (e.grade_id = sg.id);


--
-- Name: VIEW enroll_grade; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON VIEW enroll_grade IS 'Provides enrollment dates and grade levels';


--
-- Name: food_service_accounts; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE food_service_accounts (
    account_id numeric NOT NULL,
    balance numeric(9,2) NOT NULL,
    transaction_id numeric
);


--
-- Name: food_service_categories; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE food_service_categories (
    category_id numeric NOT NULL,
    school_id numeric NOT NULL,
    menu_id numeric NOT NULL,
    title character varying(25),
    sort_order numeric
);


--
-- Name: food_service_categories_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE food_service_categories_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: food_service_categories_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('food_service_categories_seq', 1, true);


--
-- Name: food_service_items; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE food_service_items (
    item_id numeric NOT NULL,
    school_id numeric NOT NULL,
    short_name character varying(25),
    sort_order numeric,
    description character varying(25),
    icon character varying(50),
    price numeric(9,2) NOT NULL,
    price_reduced numeric(9,2),
    price_free numeric(9,2),
    price_staff numeric(9,2) NOT NULL
);


--
-- Name: food_service_items_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE food_service_items_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: food_service_items_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('food_service_items_seq', 4, true);


--
-- Name: food_service_menu_items; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE food_service_menu_items (
    menu_item_id numeric NOT NULL,
    school_id numeric NOT NULL,
    menu_id numeric NOT NULL,
    item_id numeric NOT NULL,
    category_id numeric,
    sort_order numeric,
    does_count character varying(1)
);


--
-- Name: food_service_menu_items_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE food_service_menu_items_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: food_service_menu_items_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('food_service_menu_items_seq', 4, true);


--
-- Name: food_service_menus; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE food_service_menus (
    menu_id numeric NOT NULL,
    school_id numeric NOT NULL,
    title character varying(25) NOT NULL,
    sort_order numeric
);


--
-- Name: food_service_menus_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE food_service_menus_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: food_service_menus_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('food_service_menus_seq', 1, true);


--
-- Name: food_service_staff_accounts; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE food_service_staff_accounts (
    staff_id numeric NOT NULL,
    status character varying(25),
    barcode character varying(50),
    balance numeric(9,2) NOT NULL,
    transaction_id numeric
);


--
-- Name: food_service_staff_transaction_items; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE food_service_staff_transaction_items (
    item_id numeric NOT NULL,
    transaction_id numeric NOT NULL,
    amount numeric(9,2),
    short_name character varying(25),
    description character varying(50)
);


--
-- Name: food_service_staff_transactions; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE food_service_staff_transactions (
    transaction_id numeric NOT NULL,
    staff_id numeric NOT NULL,
    school_id numeric,
    syear numeric(4,0),
    balance numeric(9,2),
    "timestamp" timestamp(0) without time zone,
    short_name character varying(25),
    description character varying(50),
    seller_id numeric
);


--
-- Name: food_service_staff_transactions_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE food_service_staff_transactions_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: food_service_staff_transactions_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('food_service_staff_transactions_seq', 1, true);


--
-- Name: food_service_student_accounts; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE food_service_student_accounts (
    student_id numeric NOT NULL,
    account_id numeric NOT NULL,
    discount character varying(25),
    status character varying(25),
    barcode character varying(50)
);


--
-- Name: food_service_transaction_items; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE food_service_transaction_items (
    item_id numeric NOT NULL,
    transaction_id numeric NOT NULL,
    amount numeric(9,2),
    discount character varying(25),
    short_name character varying(25),
    description character varying(50)
);


--
-- Name: food_service_transactions; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE food_service_transactions (
    transaction_id numeric NOT NULL,
    account_id numeric NOT NULL,
    student_id numeric,
    school_id numeric,
    syear numeric(4,0),
    discount character varying(25),
    balance numeric(9,2),
    "timestamp" timestamp(0) without time zone,
    short_name character varying(25),
    description character varying(50),
    seller_id numeric
);


--
-- Name: food_service_transactions_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE food_service_transactions_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: food_service_transactions_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('food_service_transactions_seq', 1, true);


--
-- Name: gradebook_assignment_types; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE gradebook_assignment_types (
    assignment_type_id numeric NOT NULL,
    staff_id numeric,
    course_id numeric,
    title character varying(100),
    final_grade_percent numeric(6,5),
    sort_order numeric,
    color character varying(30)
);


--
-- Name: gradebook_assignment_types_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE gradebook_assignment_types_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: gradebook_assignment_types_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('gradebook_assignment_types_seq', 1, false);


--
-- Name: gradebook_assignments; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE gradebook_assignments (
    assignment_id numeric NOT NULL,
    staff_id numeric,
    marking_period_id numeric,
    course_period_id numeric,
    course_id numeric,
    assignment_type_id numeric NOT NULL,
    title character varying(100),
    assigned_date date,
    due_date date,
    points numeric,
    description character varying(1000)
);


--
-- Name: gradebook_assignments_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE gradebook_assignments_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: gradebook_assignments_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('gradebook_assignments_seq', 1, true);


--
-- Name: gradebook_grades; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE gradebook_grades (
    student_id numeric NOT NULL,
    period_id numeric,
    course_period_id numeric NOT NULL,
    assignment_id numeric NOT NULL,
    points numeric(6,2),
    "comment" character varying(100)
);


--
-- Name: grades_completed; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE grades_completed (
    staff_id numeric NOT NULL,
    marking_period_id character varying(10) NOT NULL,
    course_period_id numeric NOT NULL
);


--
-- Name: history_marking_periods; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE history_marking_periods (
    parent_id integer,
    mp_type character(20),
    name character(30),
    short_name character varying(10),
    post_end_date date,
    school_id integer,
    syear integer,
    marking_period_id integer DEFAULT nextval(('marking_period_seq'::text)::regclass) NOT NULL
);


--
-- Name: lunch_period; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE lunch_period (
    student_id numeric NOT NULL,
    school_date date NOT NULL,
    period_id numeric NOT NULL,
    attendance_code numeric,
    attendance_teacher_code numeric,
    attendance_reason character varying(100),
    "admin" character varying(1),
    course_period_id numeric,
    marking_period_id numeric,
    "comment" character varying(100),
    table_name numeric
);


--
-- Name: marking_period_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE marking_period_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: marking_period_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('marking_period_seq', 33, true);


--
-- Name: school_marking_periods; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE school_marking_periods (
    marking_period_id numeric NOT NULL,
    syear numeric(4,0),
    mp character varying(3) NOT NULL,
    school_id numeric,
    parent_id numeric,
    title character varying(50),
    short_name character varying(10),
    sort_order numeric,
    start_date date NOT NULL,
    end_date date NOT NULL,
    post_start_date date,
    post_end_date date,
    does_grades character varying(1),
    does_exam character varying(1),
    does_comments character varying(1),
    rollover_id numeric
);


--
-- Name: marking_periods; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW marking_periods AS
    SELECT school_marking_periods.marking_period_id, 'Centre' AS mp_source, school_marking_periods.syear, school_marking_periods.school_id, CASE WHEN ((school_marking_periods.mp)::text = 'FY'::text) THEN 'year'::text WHEN ((school_marking_periods.mp)::text = 'SEM'::text) THEN 'semester'::text WHEN ((school_marking_periods.mp)::text = 'QTR'::text) THEN 'quarter'::text ELSE NULL::text END AS mp_type, school_marking_periods.title, school_marking_periods.short_name, school_marking_periods.sort_order, CASE WHEN (school_marking_periods.parent_id > (0)::numeric) THEN school_marking_periods.parent_id ELSE (-1)::numeric END AS parent_id, CASE WHEN ((SELECT smp.parent_id FROM school_marking_periods smp WHERE (smp.marking_period_id = school_marking_periods.parent_id)) > (0)::numeric) THEN (SELECT smp.parent_id FROM school_marking_periods smp WHERE (smp.marking_period_id = school_marking_periods.parent_id)) ELSE (-1)::numeric END AS grandparent_id, school_marking_periods.start_date, school_marking_periods.end_date, school_marking_periods.post_start_date, school_marking_periods.post_end_date, school_marking_periods.does_grades, school_marking_periods.does_exam, school_marking_periods.does_comments FROM school_marking_periods UNION SELECT history_marking_periods.marking_period_id, 'History' AS mp_source, history_marking_periods.syear, history_marking_periods.school_id, history_marking_periods.mp_type, history_marking_periods.name AS title, history_marking_periods.short_name, NULL::"unknown" AS sort_order, history_marking_periods.parent_id, -1 AS grandparent_id, NULL::"unknown" AS start_date, history_marking_periods.post_end_date AS end_date, NULL::"unknown" AS post_start_date, history_marking_periods.post_end_date, 'Y' AS does_grades, NULL::"unknown" AS does_exam, NULL::"unknown" AS does_comments FROM history_marking_periods;


--
-- Name: people; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE people (
    person_id numeric(10,0) NOT NULL,
    last_name character varying(50) NOT NULL,
    first_name character varying(50) NOT NULL,
    middle_name character varying(50)
);


--
-- Name: people_field_categories; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE people_field_categories (
    id numeric NOT NULL,
    title character varying(1000),
    sort_order numeric,
    custody character(1),
    emergency character(1)
);


--
-- Name: people_field_categories_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE people_field_categories_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: people_field_categories_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('people_field_categories_seq', 1, false);


--
-- Name: people_fields; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE people_fields (
    id numeric NOT NULL,
    "type" character varying(10),
    search character varying(1),
    title character varying(1000),
    sort_order numeric,
    select_options character varying(10000),
    category_id numeric,
    system_field character(1),
    required character varying(1),
    default_selection character varying(255)
);


--
-- Name: people_fields_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE people_fields_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: people_fields_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('people_fields_seq', 1, true);


--
-- Name: people_join_contacts; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE people_join_contacts (
    id numeric NOT NULL,
    person_id numeric,
    title character varying(100),
    value character varying(100)
);


--
-- Name: people_join_contacts_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE people_join_contacts_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: people_join_contacts_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('people_join_contacts_seq', 1, true);


--
-- Name: people_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE people_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: people_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('people_seq', 1, true);


--
-- Name: portal_notes; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE portal_notes (
    id numeric NOT NULL,
    school_id numeric,
    syear numeric(4,0),
    title character varying(255),
    content character varying(5000),
    sort_order numeric,
    published_user numeric,
    published_date timestamp(0) without time zone,
    start_date date,
    end_date date,
    published_profiles character varying(255)
);


--
-- Name: portal_notes_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE portal_notes_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: portal_notes_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('portal_notes_seq', 1, true);


--
-- Name: profile_exceptions; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE profile_exceptions (
    profile_id numeric,
    modname character varying(255),
    can_use character varying(1),
    can_edit character varying(1)
);


--
-- Name: program_config; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE program_config (
    syear numeric(4,0),
    school_id numeric,
    program character varying(255),
    title character varying(100),
    value character varying(100)
);


--
-- Name: program_user_config; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE program_user_config (
    user_id numeric NOT NULL,
    program character varying(255),
    title character varying(100),
    value character varying(100)
);


--
-- Name: report_card_comment_categories; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE report_card_comment_categories (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    course_id numeric,
    sort_order numeric,
    title character varying(1000),
    rollover_id numeric,
    color character varying(30)
);


--
-- Name: report_card_comment_categories_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE report_card_comment_categories_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: report_card_comment_categories_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('report_card_comment_categories_seq', 1, true);


--
-- Name: report_card_comment_code_scales; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE report_card_comment_code_scales (
    id numeric NOT NULL,
    school_id numeric NOT NULL,
    title character varying(25),
    "comment" character varying(100),
    sort_order numeric,
    rollover_id numeric
);


--
-- Name: report_card_comment_code_scales_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE report_card_comment_code_scales_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: report_card_comment_code_scales_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('report_card_comment_code_scales_seq', 1, false);


--
-- Name: report_card_comment_codes; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE report_card_comment_codes (
    id numeric NOT NULL,
    school_id numeric NOT NULL,
    scale_id numeric NOT NULL,
    title character varying(5) NOT NULL,
    short_name character varying(100),
    "comment" character varying(100),
    sort_order numeric
);


--
-- Name: report_card_comment_codes_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE report_card_comment_codes_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: report_card_comment_codes_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('report_card_comment_codes_seq', 1, false);


--
-- Name: report_card_comments; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE report_card_comments (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    course_id numeric,
    category_id numeric,
    scale_id numeric,
    sort_order numeric,
    title character varying(5000)
);


--
-- Name: report_card_comments_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE report_card_comments_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: report_card_comments_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('report_card_comments_seq', 9, true);


--
-- Name: report_card_grade_scales; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE report_card_grade_scales (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric NOT NULL,
    title character varying(300),
    "comment" character varying(1000),
    hhr_gpa_value numeric(4,2),
    hr_gpa_value numeric(4,2),
    sort_order numeric,
    rollover_id numeric,
    gp_scale numeric(10,3)
);


--
-- Name: report_card_grade_scales_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE report_card_grade_scales_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: report_card_grade_scales_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('report_card_grade_scales_seq', 4, true);


--
-- Name: report_card_grades; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE report_card_grades (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    title character varying(100),
    sort_order numeric,
    gpa_value numeric(4,2),
    break_off numeric,
    "comment" character varying(1000),
    grade_scale_id numeric,
    unweighted_gp numeric(4,2)
);


--
-- Name: report_card_grades_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE report_card_grades_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: report_card_grades_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('report_card_grades_seq', 45, true);


--
-- Name: schedule; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE schedule (
    syear numeric(4,0) NOT NULL,
    school_id numeric,
    student_id numeric NOT NULL,
    start_date date NOT NULL,
    end_date date,
    modified_date date,
    modified_by character varying(255),
    course_id numeric NOT NULL,
    course_period_id numeric NOT NULL,
    mp character varying(3),
    marking_period_id numeric,
    scheduler_lock character varying(1),
    id numeric
);


--
-- Name: schedule_requests; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE schedule_requests (
    syear numeric(4,0),
    school_id numeric,
    request_id numeric NOT NULL,
    student_id numeric,
    subject_id numeric,
    course_id numeric,
    marking_period_id numeric,
    priority numeric,
    with_teacher_id numeric,
    not_teacher_id numeric,
    with_period_id numeric,
    not_period_id numeric
);


--
-- Name: schedule_requests_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE schedule_requests_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: schedule_requests_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('schedule_requests_seq', 1, true);


--
-- Name: schedule_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE schedule_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: schedule_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('schedule_seq', 1, false);


--
-- Name: school_gradelevels_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE school_gradelevels_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: school_gradelevels_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('school_gradelevels_seq', 9, true);


--
-- Name: school_periods; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE school_periods (
    period_id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    sort_order numeric,
    title character varying(100),
    short_name character varying(10),
    length numeric,
    start_time character varying(10),
    end_time character varying(10),
    block character varying(10),
    attendance character varying(1),
    rollover_id numeric
);


--
-- Name: school_periods_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE school_periods_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: school_periods_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('school_periods_seq', 33, true);


--
-- Name: schools; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE schools (
    syear numeric(4,0) NOT NULL,
    id numeric NOT NULL,
    title character varying(100),
    address character varying(100),
    city character varying(100),
    state character varying(10),
    zipcode character varying(10),
    phone character varying(30),
    principal character varying(100),
    www_address character varying(100),
    school_number character varying(50),
    short_name character varying(25),
    reporting_gp_scale numeric(10,3)
);


--
-- Name: schools_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE schools_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: schools_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('schools_seq', 1, true);


--
-- Name: staff; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE staff (
    syear numeric(4,0),
    staff_id numeric NOT NULL,
    current_school_id numeric,
    title character varying(5),
    first_name character varying(100) NOT NULL,
    last_name character varying(100) NOT NULL,
    middle_name character varying(100),
    name_suffix character varying(3),
    username character varying(100),
    "password" character varying(100),
    phone character varying(100),
    email character varying(100),
    profile character varying(30),
    homeroom character varying(5),
    schools character varying(255),
    last_login timestamp(0) without time zone,
    failed_login numeric,
    profile_id numeric,
    rollover_id numeric
);


--
-- Name: staff_exceptions; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE staff_exceptions (
    user_id numeric NOT NULL,
    modname character varying(255),
    can_use character varying(1),
    can_edit character varying(1)
);


--
-- Name: staff_field_categories; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE staff_field_categories (
    id numeric NOT NULL,
    title character varying(1000),
    sort_order numeric,
    columns numeric(4,0),
    include character varying(100),
    "admin" character(1),
    teacher character(1),
    parent character(1),
    "none" character(1)
);


--
-- Name: staff_field_categories_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE staff_field_categories_seq
    START WITH 3
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: staff_field_categories_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('staff_field_categories_seq', 3, false);


--
-- Name: staff_fields; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE staff_fields (
    id numeric NOT NULL,
    "type" character varying(10),
    search character varying(1),
    title character varying(1000),
    sort_order numeric,
    select_options character varying(10000),
    category_id numeric,
    system_field character(1),
    required character varying(1),
    default_selection character varying(255)
);


--
-- Name: staff_fields_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE staff_fields_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: staff_fields_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('staff_fields_seq', 1, true);


--
-- Name: staff_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE staff_seq
    START WITH 4
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: staff_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('staff_seq', 4, false);


--
-- Name: student_eligibility_activities; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE student_eligibility_activities (
    syear numeric(4,0),
    student_id numeric,
    activity_id numeric
);


--
-- Name: student_enrollment_codes; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE student_enrollment_codes (
    id numeric,
    syear numeric(4,0),
    title character varying(100),
    short_name character varying(10),
    "type" character varying(4),
    default_code character varying(1),
    sort_order numeric
);


--
-- Name: student_enrollment_codes_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE student_enrollment_codes_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: student_enrollment_codes_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('student_enrollment_codes_seq', 18, true);


--
-- Name: student_enrollment_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE student_enrollment_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: student_enrollment_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('student_enrollment_seq', 3, true);


--
-- Name: student_field_categories; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE student_field_categories (
    id numeric NOT NULL,
    title character varying(1000),
    sort_order numeric,
    columns numeric(4,0),
    include character varying(100)
);


--
-- Name: student_field_categories_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE student_field_categories_seq
    START WITH 5
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: student_field_categories_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('student_field_categories_seq', 5, false);


--
-- Name: student_gpa_calculated; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE student_gpa_calculated (
    student_id numeric,
    marking_period_id numeric,
    mp character varying(4),
    gpa numeric,
    weighted_gpa numeric,
    unweighted_gpa numeric,
    class_rank numeric
);


--
-- Name: student_gpa_running; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE student_gpa_running (
    student_id numeric,
    marking_period_id numeric,
    gpa_points numeric,
    gpa_points_weighted numeric,
    divisor numeric
);


--
-- Name: student_medical; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE student_medical (
    id numeric NOT NULL,
    student_id numeric,
    "type" character varying(25),
    medical_date date,
    comments character varying(100)
);


--
-- Name: student_medical_alerts; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE student_medical_alerts (
    id numeric NOT NULL,
    student_id numeric,
    title character varying(100)
);


--
-- Name: student_medical_alerts_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE student_medical_alerts_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: student_medical_alerts_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('student_medical_alerts_seq', 1, false);


--
-- Name: student_medical_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE student_medical_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: student_medical_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('student_medical_seq', 1, false);


--
-- Name: student_medical_visits; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE student_medical_visits (
    id numeric NOT NULL,
    student_id numeric,
    school_date date,
    time_in character varying(20),
    time_out character varying(20),
    reason character varying(100),
    result character varying(100),
    comments character varying(255)
);


--
-- Name: student_medical_visits_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE student_medical_visits_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: student_medical_visits_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('student_medical_visits_seq', 1, false);


--
-- Name: student_mp_comments; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE student_mp_comments (
    student_id numeric NOT NULL,
    syear numeric(4,0) NOT NULL,
    marking_period_id numeric NOT NULL,
    "comment" text
);


--
-- Name: student_mp_stats; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE student_mp_stats (
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
    comments character varying(75)
);


--
-- Name: student_report_card_comments; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE student_report_card_comments (
    syear numeric(4,0) NOT NULL,
    school_id numeric,
    student_id numeric NOT NULL,
    course_period_id numeric NOT NULL,
    report_card_comment_id numeric NOT NULL,
    "comment" character varying(5),
    marking_period_id character varying(10) NOT NULL
);


--
-- Name: student_report_card_grades; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE student_report_card_grades (
    syear numeric(4,0),
    school_id numeric,
    student_id numeric NOT NULL,
    course_period_id numeric,
    report_card_grade_id numeric,
    report_card_comment_id numeric,
    "comment" character varying(255),
    grade_percent numeric(4,1),
    marking_period_id character varying(10) NOT NULL,
    grade_letter character varying(5),
    weighted_gp numeric,
    unweighted_gp numeric,
    gp_scale numeric,
    credit_attempted numeric,
    credit_earned numeric,
    credit_category character varying(10),
    course_title character varying(100),
    id integer DEFAULT nextval(('student_report_card_grades_seq'::text)::regclass) NOT NULL,
    school character varying(255),
    class_rank character varying(1)
);


--
-- Name: student_report_card_grades_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE student_report_card_grades_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: student_report_card_grades_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('student_report_card_grades_seq', 1, false);


--
-- Name: student_test_categories; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE student_test_categories (
    id serial NOT NULL,
    test character varying(25),
    category character varying(40)
);


--
-- Name: student_test_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval(pg_catalog.pg_get_serial_sequence('student_test_categories', 'id'), 1, false);


--
-- Name: student_test_scores; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE student_test_scores (
    id serial NOT NULL,
    student_id integer,
    test_category_id integer,
    score character varying(25),
    test_date date
);


--
-- Name: student_test_scores_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval(pg_catalog.pg_get_serial_sequence('student_test_scores', 'id'), 1, false);


--
-- Name: students; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE students (
    student_id numeric NOT NULL,
    last_name character varying(50) NOT NULL,
    first_name character varying(50) NOT NULL,
    middle_name character varying(50),
    name_suffix character varying(3),
    username character varying(100),
    "password" character varying(100),
    last_login timestamp(0) without time zone,
    failed_login numeric,
    custom_200000000 character varying(255),
    custom_200000001 character varying(255),
    custom_200000002 character varying(255),
    custom_200000003 character varying(255),
    custom_200000004 date,
    custom_200000005 character varying(255),
    custom_200000006 character varying(255),
    custom_200000007 character varying(255),
    custom_200000008 character varying(255),
    custom_200000009 character varying(2052),
    custom_200000010 character(1),
    custom_200000011 character varying(2052)
);


--
-- Name: students_join_address; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE students_join_address (
    id numeric(10,0) NOT NULL,
    student_id numeric NOT NULL,
    address_id numeric(10,0) NOT NULL,
    contact_seq numeric(10,0),
    gets_mail character varying(1),
    primary_residence character varying(1),
    legal_residence character varying(1),
    am_bus character varying(1),
    pm_bus character varying(1),
    mailing character varying(1),
    residence character varying(1),
    bus character varying(1),
    bus_pickup character varying(1),
    bus_dropoff character varying(1)
);


--
-- Name: students_join_address_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE students_join_address_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: students_join_address_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('students_join_address_seq', 1, true);


--
-- Name: students_join_people; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE students_join_people (
    id numeric(10,0) NOT NULL,
    student_id numeric NOT NULL,
    person_id numeric(10,0) NOT NULL,
    address_id numeric,
    custody character varying(1),
    emergency character varying(1),
    student_relation character varying(100)
);


--
-- Name: students_join_people_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE students_join_people_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: students_join_people_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('students_join_people_seq', 1, true);


--
-- Name: students_join_users; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE students_join_users (
    student_id numeric NOT NULL,
    staff_id numeric NOT NULL
);


--
-- Name: students_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE students_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: students_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('students_seq', 1, true);


--
-- Name: transcript_grades; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW transcript_grades AS
    SELECT mp.syear, mp.school_id, mp.marking_period_id, mp.mp_type, mp.short_name, mp.parent_id, mp.grandparent_id, (SELECT mp2.end_date FROM (student_report_card_grades JOIN marking_periods mp2 ON (((mp2.marking_period_id)::text = (student_report_card_grades.marking_period_id)::text))) WHERE (((student_report_card_grades.student_id = (sms.student_id)::numeric) AND (((student_report_card_grades.marking_period_id)::text = (mp.parent_id)::text) OR ((student_report_card_grades.marking_period_id)::text = (mp.grandparent_id)::text))) AND ((student_report_card_grades.course_title)::text = (srcg.course_title)::text)) ORDER BY mp2.end_date LIMIT 1) AS parent_end_date, mp.end_date, sms.student_id, (sms.cum_weighted_factor * schools.reporting_gp_scale) AS cum_weighted_gpa, (sms.cum_unweighted_factor * schools.reporting_gp_scale) AS cum_unweighted_gpa, sms.cum_rank, sms.mp_rank, sms.class_size, ((sms.sum_weighted_factors / sms.count_weighted_factors) * schools.reporting_gp_scale) AS weighted_gpa, ((sms.sum_unweighted_factors / sms.count_unweighted_factors) * schools.reporting_gp_scale) AS unweighted_gpa, sms.grade_level_short, srcg."comment", srcg.grade_percent, srcg.grade_letter, srcg.weighted_gp, srcg.unweighted_gp, srcg.gp_scale, srcg.credit_attempted, srcg.credit_earned, srcg.course_title, srcg.school AS school_name, schools.reporting_gp_scale AS school_scale, ((sms.cr_weighted_factors / (sms.count_cr_factors)::numeric) * schools.reporting_gp_scale) AS cr_weighted_gpa, ((sms.cr_unweighted_factors / (sms.count_cr_factors)::numeric) * schools.reporting_gp_scale) AS cr_unweighted_gpa, (sms.cum_cr_weighted_factor * schools.reporting_gp_scale) AS cum_cr_weighted_gpa, (sms.cum_cr_unweighted_factor * schools.reporting_gp_scale) AS cum_cr_unweighted_gpa, srcg.class_rank FROM (((marking_periods mp JOIN student_report_card_grades srcg ON (((mp.marking_period_id)::text = (srcg.marking_period_id)::text))) JOIN student_mp_stats sms ON ((((sms.marking_period_id)::numeric = mp.marking_period_id) AND ((sms.student_id)::numeric = srcg.student_id)))) JOIN schools ON ((mp.school_id = schools.id)));


--
-- Name: user_profiles; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_profiles (
    id numeric,
    profile character varying(30),
    title character varying(100)
);


--
-- Name: user_profiles_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE user_profiles_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: user_profiles_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('user_profiles_seq', 3, true);


--
-- Data for Name: address; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO address (address_id, house_no, fraction, letter, direction, street, apt, zipcode, plus4, city, state, mail_street, mail_city, mail_state, mail_zipcode, address, mail_address, phone) VALUES (0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'No Address', NULL, NULL);


--
-- Data for Name: address_field_categories; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: address_fields; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: attendance_calendar; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-08-21', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-08-24', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-08-25', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-08-26', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-08-27', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-08-28', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-08-31', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-01', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-02', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-03', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-04', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-07', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-08', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-09', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-10', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-11', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-14', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-15', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-16', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-17', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-18', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-21', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-22', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-23', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-24', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-25', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-28', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-29', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-09-30', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-01', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-02', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-05', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-06', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-07', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-08', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-09', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-12', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-13', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-14', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-15', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-16', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-19', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-20', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-21', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-22', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-23', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-26', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-27', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-28', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-29', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-10-30', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-02', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-03', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-04', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-05', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-06', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-09', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-10', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-11', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-12', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-13', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-16', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-17', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-18', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-19', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-20', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-23', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-24', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-25', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-26', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-27', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-11-30', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-01', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-02', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-03', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-04', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-07', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-08', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-09', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-10', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-11', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-14', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-15', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-16', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-17', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-18', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-21', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-22', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-23', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-24', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-25', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-28', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-29', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-30', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2009-12-31', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-01', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-04', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-05', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-06', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-07', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-08', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-11', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-12', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-13', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-14', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-15', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-18', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-19', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-20', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-21', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-22', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-25', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-26', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-27', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-28', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-01-29', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-01', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-02', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-03', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-04', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-05', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-08', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-09', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-10', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-11', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-12', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-15', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-16', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-17', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-18', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-19', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-22', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-23', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-24', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-25', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-02-26', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-01', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-02', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-03', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-04', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-05', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-08', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-09', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-10', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-11', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-12', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-15', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-16', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-17', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-18', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-19', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-22', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-23', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-24', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-25', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-26', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-29', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-30', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-03-31', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-01', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-02', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-05', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-06', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-07', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-08', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-09', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-12', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-13', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-14', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-15', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-16', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-19', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-20', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-21', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-22', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-23', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-26', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-27', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-28', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-29', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-04-30', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-03', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-04', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-05', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-06', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-07', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-10', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-11', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-12', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-13', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-14', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-17', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-18', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-19', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-20', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-21', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-24', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-25', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-26', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-27', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-28', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-05-31', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-06-01', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-06-02', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-06-03', 999, NULL, 1);
INSERT INTO attendance_calendar (syear, school_id, school_date, minutes, block, calendar_id) VALUES (2009, 1, '2010-06-04', 999, NULL, 1);


--
-- Data for Name: attendance_calendars; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO attendance_calendars (school_id, title, syear, calendar_id, default_calendar, rollover_id) VALUES (1, 'Main', 2009, 1, 'Y', NULL);


--
-- Data for Name: attendance_code_categories; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: attendance_codes; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (1, 2007, 1, 'Absent', 'A', 'teacher', 'A', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (2, 2007, 1, 'Vacation', 'V', 'official', 'A', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (3, 2007, 1, 'Present', 'P', 'teacher', 'P', 'Y', 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (4, 2007, 1, 'Tardy', 'T', 'teacher', 'P', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (5, 2007, 1, 'Less than 5 minutes late', 'L', 'teacher', 'P', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (6, 2007, 1, 'Excused Absence', 'E', 'official', 'A', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (7, 2007, 1, 'Sick', 'S', 'official', 'A', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (8, 2008, 1, 'Absent', 'A', 'teacher', 'A', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (9, 2008, 1, 'Vacation', 'V', 'official', 'A', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (10, 2008, 1, 'Present', 'P', 'teacher', 'P', 'Y', 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (11, 2008, 1, 'Tardy', 'T', 'teacher', 'P', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (12, 2008, 1, 'Less than 5 minutes late', 'L', 'teacher', 'P', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (13, 2008, 1, 'Excused Absence', 'E', 'official', 'A', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (14, 2008, 1, 'Sick', 'S', 'official', 'A', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (15, 2009, 1, 'Absent', 'A', 'teacher', 'A', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (16, 2009, 1, 'Vacation', 'V', 'official', 'A', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (17, 2009, 1, 'Present', 'P', 'teacher', 'P', 'Y', 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (18, 2009, 1, 'Tardy', 'T', 'teacher', 'P', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (19, 2009, 1, 'Less than 5 minutes late', 'L', 'teacher', 'P', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (20, 2009, 1, 'Excused Absence', 'E', 'official', 'A', NULL, 0, NULL);
INSERT INTO attendance_codes (id, syear, school_id, title, short_name, "type", state_code, default_code, table_name, sort_order) VALUES (21, 2009, 1, 'Sick', 'S', 'official', 'A', NULL, 0, NULL);


--
-- Data for Name: attendance_completed; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: attendance_day; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: attendance_period; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: calendar_events; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (1, 2007, 1, '2008-04-07', 'No School', 'Spring break');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (2, 2007, 1, '2008-04-08', 'No School', 'Spring break');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (3, 2007, 1, '2008-04-09', 'No School', 'Spring break');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (4, 2007, 1, '2008-04-10', 'No School', 'Spring break');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (5, 2007, 1, '2008-04-11', 'No School', 'Spring break');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (6, 2007, 1, '2008-05-02', 'Early Dismissal', 'Staff development');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (7, 2007, 1, '2008-06-05', 'Last Day Of School', 'Unless snow days are needed');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (8, 2007, 1, '2007-12-24', 'No School', 'Christmas break');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (9, 2007, 1, '2007-12-25', 'No School', 'Merry Christmas!');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (10, 2007, 1, '2007-12-26', 'No School', 'Christmas break');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (11, 2007, 1, '2007-12-27', 'No School', 'Christmas break');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (12, 2007, 1, '2007-12-28', 'No School', 'Christmas break');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (13, 2007, 1, '2007-12-31', 'No School', 'Christmas break');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (14, 2007, 1, '2008-01-01', 'No School', 'Christmas break');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (15, 2007, 1, '2007-08-31', 'Early Dismissal', 'Staff development');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (16, 2007, 1, '2007-09-03', 'No School', 'Labor Day');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (17, 2007, 1, '2007-11-21', 'Early Dismissal', 'Thanksgiving break');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (18, 2007, 1, '2007-11-22', 'No School', 'Happy Thanksgiving!');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (19, 2007, 1, '2007-11-23', 'No School', 'Thanksgiving break');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (20, 2007, 1, '2008-01-21', 'Early Dismissal', 'Martin Luther King');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (21, 2007, 1, '2008-02-15', 'Early Dismissal', 'Staff development');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (22, 2007, 1, '2008-02-18', 'No School', 'President''s Day');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (23, 2007, 1, '2008-03-21', 'Early Dismissal', 'Good Friday');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (24, 2007, 1, '2008-03-24', 'No School', 'Easter Monday');
INSERT INTO calendar_events (id, syear, school_id, school_date, title, description) VALUES (25, 2007, 1, '2008-03-23', 'Easter', 'Happy Easter!');


--
-- Data for Name: config; Type: TABLE DATA; Schema: public; Owner: postgres
--
INSERT INTO config (title, syear, login) VALUES ('Centre School Software', 2009, 'No');


--
-- Data for Name: course_periods; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: course_subjects; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: courses; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: custom; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: custom_fields; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO custom_fields (id, "type", search, title, sort_order, select_options, category_id, system_field, required, default_selection) VALUES (200000000, 'select', NULL, 'Gender', 0, 'Male
Female', 1, 'Y', 'Y', NULL);
INSERT INTO custom_fields (id, "type", search, title, sort_order, select_options, category_id, system_field, required, default_selection) VALUES (200000001, 'select', NULL, 'Ethnicity', 1, 'White, Non-Hispanic
Black, Non-Hispanic
Amer. Indian or Alaskan Native
Asian or Pacific Islander
Hispanic
Other', 1, 'Y', 'Y', NULL);
INSERT INTO custom_fields (id, "type", search, title, sort_order, select_options, category_id, system_field, required, default_selection) VALUES (200000002, 'text', NULL, 'Common Name', 2, NULL, 1, 'Y', NULL, NULL);
INSERT INTO custom_fields (id, "type", search, title, sort_order, select_options, category_id, system_field, required, default_selection) VALUES (200000003, 'text', NULL, 'Social Security', 3, NULL, 1, 'Y', NULL, NULL);
INSERT INTO custom_fields (id, "type", search, title, sort_order, select_options, category_id, system_field, required, default_selection) VALUES (200000004, 'date', NULL, 'Birthdate', 4, NULL, 1, 'Y', NULL, NULL);
INSERT INTO custom_fields (id, "type", search, title, sort_order, select_options, category_id, system_field, required, default_selection) VALUES (200000005, 'select', NULL, 'Language', 5, 'English
Spanish', 1, 'Y', NULL, NULL);
INSERT INTO custom_fields (id, "type", search, title, sort_order, select_options, category_id, system_field, required, default_selection) VALUES (200000006, 'text', NULL, 'Physician', 6, NULL, 2, 'Y', NULL, NULL);
INSERT INTO custom_fields (id, "type", search, title, sort_order, select_options, category_id, system_field, required, default_selection) VALUES (200000007, 'text', NULL, 'Physician Phone', 7, NULL, 2, 'Y', NULL, NULL);
INSERT INTO custom_fields (id, "type", search, title, sort_order, select_options, category_id, system_field, required, default_selection) VALUES (200000008, 'text', NULL, 'Preferred Hospital', 8, NULL, 2, 'Y', NULL, NULL);
INSERT INTO custom_fields (id, "type", search, title, sort_order, select_options, category_id, system_field, required, default_selection) VALUES (200000009, 'textarea', NULL, 'Comments', 9, NULL, 2, 'Y', NULL, NULL);
INSERT INTO custom_fields (id, "type", search, title, sort_order, select_options, category_id, system_field, required, default_selection) VALUES (200000010, 'radio', NULL, 'Has Doctor''s Note', 10, NULL, 2, 'Y', NULL, NULL);
INSERT INTO custom_fields (id, "type", search, title, sort_order, select_options, category_id, system_field, required, default_selection) VALUES (200000011, 'textarea', NULL, 'Doctor''s Note Comments', 11, NULL, 2, 'Y', NULL, NULL);


--
-- Data for Name: eligibility; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: eligibility_activities; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO eligibility_activities (id, syear, school_id, title, start_date, end_date) VALUES (1, 2007, 1, 'Boy''s Basketball', '2008-01-01', '2008-04-14');
INSERT INTO eligibility_activities (id, syear, school_id, title, start_date, end_date) VALUES (2, 2007, 1, 'Chess Team', '2007-09-01', '2008-06-04');
INSERT INTO eligibility_activities (id, syear, school_id, title, start_date, end_date) VALUES (3, 2007, 1, 'Girl''s Basketball', '2008-01-01', '2008-04-15');
INSERT INTO eligibility_activities (id, syear, school_id, title, start_date, end_date) VALUES (4, 2008, 1, 'Boy''s Basketball', '2008-01-01', '2008-04-14');
INSERT INTO eligibility_activities (id, syear, school_id, title, start_date, end_date) VALUES (5, 2008, 1, 'Chess Team', '2007-09-01', '2008-06-04');
INSERT INTO eligibility_activities (id, syear, school_id, title, start_date, end_date) VALUES (6, 2008, 1, 'Girl''s Basketball', '2008-01-01', '2008-04-15');
INSERT INTO eligibility_activities (id, syear, school_id, title, start_date, end_date) VALUES (7, 2009, 1, 'Boy''s Basketball', '2008-01-01', '2008-04-14');
INSERT INTO eligibility_activities (id, syear, school_id, title, start_date, end_date) VALUES (8, 2009, 1, 'Chess Team', '2007-09-01', '2008-06-04');
INSERT INTO eligibility_activities (id, syear, school_id, title, start_date, end_date) VALUES (9, 2009, 1, 'Girl''s Basketball', '2008-01-01', '2008-04-15');


--
-- Data for Name: eligibility_completed; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: food_service_accounts; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO food_service_accounts (account_id, balance, transaction_id) VALUES (1, 0.00, NULL);


--
-- Data for Name: food_service_categories; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO food_service_categories (category_id, school_id, menu_id, title, sort_order) VALUES (1, 1, 1, 'Lunch Items', 1);


--
-- Data for Name: food_service_items; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO food_service_items (item_id, school_id, short_name, sort_order, description, icon, price, price_reduced, price_free, price_staff) VALUES (1, 1, 'HOTL', 1, 'Student Lunch', 'Lunch.jpg', 1.65, 0.40, 0.00, 2.35);
INSERT INTO food_service_items (item_id, school_id, short_name, sort_order, description, icon, price, price_reduced, price_free, price_staff) VALUES (2, 1, 'MILK', 2, 'Milk', 'Milk.jpg', 0.25, NULL, NULL, 0.50);
INSERT INTO food_service_items (item_id, school_id, short_name, sort_order, description, icon, price, price_reduced, price_free, price_staff) VALUES (3, 1, 'XTRA', 3, 'Extra', 'Fries.jpg', 0.50, NULL, NULL, 1.00);
INSERT INTO food_service_items (item_id, school_id, short_name, sort_order, description, icon, price, price_reduced, price_free, price_staff) VALUES (4, 1, 'PIZZA', 4, 'Extra Pizza', 'Pizza.jpg', 1.00, NULL, NULL, 1.00);


--
-- Data for Name: food_service_menu_items; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO food_service_menu_items (menu_item_id, school_id, menu_id, item_id, category_id, sort_order, does_count) VALUES (1, 1, 1, 1, 1, NULL, NULL);
INSERT INTO food_service_menu_items (menu_item_id, school_id, menu_id, item_id, category_id, sort_order, does_count) VALUES (2, 1, 1, 2, 1, NULL, NULL);
INSERT INTO food_service_menu_items (menu_item_id, school_id, menu_id, item_id, category_id, sort_order, does_count) VALUES (3, 1, 1, 3, 1, NULL, NULL);
INSERT INTO food_service_menu_items (menu_item_id, school_id, menu_id, item_id, category_id, sort_order, does_count) VALUES (4, 1, 1, 4, 1, NULL, NULL);


--
-- Data for Name: food_service_menus; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO food_service_menus (menu_id, school_id, title, sort_order) VALUES (1, 1, 'Lunch', 1);


--
-- Data for Name: food_service_staff_accounts; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO food_service_staff_accounts (staff_id, status, barcode, balance, transaction_id) VALUES (8, NULL, '1000002', 0.00, 0);


--
-- Data for Name: food_service_staff_transaction_items; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: food_service_staff_transactions; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: food_service_student_accounts; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO food_service_student_accounts (student_id, account_id, discount, status, barcode) VALUES (1, 1, NULL, NULL, '1000001');


--
-- Data for Name: food_service_transaction_items; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: food_service_transactions; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: gradebook_assignment_types; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: gradebook_assignments; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: gradebook_grades; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: grades_completed; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: history_marking_periods; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: lunch_period; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: people; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: people_field_categories; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: people_fields; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: people_join_contacts; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: portal_notes; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: profile_exceptions; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/PortalNotes.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/Schools.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/Schools.php?new_school=true', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/CopySchool.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/MarkingPeriods.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/Calendar.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/Periods.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/GradeLevels.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/Rollover.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/Student.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/Student.php&include=General_Info&student_id=new', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/AssignOtherInfo.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/AddUsers.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/AdvancedReport.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/AddDrop.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/Letters.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/MailingLabels.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/StudentLabels.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/PrintStudentInfo.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/StudentFields.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/AddressFields.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/PeopleFields.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/EnrollmentCodes.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/Student.php&category_id=1', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/Student.php&category_id=3', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/Student.php&category_id=2', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Students/Student.php&category_id=5', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Users/User.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Users/User.php&staff_id=new', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Users/AddStudents.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Users/Preferences.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Users/Profiles.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Users/Exceptions.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Users/UserFields.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Users/TeacherPrograms.php?include=Eligibility/EnterEligibility.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Users/User.php&category_id=1', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Users/User.php&category_id=2', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Users/User.php&category_id=3', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/Schedule.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/Requests.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/MassSchedule.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/MassRequests.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/MassDrops.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/PrintSchedules.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/PrintClassLists.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/PrintClassPictures.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/PrintRequests.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/ScheduleReport.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/RequestsReport.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/UnfilledRequests.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/IncompleteSchedules.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/AddDrop.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/Courses.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/Scheduler.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/ReportCards.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/HonorRoll.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/CalcGPA.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/FixGPA.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/Transcripts.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/StudentGrades.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/GradeBreakdown.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/FinalGrades.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/GPARankList.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/ReportCardGrades.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/ReportCardComments.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/ReportCardCommentCodes.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/EditHistoryMarkingPeriods.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Grades/EditReportCardGrades.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Users/TeacherPrograms.php?include=Grades/InputFinalGrades.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Users/TeacherPrograms.php?include=Grades/Grades.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Users/TeacherPrograms.php?include=Grades/AnomalousGrades.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Users/TeacherPrograms.php?include=Grades/AnomalousGrades.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Attendance/Administration.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Attendance/AddAbsences.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Attendance/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Attendance/Percent.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Attendance/Percent.php?list_by_day=true', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Attendance/DailySummary.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Attendance/StudentSummary.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Attendance/FixDailyAttendance.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Attendance/DuplicateAttendance.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Attendance/AttendanceCodes.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Users/TeacherPrograms.php?include=Attendance/TakeAttendance.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Eligibility/Student.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Eligibility/AddActivity.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Eligibility/StudentList.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Eligibility/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Eligibility/Activities.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Eligibility/EntryTimes.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/Accounts.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/Statements.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/Transactions.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/ServeMenus.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/ActivityReport.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/TransactionsReport.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/MenuReports.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/Reminders.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/DailyMenus.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/MenuItems.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/Menus.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/Kiosk.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Resources/Redirect.php?to=doc', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Resources/Redirect.php?to=videohelp', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Resources/Redirect.php?to=forums', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (1, 'Resources/Redirect.php?to=translate', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'School_Setup/Schools.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'School_Setup/MarkingPeriods.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'School_Setup/Calendar.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Students/Student.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Students/AddUsers.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Students/AdvancedReport.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Students/StudentLabels.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Students/Letters.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Students/Student.php&category_id=1', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Students/Student.php&category_id=3', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Students/Student.php&category_id=4', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Students/Student.php&category_id=5', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Users/User.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Users/Preferences.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Users/User.php&category_id=1', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Users/User.php&category_id=2', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Users/User.php&category_id=3', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Scheduling/Schedule.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Scheduling/PrintSchedules.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Scheduling/PrintClassLists.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Scheduling/PrintClassPictures.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Grades/InputFinalGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Grades/ReportCards.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Grades/Grades.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Grades/Assignments.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Grades/Assignments-new.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Grades/AnomalousGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Grades/ProgressReports.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Grades/StudentGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Grades/FinalGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Grades/Configuration.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Grades/ReportCardGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Grades/ReportCardComments.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Grades/ReportCardCommentCodes.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Attendance/TakeAttendance.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Attendance/DailySummary.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Attendance/StudentSummary.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Eligibility/EnterEligibility.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Food_Service/Accounts.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Food_Service/Statements.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Food_Service/DailyMenus.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Food_Service/MenuItems.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Resources/Redirect.php?to=doc', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Resources/Redirect.php?to=videohelp', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Resources/Redirect.php?to=forums', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (2, 'Resources/Redirect.php?to=translate', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'School_Setup/Schools.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'School_Setup/Calendar.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Students/Student.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Students/Student.php&category_id=1', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Students/Student.php&category_id=3', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Students/Student.php&category_id=5', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Users/User.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Users/Preferences.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Users/User.php&category_id=1', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Users/User.php&category_id=2', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Users/User.php&category_id=3', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Scheduling/Schedule.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Scheduling/PrintClassPictures.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Scheduling/Requests.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Grades/StudentGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Grades/FinalGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Grades/ReportCards.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Grades/Transcripts.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Grades/GPARankList.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Attendance/StudentSummary.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Attendance/DailySummary.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Eligibility/Student.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Eligibility/StudentList.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Food_Service/Accounts.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Food_Service/Statements.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Food_Service/DailyMenus.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Food_Service/MenuItems.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Resources/Redirect.php?to=doc', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Resources/Redirect.php?to=videohelp', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Resources/Redirect.php?to=forums', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (3, 'Resources/Redirect.php?to=translate', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'School_Setup/Schools.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'School_Setup/Calendar.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Students/Student.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Students/Student.php&category_id=1', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Students/Student.php&category_id=3', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Students/Student.php&category_id=5', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Scheduling/Schedule.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Scheduling/PrintClassPictures.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Scheduling/Requests.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Grades/StudentGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Grades/FinalGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Grades/ReportCards.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Grades/Transcripts.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Grades/GPARankList.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Attendance/StudentSummary.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Attendance/DailySummary.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Eligibility/Student.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Eligibility/StudentList.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Food_Service/Accounts.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Food_Service/Statements.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Food_Service/DailyMenus.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Food_Service/MenuItems.php', 'Y', NULL);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Resources/Redirect.php?to=doc', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Resources/Redirect.php?to=videohelp', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Resources/Redirect.php?to=forums', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (0, 'Resources/Redirect.php?to=translate', 'Y', 'Y');


--
-- Data for Name: program_config; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO program_config (syear, school_id, program, title, value) VALUES (2009, 1, 'eligibility', 'START_DAY', '1');
INSERT INTO program_config (syear, school_id, program, title, value) VALUES (2009, 1, 'eligibility', 'START_HOUR', '23');
INSERT INTO program_config (syear, school_id, program, title, value) VALUES (2009, 1, 'eligibility', 'START_MINUTE', '30');
INSERT INTO program_config (syear, school_id, program, title, value) VALUES (2009, 1, 'eligibility', 'START_M', 'PM');
INSERT INTO program_config (syear, school_id, program, title, value) VALUES (2009, 1, 'eligibility', 'END_DAY', '5');
INSERT INTO program_config (syear, school_id, program, title, value) VALUES (2009, 1, 'eligibility', 'END_HOUR', '23');
INSERT INTO program_config (syear, school_id, program, title, value) VALUES (2009, 1, 'eligibility', 'END_MINUTE', '30');
INSERT INTO program_config (syear, school_id, program, title, value) VALUES (2009, 1, 'eligibility', 'END_M', 'PM');


--
-- Data for Name: program_user_config; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: report_card_comment_categories; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: report_card_comment_code_scales; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: report_card_comment_codes; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: report_card_comments; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO report_card_comments (id, syear, school_id, course_id, category_id, scale_id, sort_order, title) VALUES (1, 2007, 1, NULL, NULL, NULL, 1, 'Fails to Meet Course Requirements');
INSERT INTO report_card_comments (id, syear, school_id, course_id, category_id, scale_id, sort_order, title) VALUES (2, 2007, 1, NULL, NULL, NULL, 2, 'Comes to Class Unprepared');
INSERT INTO report_card_comments (id, syear, school_id, course_id, category_id, scale_id, sort_order, title) VALUES (3, 2007, 1, NULL, NULL, NULL, 3, 'Exerts Positive Influence in Class');
INSERT INTO report_card_comments (id, syear, school_id, course_id, category_id, scale_id, sort_order, title) VALUES (4, 2008, 1, NULL, NULL, NULL, 1, 'Fails to Meet Course Requirements');
INSERT INTO report_card_comments (id, syear, school_id, course_id, category_id, scale_id, sort_order, title) VALUES (5, 2008, 1, NULL, NULL, NULL, 2, 'Comes to Class Unprepared');
INSERT INTO report_card_comments (id, syear, school_id, course_id, category_id, scale_id, sort_order, title) VALUES (6, 2008, 1, NULL, NULL, NULL, 3, 'Exerts Positive Influence in Class');
INSERT INTO report_card_comments (id, syear, school_id, course_id, category_id, scale_id, sort_order, title) VALUES (7, 2009, 1, NULL, NULL, NULL, 1, 'Fails to Meet Course Requirements');
INSERT INTO report_card_comments (id, syear, school_id, course_id, category_id, scale_id, sort_order, title) VALUES (8, 2009, 1, NULL, NULL, NULL, 2, 'Comes to Class Unprepared');
INSERT INTO report_card_comments (id, syear, school_id, course_id, category_id, scale_id, sort_order, title) VALUES (9, 2009, 1, NULL, NULL, NULL, 3, 'Exerts Positive Influence in Class');


--
-- Data for Name: report_card_grade_scales; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO report_card_grade_scales (id, syear, school_id, title, "comment", hhr_gpa_value, hr_gpa_value, sort_order, rollover_id, gp_scale) VALUES (3, 2008, 1, 'Main', NULL, NULL, NULL, 1, 2, NULL);
INSERT INTO report_card_grade_scales (id, syear, school_id, title, "comment", hhr_gpa_value, hr_gpa_value, sort_order, rollover_id, gp_scale) VALUES (2, 2007, 1, 'Main', NULL, NULL, NULL, 1, NULL, 4.000);
INSERT INTO report_card_grade_scales (id, syear, school_id, title, "comment", hhr_gpa_value, hr_gpa_value, sort_order, rollover_id, gp_scale) VALUES (4, 2009, 1, 'Main', NULL, NULL, NULL, 1, 3, NULL);


--
-- Data for Name: report_card_grades; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO report_card_grades (id, syear, school_id, title, sort_order, gpa_value, break_off, "comment", grade_scale_id, unweighted_gp) VALUES (31, 2009, 1, 'A+', 1, 12.00, 97, 'Consistently superior', 4, NULL);
INSERT INTO report_card_grades (id, syear, school_id, title, sort_order, gpa_value, break_off, "comment", grade_scale_id, unweighted_gp) VALUES (32, 2009, 1, 'A', 2, 11.00, 93, 'Superior', 4, NULL);
INSERT INTO report_card_grades (id, syear, school_id, title, sort_order, gpa_value, break_off, "comment", grade_scale_id, unweighted_gp) VALUES (33, 2009, 1, 'A-', 3, 10.00, 90, NULL, 4, NULL);
INSERT INTO report_card_grades (id, syear, school_id, title, sort_order, gpa_value, break_off, "comment", grade_scale_id, unweighted_gp) VALUES (34, 2009, 1, 'B+', 4, 9.00, 87, NULL, 4, NULL);
INSERT INTO report_card_grades (id, syear, school_id, title, sort_order, gpa_value, break_off, "comment", grade_scale_id, unweighted_gp) VALUES (35, 2009, 1, 'B', 5, 8.00, 83, 'Above average', 4, NULL);
INSERT INTO report_card_grades (id, syear, school_id, title, sort_order, gpa_value, break_off, "comment", grade_scale_id, unweighted_gp) VALUES (36, 2009, 1, 'B-', 6, 7.00, 80, NULL, 4, NULL);
INSERT INTO report_card_grades (id, syear, school_id, title, sort_order, gpa_value, break_off, "comment", grade_scale_id, unweighted_gp) VALUES (37, 2009, 1, 'C+', 7, 6.00, 77, NULL, 4, NULL);
INSERT INTO report_card_grades (id, syear, school_id, title, sort_order, gpa_value, break_off, "comment", grade_scale_id, unweighted_gp) VALUES (38, 2009, 1, 'C', 8, 5.00, 73, 'Average', 4, NULL);
INSERT INTO report_card_grades (id, syear, school_id, title, sort_order, gpa_value, break_off, "comment", grade_scale_id, unweighted_gp) VALUES (39, 2009, 1, 'C-', 9, 4.00, 70, NULL, 4, NULL);
INSERT INTO report_card_grades (id, syear, school_id, title, sort_order, gpa_value, break_off, "comment", grade_scale_id, unweighted_gp) VALUES (40, 2009, 1, 'D+', 10, 3.00, 67, NULL, 4, NULL);
INSERT INTO report_card_grades (id, syear, school_id, title, sort_order, gpa_value, break_off, "comment", grade_scale_id, unweighted_gp) VALUES (41, 2009, 1, 'D', 11, 2.00, 63, 'Below average', 4, NULL);
INSERT INTO report_card_grades (id, syear, school_id, title, sort_order, gpa_value, break_off, "comment", grade_scale_id, unweighted_gp) VALUES (42, 2009, 1, 'D-', 12, 1.00, 60, NULL, 4, NULL);
INSERT INTO report_card_grades (id, syear, school_id, title, sort_order, gpa_value, break_off, "comment", grade_scale_id, unweighted_gp) VALUES (43, 2009, 1, 'F', 13, 0.00, 0, 'Failing', 4, NULL);
INSERT INTO report_card_grades (id, syear, school_id, title, sort_order, gpa_value, break_off, "comment", grade_scale_id, unweighted_gp) VALUES (44, 2009, 1, 'I', 14, 0.00, 0, 'Incomplete', 4, NULL);
INSERT INTO report_card_grades (id, syear, school_id, title, sort_order, gpa_value, break_off, "comment", grade_scale_id, unweighted_gp) VALUES (45, 2009, 1, 'N/A', 15, 0.00, NULL, NULL, 4, NULL);


--
-- Data for Name: schedule; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: schedule_requests; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: school_gradelevels; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO school_gradelevels (id, school_id, short_name, title, next_grade_id, sort_order) VALUES (1, 1, 'KG', 'Kindergarten', 2, 1);
INSERT INTO school_gradelevels (id, school_id, short_name, title, next_grade_id, sort_order) VALUES (2, 1, '01', '1st', 3, 2);
INSERT INTO school_gradelevels (id, school_id, short_name, title, next_grade_id, sort_order) VALUES (3, 1, '02', '2nd', 4, 3);
INSERT INTO school_gradelevels (id, school_id, short_name, title, next_grade_id, sort_order) VALUES (4, 1, '03', '3rd', 5, 4);
INSERT INTO school_gradelevels (id, school_id, short_name, title, next_grade_id, sort_order) VALUES (5, 1, '04', '4th', 6, 5);
INSERT INTO school_gradelevels (id, school_id, short_name, title, next_grade_id, sort_order) VALUES (6, 1, '05', '5th', 7, 6);
INSERT INTO school_gradelevels (id, school_id, short_name, title, next_grade_id, sort_order) VALUES (7, 1, '06', '6th', 8, 7);
INSERT INTO school_gradelevels (id, school_id, short_name, title, next_grade_id, sort_order) VALUES (8, 1, '07', '7th', 9, 8);
INSERT INTO school_gradelevels (id, school_id, short_name, title, next_grade_id, sort_order) VALUES (9, 1, '08', '8th', NULL, 9);


--
-- Data for Name: school_marking_periods; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO school_marking_periods (marking_period_id, syear, mp, school_id, parent_id, title, short_name, sort_order, start_date, end_date, post_start_date, post_end_date, does_grades, does_exam, does_comments, rollover_id) VALUES (23, 2009, 'FY', 1, NULL, 'Full Year', 'FY', 1, '2009-08-21', '2010-06-05', NULL, NULL, NULL, NULL, NULL, 12);
INSERT INTO school_marking_periods (marking_period_id, syear, mp, school_id, parent_id, title, short_name, sort_order, start_date, end_date, post_start_date, post_end_date, does_grades, does_exam, does_comments, rollover_id) VALUES (24, 2009, 'SEM', 1, 23, 'Semester 1', 'S1', 1, '2009-08-21', '2010-01-06', '2010-01-05', '2010-01-06', NULL, NULL, NULL, 13);
INSERT INTO school_marking_periods (marking_period_id, syear, mp, school_id, parent_id, title, short_name, sort_order, start_date, end_date, post_start_date, post_end_date, does_grades, does_exam, does_comments, rollover_id) VALUES (25, 2009, 'SEM', 1, 23, 'Semester 2', 'S2', 2, '2010-01-07', '2010-06-05', '2010-06-04', '2010-06-05', NULL, NULL, NULL, 14);
INSERT INTO school_marking_periods (marking_period_id, syear, mp, school_id, parent_id, title, short_name, sort_order, start_date, end_date, post_start_date, post_end_date, does_grades, does_exam, does_comments, rollover_id) VALUES (26, 2009, 'QTR', 1, 24, 'Quarter 1', 'Q1', 1, '2009-08-21', '2009-10-10', '2009-10-09', '2009-10-10', 'Y', NULL, 'Y', 15);
INSERT INTO school_marking_periods (marking_period_id, syear, mp, school_id, parent_id, title, short_name, sort_order, start_date, end_date, post_start_date, post_end_date, does_grades, does_exam, does_comments, rollover_id) VALUES (27, 2009, 'QTR', 1, 24, 'Quarter 2', 'Q2', 2, '2009-10-11', '2010-01-06', '2010-01-05', '2010-01-06', 'Y', NULL, 'Y', 16);
INSERT INTO school_marking_periods (marking_period_id, syear, mp, school_id, parent_id, title, short_name, sort_order, start_date, end_date, post_start_date, post_end_date, does_grades, does_exam, does_comments, rollover_id) VALUES (28, 2009, 'QTR', 1, 25, 'Quarter 3', 'Q3', 3, '2010-01-07', '2010-03-10', '2010-03-09', '2010-03-10', 'Y', NULL, 'Y', 17);
INSERT INTO school_marking_periods (marking_period_id, syear, mp, school_id, parent_id, title, short_name, sort_order, start_date, end_date, post_start_date, post_end_date, does_grades, does_exam, does_comments, rollover_id) VALUES (29, 2009, 'QTR', 1, 25, 'Quarter 4', 'Q4', 4, '2010-03-11', '2010-06-05', '2010-06-06', '2010-06-05', 'Y', NULL, 'Y', 18);
INSERT INTO school_marking_periods (marking_period_id, syear, mp, school_id, parent_id, title, short_name, sort_order, start_date, end_date, post_start_date, post_end_date, does_grades, does_exam, does_comments, rollover_id) VALUES (30, 2009, 'PRO', 1, 26, 'Midterm 1', 'M1', 1, '2009-08-21', '2009-09-21', '2009-09-20', '2009-09-21', 'Y', NULL, NULL, 19);
INSERT INTO school_marking_periods (marking_period_id, syear, mp, school_id, parent_id, title, short_name, sort_order, start_date, end_date, post_start_date, post_end_date, does_grades, does_exam, does_comments, rollover_id) VALUES (31, 2009, 'PRO', 1, 27, 'Midterm 2', 'M2', 2, '2009-10-11', '2009-11-11', '2009-11-10', '2009-11-11', 'Y', NULL, NULL, 20);
INSERT INTO school_marking_periods (marking_period_id, syear, mp, school_id, parent_id, title, short_name, sort_order, start_date, end_date, post_start_date, post_end_date, does_grades, does_exam, does_comments, rollover_id) VALUES (32, 2009, 'PRO', 1, 28, 'Midterm 3', 'M3', 3, '2010-01-07', '2010-02-07', '2010-02-06', '2010-02-07', 'Y', NULL, NULL, 21);
INSERT INTO school_marking_periods (marking_period_id, syear, mp, school_id, parent_id, title, short_name, sort_order, start_date, end_date, post_start_date, post_end_date, does_grades, does_exam, does_comments, rollover_id) VALUES (33, 2009, 'PRO', 1, 29, 'Midterm 4', 'M4', 4, '2010-03-11', '2010-04-11', '2010-04-10', '2010-04-11', 'Y', NULL, NULL, 22);


--
-- Data for Name: school_periods; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO school_periods (period_id, syear, school_id, sort_order, title, short_name, length, start_time, end_time, block, attendance, rollover_id) VALUES (23, 2009, 1, 1, 'Full Day', 'FD', 300, NULL, NULL, NULL, 'Y', 12);
INSERT INTO school_periods (period_id, syear, school_id, sort_order, title, short_name, length, start_time, end_time, block, attendance, rollover_id) VALUES (24, 2009, 1, 2, 'Half Day AM', 'AM', 150, NULL, NULL, NULL, 'Y', 13);
INSERT INTO school_periods (period_id, syear, school_id, sort_order, title, short_name, length, start_time, end_time, block, attendance, rollover_id) VALUES (25, 2009, 1, 3, 'Half Day PM', 'PM', 150, NULL, NULL, NULL, 'Y', 14);
INSERT INTO school_periods (period_id, syear, school_id, sort_order, title, short_name, length, start_time, end_time, block, attendance, rollover_id) VALUES (26, 2009, 1, 4, 'Period 1', '01', 0, NULL, NULL, NULL, NULL, 15);
INSERT INTO school_periods (period_id, syear, school_id, sort_order, title, short_name, length, start_time, end_time, block, attendance, rollover_id) VALUES (27, 2009, 1, 5, 'Period 2', '02', 0, NULL, NULL, NULL, NULL, 16);
INSERT INTO school_periods (period_id, syear, school_id, sort_order, title, short_name, length, start_time, end_time, block, attendance, rollover_id) VALUES (28, 2009, 1, 6, 'Period 3', '03', 0, NULL, NULL, NULL, NULL, 17);
INSERT INTO school_periods (period_id, syear, school_id, sort_order, title, short_name, length, start_time, end_time, block, attendance, rollover_id) VALUES (29, 2009, 1, 7, 'Period 4', '04', 0, NULL, NULL, NULL, NULL, 18);
INSERT INTO school_periods (period_id, syear, school_id, sort_order, title, short_name, length, start_time, end_time, block, attendance, rollover_id) VALUES (30, 2009, 1, 8, 'Period 5', '05', 0, NULL, NULL, NULL, NULL, 19);
INSERT INTO school_periods (period_id, syear, school_id, sort_order, title, short_name, length, start_time, end_time, block, attendance, rollover_id) VALUES (31, 2009, 1, 9, 'Period 6', '06', 0, NULL, NULL, NULL, NULL, 20);
INSERT INTO school_periods (period_id, syear, school_id, sort_order, title, short_name, length, start_time, end_time, block, attendance, rollover_id) VALUES (32, 2009, 1, 10, 'Period 7', '07', 0, NULL, NULL, NULL, NULL, 21);
INSERT INTO school_periods (period_id, syear, school_id, sort_order, title, short_name, length, start_time, end_time, block, attendance, rollover_id) VALUES (33, 2009, 1, 11, 'Period 8', '08', 0, NULL, NULL, NULL, NULL, 22);


--
-- Data for Name: schools; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO schools (syear, id, title, address, city, state, zipcode, phone, principal, www_address, school_number, short_name, reporting_gp_scale) VALUES (2009, 1, 'Default School', '500 S. Street St.', 'Springfield', 'IL', '62704', NULL, 'Mr. Principal', 'www.centresis.org', NULL, NULL, NULL);


--
-- Data for Name: staff; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO staff (syear, staff_id, current_school_id, title, first_name, last_name, middle_name, name_suffix, username, "password", phone, email, profile, homeroom, schools, last_login, failed_login, profile_id, rollover_id) VALUES (2009, 2, NULL, NULL, 'Teach', 'Teacher', 'T', NULL, 'teacher', 'teacher', NULL, NULL, 'teacher', NULL, NULL, NULL, NULL, 2, NULL);
INSERT INTO staff (syear, staff_id, current_school_id, title, first_name, last_name, middle_name, name_suffix, username, "password", phone, email, profile, homeroom, schools, last_login, failed_login, profile_id, rollover_id) VALUES (2009, 3, NULL, NULL, 'Parent', 'Parent', 'P', NULL, 'parent', 'parent', NULL, NULL, 'parent', NULL, NULL, NULL, NULL, 3, NULL);
INSERT INTO staff (syear, staff_id, current_school_id, title, first_name, last_name, middle_name, name_suffix, username, "password", phone, email, profile, homeroom, schools, last_login, failed_login, profile_id, rollover_id) VALUES (2009, 1, 1, NULL, 'Admin', 'Administrator', 'A', NULL, 'admin', 'admin', NULL, NULL, 'admin', NULL, NULL, '2009-09-15 20:09:55', NULL, 1, NULL);


--
-- Data for Name: staff_exceptions; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/PortalNotes.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/Schools.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/Schools.php?new_school=true', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/CopySchool.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/MarkingPeriods.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/Calendar.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/Periods.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/GradeLevels.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'School_Setup/Rollover.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/Student.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/Student.php&include=General_Info&student_id=new', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/AssignOtherInfo.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/AddUsers.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/AdvancedReport.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/AddDrop.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/Letters.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/MailingLabels.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/StudentLabels.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/PrintStudentInfo.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/StudentFields.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/AddressFields.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/PeopleFields.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/EnrollmentCodes.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Custom/CreateParents.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/Student.php&category_id=1', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/Student.php&category_id=3', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/Student.php&category_id=2', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/Student.php&category_id=4', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Students/Student.php&category_id=5', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Users/User.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Users/User.php&staff_id=new', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Users/AddStudents.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Users/Preferences.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Users/Profiles.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Users/Exceptions.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Users/UserFields.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Users/TeacherPrograms.php?include=Eligibility/EnterEligibility.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Users/User.php&category_id=1', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Users/User.php&category_id=2', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Users/User.php&category_id=3', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/Schedule.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/Requests.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/MassSchedule.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/MassRequests.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/MassDrops.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/PrintSchedules.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/PrintClassLists.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/PrintClassPictures.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/PrintRequests.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/ScheduleReport.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/RequestsReport.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/UnfilledRequests.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/IncompleteSchedules.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/AddDrop.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/Courses.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Scheduling/Scheduler.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Grades/ReportCards.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Grades/HonorRoll.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Grades/CalcGPA.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Grades/Transcripts.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Grades/StudentGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Grades/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Grades/GradeBreakdown.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Grades/FinalGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Grades/GPARankList.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Grades/ReportCardGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Grades/ReportCardComments.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Grades/ReportCardCommentCodes.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Grades/FixGPA.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Users/TeacherPrograms.php?include=Grades/InputFinalGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Users/TeacherPrograms.php?include=Grades/Grades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Users/TeacherPrograms.php?include=Grades/AnomalousGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Users/TeacherPrograms.php?include=Grades/AnomalousGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Attendance/Administration.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Attendance/AddAbsences.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Attendance/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Attendance/Percent.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Attendance/Percent.php?list_by_day=true', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Attendance/DailySummary.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Attendance/StudentSummary.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Attendance/FixDailyAttendance.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Attendance/DuplicateAttendance.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Attendance/AttendanceCodes.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Users/TeacherPrograms.php?include=Attendance/TakeAttendance.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Eligibility/Student.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Eligibility/AddActivity.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Eligibility/StudentList.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Eligibility/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Eligibility/Activities.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Eligibility/EntryTimes.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/Accounts.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/Statements.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/Transactions.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/ServeMenus.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/ActivityReport.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/TransactionsReport.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/MenuReports.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/Reminders.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/DailyMenus.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/MenuItems.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/Menus.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (1, 'Food_Service/Kiosk.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Food_Service/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'School_Setup/PortalNotes.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'School_Setup/Schools.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'School_Setup/Schools.php?new_school=true', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'School_Setup/CopySchool.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'School_Setup/MarkingPeriods.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'School_Setup/Calendar.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'School_Setup/Periods.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'School_Setup/GradeLevels.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'School_Setup/Rollover.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/Student.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/Student.php&include=General_Info&student_id=new', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/AssignOtherInfo.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/AddUsers.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/AdvancedReport.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/AddDrop.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/Letters.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/MailingLabels.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/StudentLabels.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/PrintStudentInfo.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/StudentFields.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/AddressFields.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/PeopleFields.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/EnrollmentCodes.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Custom/CreateParents.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/Student.php&category_id=1', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/Student.php&category_id=3', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/Student.php&category_id=2', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/Student.php&category_id=4', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Students/Student.php&category_id=5', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Users/User.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Users/User.php&staff_id=new', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Users/AddStudents.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Users/Preferences.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Users/Profiles.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Users/Exceptions.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Users/UserFields.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Users/TeacherPrograms.php?include=Eligibility/EnterEligibility.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Users/User.php&category_id=1', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Users/User.php&category_id=2', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Users/User.php&category_id=3', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Scheduling/Schedule.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Scheduling/Requests.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Scheduling/MassSchedule.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Scheduling/MassRequests.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Scheduling/MassDrops.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Scheduling/PrintSchedules.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Scheduling/PrintClassLists.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Scheduling/PrintClassPictures.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Scheduling/PrintRequests.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Scheduling/ScheduleReport.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Scheduling/RequestsReport.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Scheduling/UnfilledRequests.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Scheduling/IncompleteSchedules.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Scheduling/AddDrop.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Scheduling/Courses.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Scheduling/Scheduler.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Grades/ReportCards.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Grades/HonorRoll.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Grades/CalcGPA.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Grades/Transcripts.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Grades/StudentGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Grades/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Grades/GradeBreakdown.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Grades/FinalGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Grades/GPARankList.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Grades/ReportCardGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Grades/ReportCardComments.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Grades/FixGPA.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Users/TeacherPrograms.php?include=Grades/InputFinalGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Users/TeacherPrograms.php?include=Grades/Grades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Users/TeacherPrograms.php?include=Grades/AnomalousGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Users/TeacherPrograms.php?include=Grades/AnomalousGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Attendance/Administration.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Attendance/AddAbsences.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Attendance/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Attendance/Percent.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Attendance/Percent.php?list_by_day=true', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Attendance/DailySummary.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Attendance/StudentSummary.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Attendance/FixDailyAttendance.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Attendance/DuplicateAttendance.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Attendance/AttendanceCodes.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Users/TeacherPrograms.php?include=Attendance/TakeAttendance.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Eligibility/Student.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Eligibility/AddActivity.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Eligibility/StudentList.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Eligibility/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Eligibility/Activities.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Eligibility/EntryTimes.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Food_Service/Accounts.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Food_Service/Statements.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Food_Service/Transactions.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Food_Service/ServeMenus.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Food_Service/ActivityReport.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Food_Service/TransactionsReport.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Food_Service/MenuReports.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Food_Service/Reminders.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Food_Service/DailyMenus.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Food_Service/MenuItems.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Food_Service/Menus.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (6, 'Food_Service/Kiosk.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Food_Service/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'School_Setup/PortalNotes.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'School_Setup/Schools.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'School_Setup/Schools.php?new_school=true', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'School_Setup/CopySchool.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'School_Setup/MarkingPeriods.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'School_Setup/Calendar.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'School_Setup/Periods.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'School_Setup/GradeLevels.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'School_Setup/Rollover.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/Student.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/Student.php&include=General_Info&student_id=new', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/AssignOtherInfo.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/AddUsers.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/AdvancedReport.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/AddDrop.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/Letters.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/MailingLabels.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/StudentLabels.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/PrintStudentInfo.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/StudentFields.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/AddressFields.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/PeopleFields.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/EnrollmentCodes.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Custom/CreateParents.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/Student.php&category_id=1', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/Student.php&category_id=3', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/Student.php&category_id=2', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/Student.php&category_id=4', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Students/Student.php&category_id=5', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Users/User.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Users/User.php&staff_id=new', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Users/AddStudents.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Users/Preferences.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Users/Profiles.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Users/Exceptions.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Users/UserFields.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Users/TeacherPrograms.php?include=Eligibility/EnterEligibility.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Users/User.php&category_id=1', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Users/User.php&category_id=2', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Users/User.php&category_id=3', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Scheduling/Schedule.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Scheduling/Requests.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Scheduling/MassSchedule.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Scheduling/MassRequests.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Scheduling/MassDrops.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Scheduling/PrintSchedules.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Scheduling/PrintClassLists.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Scheduling/PrintClassPictures.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Scheduling/PrintRequests.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Scheduling/ScheduleReport.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Scheduling/RequestsReport.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Scheduling/UnfilledRequests.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Scheduling/IncompleteSchedules.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Scheduling/AddDrop.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Scheduling/Courses.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Scheduling/Scheduler.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Grades/ReportCards.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Grades/HonorRoll.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Grades/CalcGPA.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Grades/Transcripts.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Grades/StudentGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Grades/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Grades/GradeBreakdown.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Grades/FinalGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Grades/GPARankList.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Grades/ReportCardGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Grades/ReportCardComments.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Grades/FixGPA.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Users/TeacherPrograms.php?include=Grades/InputFinalGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Users/TeacherPrograms.php?include=Grades/Grades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Users/TeacherPrograms.php?include=Grades/AnomalousGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Users/TeacherPrograms.php?include=Grades/AnomalousGrades.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Attendance/Administration.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Attendance/AddAbsences.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Attendance/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Attendance/Percent.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Attendance/Percent.php?list_by_day=true', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Attendance/DailySummary.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Attendance/StudentSummary.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Attendance/FixDailyAttendance.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Attendance/DuplicateAttendance.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Attendance/AttendanceCodes.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Users/TeacherPrograms.php?include=Attendance/TakeAttendance.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Eligibility/Student.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Eligibility/AddActivity.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Eligibility/StudentList.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Eligibility/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Eligibility/Activities.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Eligibility/EntryTimes.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Food_Service/Accounts.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Food_Service/Statements.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Food_Service/Transactions.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Food_Service/ServeMenus.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Food_Service/ActivityReport.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Food_Service/TransactionsReport.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Food_Service/MenuReports.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Food_Service/Reminders.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Food_Service/DailyMenus.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Food_Service/MenuItems.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Food_Service/Menus.php', 'Y', 'Y');
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) VALUES (9, 'Food_Service/Kiosk.php', 'Y', 'Y');


--
-- Data for Name: staff_field_categories; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO staff_field_categories (id, title, sort_order, columns, include, "admin", teacher, parent, "none") VALUES (1, 'General Info', 1, NULL, NULL, 'Y', 'Y', 'Y', 'Y');
INSERT INTO staff_field_categories (id, title, sort_order, columns, include, "admin", teacher, parent, "none") VALUES (2, 'Schedule', 2, NULL, NULL, NULL, 'Y', NULL, NULL);
INSERT INTO staff_field_categories (id, title, sort_order, columns, include, "admin", teacher, parent, "none") VALUES (3, 'Food Service', 3, NULL, 'Food_Service/User', 'Y', 'Y', NULL, NULL);


--
-- Data for Name: staff_fields; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: student_eligibility_activities; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: student_enrollment; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO student_enrollment (id, syear, school_id, student_id, grade_id, start_date, end_date, enrollment_code, drop_code, next_school, calendar_id, last_school) VALUES (3, 2009, 1, 1, 7, '2009-09-14', NULL, 15, NULL, 1, 4, 1);


--
-- Data for Name: student_enrollment_codes; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO student_enrollment_codes (id, syear, title, short_name, "type", default_code, sort_order) VALUES (13, 2009, 'Moved from District', 'MOVE', 'Drop', NULL, 1);
INSERT INTO student_enrollment_codes (id, syear, title, short_name, "type", default_code, sort_order) VALUES (14, 2009, 'Expelled', 'EXP', 'Drop', NULL, 2);
INSERT INTO student_enrollment_codes (id, syear, title, short_name, "type", default_code, sort_order) VALUES (15, 2009, 'Beginning of Year', 'EBY', 'Add', 'Y', 3);
INSERT INTO student_enrollment_codes (id, syear, title, short_name, "type", default_code, sort_order) VALUES (16, 2009, 'From Other District', 'OTHER', 'Add', NULL, 4);
INSERT INTO student_enrollment_codes (id, syear, title, short_name, "type", default_code, sort_order) VALUES (17, 2009, 'Transferred in District', 'TRAN', 'Drop', NULL, 5);
INSERT INTO student_enrollment_codes (id, syear, title, short_name, "type", default_code, sort_order) VALUES (18, 2009, 'Transferred in District', 'EMY', 'Add', NULL, 6);


--
-- Data for Name: student_field_categories; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO student_field_categories (id, title, sort_order, columns, include) VALUES (1, 'General Info', 1, NULL, NULL);
INSERT INTO student_field_categories (id, title, sort_order, columns, include) VALUES (3, 'Addresses & Contacts', 2, NULL, NULL);
INSERT INTO student_field_categories (id, title, sort_order, columns, include) VALUES (2, 'Medical', 3, NULL, NULL);
INSERT INTO student_field_categories (id, title, sort_order, columns, include) VALUES (4, 'Comments', 4, NULL, NULL);
INSERT INTO student_field_categories (id, title, sort_order, columns, include) VALUES (5, 'Food Service', 5, NULL, 'Food_Service/Student');


--
-- Data for Name: student_gpa_calculated; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: student_gpa_running; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: student_medical; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: student_medical_alerts; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: student_medical_visits; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: student_mp_comments; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: student_mp_stats; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: student_report_card_comments; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: student_report_card_grades; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: student_test_categories; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: student_test_scores; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: students; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO students (student_id, last_name, first_name, middle_name, name_suffix, username, "password", last_login, failed_login, custom_200000000, custom_200000001, custom_200000002, custom_200000003, custom_200000004, custom_200000005, custom_200000006, custom_200000007, custom_200000008, custom_200000009, custom_200000010, custom_200000011) VALUES (1, 'Student', 'Student', 'S', NULL, 'student', 'student', NULL, NULL, 'Male', 'White, Non-Hispanic', 'Bug', NULL, '1996-12-04', 'English', NULL, NULL, NULL, NULL, NULL, NULL);


--
-- Data for Name: students_join_address; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: students_join_people; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: students_join_users; Type: TABLE DATA; Schema: public; Owner: postgres
--



--
-- Data for Name: user_profiles; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO user_profiles (id, profile, title) VALUES (0, 'student', 'Student');
INSERT INTO user_profiles (id, profile, title) VALUES (1, 'admin', 'Administrator');
INSERT INTO user_profiles (id, profile, title) VALUES (2, 'teacher', 'Teacher');
INSERT INTO user_profiles (id, profile, title) VALUES (3, 'parent', 'Parent');


--
-- Name: address_field_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY address_field_categories
    ADD CONSTRAINT address_field_categories_pkey PRIMARY KEY (id);


--
-- Name: address_fields_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY address_fields
    ADD CONSTRAINT address_fields_pkey PRIMARY KEY (id);


--
-- Name: address_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY address
    ADD CONSTRAINT address_pkey PRIMARY KEY (address_id);


--
-- Name: attendance_calendar_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY attendance_calendar
    ADD CONSTRAINT attendance_calendar_pkey PRIMARY KEY (syear, school_id, school_date, calendar_id);


--
-- Name: attendance_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY attendance_codes
    ADD CONSTRAINT attendance_codes_pkey PRIMARY KEY (id);


--
-- Name: attendance_completed_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY attendance_completed
    ADD CONSTRAINT attendance_completed_pkey PRIMARY KEY (staff_id, school_date, period_id, table_name);


--
-- Name: attendance_day_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY attendance_day
    ADD CONSTRAINT attendance_day_pkey PRIMARY KEY (student_id, school_date);


--
-- Name: attendance_period_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY attendance_period
    ADD CONSTRAINT attendance_period_pkey PRIMARY KEY (student_id, school_date, period_id);


--
-- Name: calendar_events_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY calendar_events
    ADD CONSTRAINT calendar_events_pkey PRIMARY KEY (id);


--
-- Name: course_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY course_periods
    ADD CONSTRAINT course_periods_pkey PRIMARY KEY (course_period_id);


--
-- Name: course_subjects_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY course_subjects
    ADD CONSTRAINT course_subjects_pkey PRIMARY KEY (subject_id);


--
-- Name: courses_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY courses
    ADD CONSTRAINT courses_pkey PRIMARY KEY (course_id);


--
-- Name: custom_fields_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY custom_fields
    ADD CONSTRAINT custom_fields_pkey PRIMARY KEY (id);


--
-- Name: custom_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY custom
    ADD CONSTRAINT custom_pkey PRIMARY KEY (student_id);


--
-- Name: eligibility_activities_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY eligibility_activities
    ADD CONSTRAINT eligibility_activities_pkey PRIMARY KEY (id);


--
-- Name: eligibility_completed_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY eligibility_completed
    ADD CONSTRAINT eligibility_completed_pkey PRIMARY KEY (staff_id, school_date, period_id);


--
-- Name: food_service_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY food_service_accounts
    ADD CONSTRAINT food_service_accounts_pkey PRIMARY KEY (account_id);


--
-- Name: food_service_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY food_service_categories
    ADD CONSTRAINT food_service_categories_pkey PRIMARY KEY (category_id);


--
-- Name: food_service_items_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY food_service_items
    ADD CONSTRAINT food_service_items_pkey PRIMARY KEY (item_id);


--
-- Name: food_service_menu_items_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY food_service_menu_items
    ADD CONSTRAINT food_service_menu_items_pkey PRIMARY KEY (menu_item_id);


--
-- Name: food_service_menus_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY food_service_menus
    ADD CONSTRAINT food_service_menus_pkey PRIMARY KEY (menu_id);


--
-- Name: food_service_staff_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY food_service_staff_accounts
    ADD CONSTRAINT food_service_staff_accounts_pkey PRIMARY KEY (staff_id);


--
-- Name: food_service_staff_transaction_items_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY food_service_staff_transaction_items
    ADD CONSTRAINT food_service_staff_transaction_items_pkey PRIMARY KEY (item_id, transaction_id);


--
-- Name: food_service_staff_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY food_service_staff_transactions
    ADD CONSTRAINT food_service_staff_transactions_pkey PRIMARY KEY (transaction_id);


--
-- Name: food_service_student_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY food_service_student_accounts
    ADD CONSTRAINT food_service_student_accounts_pkey PRIMARY KEY (student_id);


--
-- Name: food_service_transaction_items_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY food_service_transaction_items
    ADD CONSTRAINT food_service_transaction_items_pkey PRIMARY KEY (item_id, transaction_id);


--
-- Name: food_service_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY food_service_transactions
    ADD CONSTRAINT food_service_transactions_pkey PRIMARY KEY (transaction_id);


--
-- Name: gradebook_assignment_types_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY gradebook_assignment_types
    ADD CONSTRAINT gradebook_assignment_types_pkey PRIMARY KEY (assignment_type_id);


--
-- Name: gradebook_assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY gradebook_assignments
    ADD CONSTRAINT gradebook_assignments_pkey PRIMARY KEY (assignment_id);


--
-- Name: gradebook_grades_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY gradebook_grades
    ADD CONSTRAINT gradebook_grades_pkey PRIMARY KEY (student_id, assignment_id, course_period_id);


--
-- Name: grades_completed_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY grades_completed
    ADD CONSTRAINT grades_completed_pkey PRIMARY KEY (staff_id, marking_period_id, course_period_id);


--
-- Name: history_marking_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY history_marking_periods
    ADD CONSTRAINT history_marking_periods_pkey PRIMARY KEY (marking_period_id);


--
-- Name: lunch_period_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY lunch_period
    ADD CONSTRAINT lunch_period_pkey PRIMARY KEY (student_id, school_date, period_id);


--
-- Name: people_field_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY people_field_categories
    ADD CONSTRAINT people_field_categories_pkey PRIMARY KEY (id);


--
-- Name: people_fields_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY people_fields
    ADD CONSTRAINT people_fields_pkey PRIMARY KEY (id);


--
-- Name: people_join_contacts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY people_join_contacts
    ADD CONSTRAINT people_join_contacts_pkey PRIMARY KEY (id);


--
-- Name: people_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY people
    ADD CONSTRAINT people_pkey PRIMARY KEY (person_id);


--
-- Name: portal_notes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY portal_notes
    ADD CONSTRAINT portal_notes_pkey PRIMARY KEY (id);


--
-- Name: report_card_comment_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY report_card_comment_categories
    ADD CONSTRAINT report_card_comment_categories_pkey PRIMARY KEY (id);


--
-- Name: report_card_comment_code_scales_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY report_card_comment_code_scales
    ADD CONSTRAINT report_card_comment_code_scales_pkey PRIMARY KEY (id);


--
-- Name: report_card_comment_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY report_card_comment_codes
    ADD CONSTRAINT report_card_comment_codes_pkey PRIMARY KEY (id);


--
-- Name: report_card_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY report_card_comments
    ADD CONSTRAINT report_card_comments_pkey PRIMARY KEY (id);


--
-- Name: report_card_grade_scales_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY report_card_grade_scales
    ADD CONSTRAINT report_card_grade_scales_pkey PRIMARY KEY (id);


--
-- Name: report_card_grades_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY report_card_grades
    ADD CONSTRAINT report_card_grades_pkey PRIMARY KEY (id);


--
-- Name: schedule_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY schedule_requests
    ADD CONSTRAINT schedule_requests_pkey PRIMARY KEY (request_id);


--
-- Name: school_gradelevels_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY school_gradelevels
    ADD CONSTRAINT school_gradelevels_pkey PRIMARY KEY (id);


--
-- Name: school_marking_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY school_marking_periods
    ADD CONSTRAINT school_marking_periods_pkey PRIMARY KEY (marking_period_id);


--
-- Name: school_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY school_periods
    ADD CONSTRAINT school_periods_pkey PRIMARY KEY (period_id);


--
-- Name: schools_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY schools
    ADD CONSTRAINT schools_pkey PRIMARY KEY (id, syear);


--
-- Name: staff_field_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY staff_field_categories
    ADD CONSTRAINT staff_field_categories_pkey PRIMARY KEY (id);


--
-- Name: staff_fields_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY staff_fields
    ADD CONSTRAINT staff_fields_pkey PRIMARY KEY (id);


--
-- Name: staff_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY staff
    ADD CONSTRAINT staff_pkey PRIMARY KEY (staff_id);


--
-- Name: student_enrollment_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_enrollment
    ADD CONSTRAINT student_enrollment_pkey PRIMARY KEY (id);


--
-- Name: student_field_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_field_categories
    ADD CONSTRAINT student_field_categories_pkey PRIMARY KEY (id);


--
-- Name: student_medical_alerts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_medical_alerts
    ADD CONSTRAINT student_medical_alerts_pkey PRIMARY KEY (id);


--
-- Name: student_medical_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_medical
    ADD CONSTRAINT student_medical_pkey PRIMARY KEY (id);


--
-- Name: student_medical_visits_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_medical_visits
    ADD CONSTRAINT student_medical_visits_pkey PRIMARY KEY (id);


--
-- Name: student_mp_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_mp_comments
    ADD CONSTRAINT student_mp_comments_pkey PRIMARY KEY (student_id, syear, marking_period_id);


--
-- Name: student_mp_stats_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_mp_stats
    ADD CONSTRAINT student_mp_stats_pkey PRIMARY KEY (student_id, marking_period_id);


--
-- Name: student_report_card_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_report_card_comments
    ADD CONSTRAINT student_report_card_comments_pkey PRIMARY KEY (syear, student_id, course_period_id, marking_period_id, report_card_comment_id);


--
-- Name: student_report_card_grades_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_report_card_grades
    ADD CONSTRAINT student_report_card_grades_id_key UNIQUE (id);


--
-- Name: student_report_card_grades_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_report_card_grades
    ADD CONSTRAINT student_report_card_grades_pkey PRIMARY KEY (id);


--
-- Name: student_test_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_test_categories
    ADD CONSTRAINT student_test_categories_pkey PRIMARY KEY (id);


--
-- Name: student_test_scores_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY student_test_scores
    ADD CONSTRAINT student_test_scores_pkey PRIMARY KEY (id);


--
-- Name: students_join_address_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY students_join_address
    ADD CONSTRAINT students_join_address_pkey PRIMARY KEY (id);


--
-- Name: students_join_people_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY students_join_people
    ADD CONSTRAINT students_join_people_pkey PRIMARY KEY (id);


--
-- Name: students_join_users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY students_join_users
    ADD CONSTRAINT students_join_users_pkey PRIMARY KEY (student_id, staff_id);


--
-- Name: students_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY students
    ADD CONSTRAINT students_pkey PRIMARY KEY (student_id);


--
-- Name: address_3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX address_3 ON address USING btree (zipcode, plus4);


--
-- Name: address_4; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX address_4 ON address USING btree (street);


--
-- Name: address_desc_ind; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX address_desc_ind ON address_fields USING btree (id);


--
-- Name: address_desc_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX address_desc_ind2 ON custom_fields USING btree ("type");


--
-- Name: address_fields_ind3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX address_fields_ind3 ON custom_fields USING btree (category_id);


--
-- Name: attendance_code_categories_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX attendance_code_categories_ind1 ON attendance_code_categories USING btree (id);


--
-- Name: attendance_code_categories_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX attendance_code_categories_ind2 ON attendance_code_categories USING btree (syear, school_id);


--
-- Name: attendance_codes_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX attendance_codes_ind2 ON attendance_codes USING btree (syear, school_id);


--
-- Name: attendance_codes_ind3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX attendance_codes_ind3 ON attendance_codes USING btree (short_name);


--
-- Name: attendance_period_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX attendance_period_ind1 ON attendance_period USING btree (student_id);


--
-- Name: attendance_period_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX attendance_period_ind2 ON attendance_period USING btree (period_id);


--
-- Name: attendance_period_ind3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX attendance_period_ind3 ON attendance_period USING btree (attendance_code);


--
-- Name: attendance_period_ind4; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX attendance_period_ind4 ON attendance_period USING btree (school_date);


--
-- Name: attendance_period_ind5; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX attendance_period_ind5 ON attendance_period USING btree (attendance_code);


--
-- Name: course_periods_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX course_periods_ind1 ON course_periods USING btree (syear);


--
-- Name: course_periods_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX course_periods_ind2 ON course_periods USING btree (course_id, syear, school_id);


--
-- Name: course_periods_ind3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX course_periods_ind3 ON course_periods USING btree (course_period_id);


--
-- Name: course_periods_ind4; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX course_periods_ind4 ON course_periods USING btree (period_id);


--
-- Name: course_periods_ind5; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX course_periods_ind5 ON course_periods USING btree (parent_id);


--
-- Name: course_subjects_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX course_subjects_ind1 ON course_subjects USING btree (syear, school_id, subject_id);


--
-- Name: courses_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX courses_ind1 ON courses USING btree (course_id, syear);


--
-- Name: courses_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX courses_ind2 ON courses USING btree (subject_id);


--
-- Name: custom_desc_ind; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX custom_desc_ind ON custom_fields USING btree (id);


--
-- Name: custom_desc_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX custom_desc_ind2 ON custom_fields USING btree ("type");


--
-- Name: custom_fields_ind3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX custom_fields_ind3 ON custom_fields USING btree (category_id);


--
-- Name: custom_ind; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX custom_ind ON custom USING btree (student_id);


--
-- Name: eligibility_activities_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX eligibility_activities_ind1 ON eligibility_activities USING btree (school_id, syear);


--
-- Name: eligibility_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX eligibility_ind1 ON eligibility USING btree (student_id, course_period_id, school_date);


--
-- Name: food_service_categories_title; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX food_service_categories_title ON food_service_categories USING btree (school_id, menu_id, title);


--
-- Name: food_service_items_short_name; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX food_service_items_short_name ON food_service_items USING btree (school_id, short_name);


--
-- Name: food_service_menus_title; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX food_service_menus_title ON food_service_menus USING btree (school_id, title);


--
-- Name: food_service_staff_transaction_items_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX food_service_staff_transaction_items_ind1 ON food_service_staff_transaction_items USING btree (transaction_id);


--
-- Name: food_service_transaction_items_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX food_service_transaction_items_ind1 ON food_service_transaction_items USING btree (transaction_id);


--
-- Name: gradebook_assignment_types_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX gradebook_assignment_types_ind1 ON gradebook_assignments USING btree (staff_id, course_id);


--
-- Name: gradebook_assignments_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX gradebook_assignments_ind1 ON gradebook_assignments USING btree (staff_id, marking_period_id);


--
-- Name: gradebook_assignments_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX gradebook_assignments_ind2 ON gradebook_assignments USING btree (course_id, course_period_id);


--
-- Name: gradebook_assignments_ind3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX gradebook_assignments_ind3 ON gradebook_assignments USING btree (assignment_type_id);


--
-- Name: gradebook_grades_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX gradebook_grades_ind1 ON gradebook_grades USING btree (assignment_id);


--
-- Name: history_marking_period_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX history_marking_period_ind1 ON history_marking_periods USING btree (school_id);


--
-- Name: history_marking_period_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX history_marking_period_ind2 ON history_marking_periods USING btree (syear);


--
-- Name: history_marking_period_ind3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX history_marking_period_ind3 ON history_marking_periods USING btree (mp_type);


--
-- Name: lunch_period_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX lunch_period_ind1 ON lunch_period USING btree (student_id);


--
-- Name: lunch_period_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX lunch_period_ind2 ON lunch_period USING btree (period_id);


--
-- Name: lunch_period_ind3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX lunch_period_ind3 ON lunch_period USING btree (attendance_code);


--
-- Name: lunch_period_ind4; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX lunch_period_ind4 ON lunch_period USING btree (school_date);


--
-- Name: lunch_period_ind5; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX lunch_period_ind5 ON lunch_period USING btree (attendance_code);


--
-- Name: name; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX name ON students USING btree (last_name, first_name, middle_name);


--
-- Name: people_1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX people_1 ON people USING btree (last_name, first_name);


--
-- Name: people_3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX people_3 ON people USING btree (person_id, last_name, first_name, middle_name);


--
-- Name: people_desc_ind; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX people_desc_ind ON people_fields USING btree (id);


--
-- Name: people_desc_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX people_desc_ind2 ON custom_fields USING btree ("type");


--
-- Name: people_fields_ind3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX people_fields_ind3 ON custom_fields USING btree (category_id);


--
-- Name: people_join_contacts_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX people_join_contacts_ind1 ON people_join_contacts USING btree (person_id);


--
-- Name: program_config_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX program_config_ind1 ON program_config USING btree (program, school_id, syear);


--
-- Name: program_user_config_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX program_user_config_ind1 ON program_user_config USING btree (user_id, program);


--
-- Name: relations_meets_2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX relations_meets_2 ON students_join_people USING btree (person_id);


--
-- Name: relations_meets_5; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX relations_meets_5 ON students_join_people USING btree (id);


--
-- Name: relations_meets_6; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX relations_meets_6 ON students_join_people USING btree (custody, emergency);


--
-- Name: report_card_comment_categories_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX report_card_comment_categories_ind1 ON report_card_comment_categories USING btree (syear, school_id);


--
-- Name: report_card_comment_codes_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX report_card_comment_codes_ind1 ON report_card_comment_codes USING btree (school_id);


--
-- Name: report_card_comments_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX report_card_comments_ind1 ON report_card_comments USING btree (syear, school_id);


--
-- Name: report_card_grades_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX report_card_grades_ind1 ON report_card_grades USING btree (syear, school_id);


--
-- Name: schedule_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX schedule_ind1 ON schedule USING btree (course_id);


--
-- Name: schedule_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX schedule_ind2 ON schedule USING btree (course_period_id);


--
-- Name: schedule_ind3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX schedule_ind3 ON schedule USING btree (student_id, marking_period_id, start_date, end_date);


--
-- Name: schedule_ind4; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX schedule_ind4 ON schedule USING btree (syear, school_id);


--
-- Name: schedule_requests_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX schedule_requests_ind1 ON schedule_requests USING btree (student_id, course_id, syear, school_id);


--
-- Name: schedule_requests_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX schedule_requests_ind2 ON schedule_requests USING btree (syear, school_id);


--
-- Name: schedule_requests_ind3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX schedule_requests_ind3 ON schedule_requests USING btree (course_id, syear, school_id);


--
-- Name: schedule_requests_ind4; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX schedule_requests_ind4 ON schedule_requests USING btree (with_teacher_id);


--
-- Name: schedule_requests_ind5; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX schedule_requests_ind5 ON schedule_requests USING btree (not_teacher_id);


--
-- Name: schedule_requests_ind6; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX schedule_requests_ind6 ON schedule_requests USING btree (with_period_id);


--
-- Name: schedule_requests_ind7; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX schedule_requests_ind7 ON schedule_requests USING btree (not_period_id);


--
-- Name: schedule_requests_ind8; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX schedule_requests_ind8 ON schedule_requests USING btree (request_id);


--
-- Name: school_gradelevels_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX school_gradelevels_ind1 ON school_gradelevels USING btree (school_id);


--
-- Name: school_marking_periods_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX school_marking_periods_ind1 ON school_marking_periods USING btree (parent_id);


--
-- Name: school_marking_periods_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX school_marking_periods_ind2 ON school_marking_periods USING btree (syear, school_id, start_date, end_date);


--
-- Name: school_periods_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX school_periods_ind1 ON school_periods USING btree (period_id, syear);


--
-- Name: schools_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX schools_ind1 ON schools USING btree (syear);


--
-- Name: staff_barcode; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX staff_barcode ON food_service_staff_accounts USING btree (barcode);


--
-- Name: staff_desc_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX staff_desc_ind1 ON staff_fields USING btree (id);


--
-- Name: staff_desc_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX staff_desc_ind2 ON staff_fields USING btree ("type");


--
-- Name: staff_fields_ind3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX staff_fields_ind3 ON staff_fields USING btree (category_id);


--
-- Name: staff_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX staff_ind1 ON staff USING btree (staff_id, syear);


--
-- Name: staff_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX staff_ind2 ON staff USING btree (last_name, first_name);


--
-- Name: staff_ind3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX staff_ind3 ON staff USING btree (schools);


--
-- Name: staff_ind4; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX staff_ind4 ON staff USING btree (username, syear);


--
-- Name: stu_addr_meets_2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX stu_addr_meets_2 ON students_join_address USING btree (address_id);


--
-- Name: stu_addr_meets_3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX stu_addr_meets_3 ON students_join_address USING btree (primary_residence);


--
-- Name: stu_addr_meets_4; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX stu_addr_meets_4 ON students_join_address USING btree (legal_residence);


--
-- Name: student_eligibility_activities_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX student_eligibility_activities_ind1 ON student_eligibility_activities USING btree (student_id);


--
-- Name: student_enrollment_1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX student_enrollment_1 ON student_enrollment USING btree (student_id, enrollment_code);


--
-- Name: student_enrollment_2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX student_enrollment_2 ON student_enrollment USING btree (grade_id);


--
-- Name: student_enrollment_3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX student_enrollment_3 ON student_enrollment USING btree (syear, student_id, school_id, grade_id);


--
-- Name: student_enrollment_6; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX student_enrollment_6 ON student_enrollment USING btree (start_date, end_date);


--
-- Name: student_enrollment_7; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX student_enrollment_7 ON student_enrollment USING btree (school_id);


--
-- Name: student_gpa_calculated_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX student_gpa_calculated_ind1 ON student_gpa_calculated USING btree (marking_period_id, student_id);


--
-- Name: student_gpa_running_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX student_gpa_running_ind1 ON student_gpa_running USING btree (marking_period_id, student_id);


--
-- Name: student_medical_alerts_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX student_medical_alerts_ind1 ON student_medical_alerts USING btree (student_id);


--
-- Name: student_medical_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX student_medical_ind1 ON student_medical USING btree (student_id);


--
-- Name: student_medical_visits_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX student_medical_visits_ind1 ON student_medical_visits USING btree (student_id);


--
-- Name: student_report_card_comments_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX student_report_card_comments_ind1 ON student_report_card_comments USING btree (school_id);


--
-- Name: student_report_card_grades_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX student_report_card_grades_ind1 ON student_report_card_grades USING btree (school_id);


--
-- Name: student_report_card_grades_ind2; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX student_report_card_grades_ind2 ON student_report_card_grades USING btree (student_id);


--
-- Name: student_report_card_grades_ind3; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX student_report_card_grades_ind3 ON student_report_card_grades USING btree (course_period_id);


--
-- Name: student_report_card_grades_ind4; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX student_report_card_grades_ind4 ON student_report_card_grades USING btree (marking_period_id);


--
-- Name: students_barcode; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX students_barcode ON food_service_student_accounts USING btree (barcode);


--
-- Name: students_ind4; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE UNIQUE INDEX students_ind4 ON students USING btree (username);


--
-- Name: students_join_address_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX students_join_address_ind1 ON students_join_address USING btree (student_id);


--
-- Name: students_join_people_ind1; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX students_join_people_ind1 ON students_join_people USING btree (student_id);


--
-- Name: sys_c007322; Type: INDEX; Schema: public; Owner: postgres; Tablespace: 
--

CREATE INDEX sys_c007322 ON students_join_address USING btree (id, student_id, address_id);


--
-- Name: srcg_mp_stats_update; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER srcg_mp_stats_update
    AFTER INSERT OR DELETE OR UPDATE ON student_report_card_grades
    FOR EACH ROW
    EXECUTE PROCEDURE t_update_mp_stats();



---
--- Billing module
---

CREATE SEQUENCE billing_payment_type_seq;
CREATE TABLE billing_payment_type (
  id INTEGER DEFAULT NEXTVAL('billing_payment_type_seq') NOT NULL,
  type_id NUMERIC NOT NULL,
  type_desc VARCHAR(255)
);

CREATE SEQUENCE billing_fee_seq;
CREATE TABLE billing_fee (
  id INTEGER DEFAULT NEXTVAL('billing_fee_seq') NOT NULL,
  fee_id NUMERIC NOT NULL,
  student_id NUMERIC NOT NULL,
  amount NUMERIC NOT NULL,
  module VARCHAR(255) NOT NULL,
  inserted_by VARCHAR(255) NOT NULL,
  waived_date DATE,
  waived_by VARCHAR(255),
  title VARCHAR(255) NOT NULL,
  assigned_date DATE,
  inserted_date DATE,
  due_date DATE,
  comment VARCHAR(255) NOT NULL,
  waived integer DEFAULT 0
);

CREATE SEQUENCE billing_payment_seq;
CREATE TABLE BILLING_PAYMENT (
  payment_id NUMERIC NOT NULL,
  student_id NUMERIC NOT NULL,
  amount NUMERIC NOT NULL,
  payment_type VARCHAR(255) NOT NULL,
  comment VARCHAR(255) NOT NULL,
  payment_date DATE,
  refunded integer DEFAULT 0,
  refund_date DATE
);

DELETE FROM PROFILE_EXCEPTIONS where modname='Billing/fees.php';
DELETE FROM PROFILE_EXCEPTIONS where modname='Billing/reports.php';
DELETE FROM PROFILE_EXCEPTIONS where modname='Billing/billingAdmin.php';

INSERT INTO `PROFILE_EXCEPTIONS` (`profile_id`, `modname`, `can_use`, `can_edit`) VALUES
(0, 'Billing/reports.php', 'Y', NULL),
(1, 'Billing/fees.php', 'Y', 'Y'),
(1, 'Billing/reports.php', 'Y', 'Y'),
(1, 'Billing/billingAdmin.php', 'Y', 'Y');

INSERT INTO BILLING_PAYMENT_TYPE (type_id, type_desc) VALUES
(1, 'Cash'),
(2, 'Debit/Credit Card'),
(3, 'Scholarship');

--
-- PostgreSQL database dump complete
--


