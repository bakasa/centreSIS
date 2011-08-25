-- make gradebook_assignments column assignment_type_id not null
DELETE FROM gradebook_assignments WHERE assignment_type_id IS NULL;
ALTER TABLE gradebook_assignments ALTER COLUMN assignment_type_id SET NOT NULL;

-- force students column username unique
UPDATE students SET username=NULL WHERE exists(SELECT * FROM students s WHERE s.username=students.username AND s.student_id!=students.student_id);
CREATE UNIQUE INDEX students_ind4 ON students USING btree (username);

-- add gradebook_assignment_types columns for sort_order and color
ALTER TABLE gradebook_assignment_types ADD COLUMN sort_order NUMERIC;
ALTER TABLE gradebook_assignment_types ADD COLUMN color VARCHAR(30);

-- add report_card_comment_categories column for color
ALTER TABLE report_card_comment_categories ADD COLUMN color VARCHAR(30);

-- enable the new assignments interface for users of the old
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) SELECT profile_id, 'Grades/Assignments-new.php', can_use, can_edit FROM profile_exceptions WHERE modname='Grades/Assignments.php';
INSERT INTO staff_exceptions (user_id, modname, can_use, can_edit) SELECT user_id, 'Grades/Assignments-new.php', can_use, can_edit FROM staff_exceptions WHERE modname='Grades/Assignments.php';

-- current food service users should delete everything below this point
--    and use modules/Food_Service/upgrade.sql instead

-- create the food service tables
CREATE TABLE food_service_student_accounts (
    student_id numeric NOT NULL,
    account_id numeric NOT NULL,
    discount character varying(25),
    status character varying(25),
    barcode character varying(50)
);

CREATE UNIQUE INDEX students_barcode ON food_service_student_accounts USING btree (barcode);

ALTER TABLE ONLY food_service_student_accounts
    ADD CONSTRAINT food_service_student_accounts_pkey PRIMARY KEY (student_id);

CREATE TABLE food_service_accounts (
    account_id numeric NOT NULL,
    balance numeric(9,2) NOT NULL,
    transaction_id numeric
);

ALTER TABLE ONLY food_service_accounts
    ADD CONSTRAINT food_service_accounts_pkey PRIMARY KEY (account_id);

CREATE TABLE food_service_transactions (
    transaction_id numeric NOT NULL,
    account_id numeric NOT NULL,
    student_id numeric,
    syear numeric(4,0),
    school_id numeric,
    discount character varying(25),
    balance numeric(9,2),
    "timestamp" timestamp(0) without time zone,
    short_name character varying(25),
    description character varying(50),
    seller_id numeric
);

ALTER TABLE ONLY food_service_transactions
    ADD CONSTRAINT food_service_transactions_pkey PRIMARY KEY (transaction_id);

CREATE SEQUENCE food_service_transactions_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

SELECT pg_catalog.setval('food_service_transactions_seq', 1, true);

CREATE TABLE food_service_transaction_items (
    item_id numeric NOT NULL,
    transaction_id numeric NOT NULL,
    amount numeric(9,2),
    discount character varying(25),
    short_name character varying(25),
    description character varying(50)
);

CREATE INDEX food_service_transaction_items_ind1 ON food_service_transaction_items USING btree (transaction_id);

ALTER TABLE ONLY food_service_transaction_items
    ADD CONSTRAINT food_service_transaction_items_pkey PRIMARY KEY (item_id, transaction_id);

CREATE TABLE food_service_staff_accounts (
    staff_id numeric NOT NULL,
    status character varying(25),
    barcode character varying(50),
    balance numeric(9,2) NOT NULL,
    transaction_id numeric
);

CREATE UNIQUE INDEX staff_barcode ON food_service_staff_accounts USING btree (barcode);

ALTER TABLE ONLY food_service_staff_accounts
    ADD CONSTRAINT food_service_staff_accounts_pkey PRIMARY KEY (staff_id);

CREATE TABLE food_service_staff_transactions (
    transaction_id numeric NOT NULL,
    staff_id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    balance numeric(9,2),
    "timestamp" timestamp(0) without time zone,
    short_name character varying(25),
    description character varying(50),
    seller_id numeric
);

ALTER TABLE ONLY food_service_staff_transactions
    ADD CONSTRAINT food_service_staff_transactions_pkey PRIMARY KEY (transaction_id);

CREATE SEQUENCE food_service_staff_transactions_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

SELECT pg_catalog.setval('food_service_staff_transactions_seq', 1, true);

CREATE TABLE food_service_staff_transaction_items (
    item_id numeric NOT NULL,
    transaction_id numeric NOT NULL,
    amount numeric(9,2),
    short_name character varying(25),
    description character varying(50)
);

CREATE INDEX food_service_staff_transaction_items_ind1 ON food_service_staff_transaction_items USING btree (transaction_id);

ALTER TABLE ONLY food_service_staff_transaction_items
    ADD CONSTRAINT food_service_staff_transaction_items_pkey PRIMARY KEY (item_id, transaction_id);

CREATE TABLE food_service_menus (
    menu_id numeric NOT NULL,
    school_id numeric NOT NULL,
    title character varying(25) NOT NULL,
    sort_order numeric
);

ALTER TABLE ONLY food_service_menus
    ADD CONSTRAINT food_service_menus_pkey PRIMARY KEY (menu_id);

CREATE UNIQUE INDEX food_service_menus_title ON food_service_menus USING btree (school_id, title);

CREATE SEQUENCE food_service_menus_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

SELECT pg_catalog.setval('food_service_menus_seq', 1, true);

CREATE TABLE food_service_categories (
    category_id numeric NOT NULL,
    school_id numeric NOT NULL,
    menu_id numeric NOT NULL,
    title character varying(25),
    sort_order numeric
);

ALTER TABLE ONLY food_service_categories
    ADD CONSTRAINT food_service_categories_pkey PRIMARY KEY (category_id);

CREATE UNIQUE INDEX food_service_categories_title ON food_service_categories USING btree (school_id, menu_id, title);

CREATE SEQUENCE food_service_categories_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

SELECT pg_catalog.setval('food_service_categories_seq', 1, true);

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

ALTER TABLE ONLY food_service_items
    ADD CONSTRAINT food_service_items_pkey PRIMARY KEY (item_id);

CREATE UNIQUE INDEX food_service_items_short_name ON food_service_items USING btree (school_id, short_name);

CREATE SEQUENCE food_service_items_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

SELECT pg_catalog.setval('food_service_items_seq', 4, true);

CREATE TABLE food_service_menu_items (
    menu_item_id numeric NOT NULL,
    school_id numeric NOT NULL,
    menu_id numeric NOT NULL,
    item_id numeric NOT NULL,
    category_id numeric,
    sort_order numeric,
    does_count character varying(1)
);

ALTER TABLE ONLY food_service_menu_items
    ADD CONSTRAINT food_service_menu_items_pkey PRIMARY KEY (menu_item_id);

CREATE SEQUENCE food_service_menu_items_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

SELECT pg_catalog.setval('food_service_menu_items_seq', 4, true);

-- create accounts for existing students
INSERT INTO food_service_student_accounts (student_id, account_id, discount, status, barcode) (SELECT student_id, student_id, NULL, NULL, NULL FROM students);
INSERT INTO food_service_accounts (account_id, balance, transaction_id) (SELECT student_id, 0, NULL FROM students);
