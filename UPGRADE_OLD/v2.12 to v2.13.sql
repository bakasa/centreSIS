
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


CREATE INDEX school_marking_periods_ind2 ON school_marking_periods USING btree (syear, school_id, start_date, end_date);


CREATE INDEX school_marking_periods_ind1 ON school_marking_periods USING btree (parent_id);


ALTER TABLE ONLY school_marking_periods
    ADD CONSTRAINT school_marking_periods_pkey PRIMARY KEY (marking_period_id);

INSERT INTO school_marking_periods (marking_period_id,syear,mp,school_id,parent_id,title,short_name,sort_order,start_date,end_date,post_start_date,post_end_date,does_grades,does_exam,does_comments,rollover_id) (SELECT marking_period_id,syear,'FY',school_id,NULL,title,short_name,sort_order,start_date,end_date,post_start_date,post_end_date,does_grades,does_exam,does_comments,rollover_id FROM school_years);
INSERT INTO school_marking_periods (marking_period_id,syear,mp,school_id,parent_id,title,short_name,sort_order,start_date,end_date,post_start_date,post_end_date,does_grades,does_exam,does_comments,rollover_id) (SELECT marking_period_id,syear,'SEM',school_id,year_id,title,short_name,sort_order,start_date,end_date,post_start_date,post_end_date,does_grades,does_exam,does_comments,rollover_id FROM school_semesters);
INSERT INTO school_marking_periods (marking_period_id,syear,mp,school_id,parent_id,title,short_name,sort_order,start_date,end_date,post_start_date,post_end_date,does_grades,does_exam,does_comments,rollover_id) (SELECT marking_period_id,syear,'QTR',school_id,semester_id,title,short_name,sort_order,start_date,end_date,post_start_date,post_end_date,does_grades,does_exam,does_comments,rollover_id FROM school_quarters);
INSERT INTO school_marking_periods (marking_period_id,syear,mp,school_id,parent_id,title,short_name,sort_order,start_date,end_date,post_start_date,post_end_date,does_grades,does_exam,does_comments,rollover_id) (SELECT marking_period_id,syear,'PRO',school_id,quarter_id,title,short_name,sort_order,start_date,end_date,post_start_date,post_end_date,does_grades,does_exam,does_comments,rollover_id FROM school_progress_periods);

DROP TABLE school_years;
DROP TABLE school_semesters;
DROP TABLE school_quarters;
DROP TABLE school_progress_periods;

INSERT INTO address (address_id,address) VALUES (0,'No Address');
INSERT INTO students_join_address (id,student_id,address_id) (SELECT -student_id,student_id,0 FROM students_join_people WHERE address_id=0 GROUP BY student_id);
