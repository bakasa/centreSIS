-- add sort orders to custom fields and categories
ALTER TABLE custom_fields ADD column sort_order numeric;
ALTER TABLE student_field_categories ADD column sort_order numeric;
-- add required to custom fields
ALTER TABLE custom_fields ADD column required character varying(1);

-- add failed login to students and users
ALTER TABLE students ADD column failed_login numeric;
ALTER TABLE staff ADD column failed_login numeric;

-- add title to users
ALTER TABLE staff ADD column title character varying(5);


-- fix possible errors due to gradebook configuration bug
UPDATE program_user_config SET value='ASSIGNMENT_ID' WHERE value='UP' AND program='Gradebook' AND title='ASSIGNMENT_SORTING';

--
-- Name: portal_notes; Type: TABLE; Schema: public; Owner: postgres; Tablespace:
--

CREATE TABLE portal_notes (
    id numeric,
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
-- Name: address_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace:
--

ALTER TABLE ONLY portal_notes
    ADD CONSTRAINT portal_notes_pkey PRIMARY KEY (id);

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
