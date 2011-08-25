alter table attendance_calendar RENAME day_percent to minutes;
update attendance_calendar set minutes='999' where minutes='1';
alter table attendance_calendar drop column comment;

CREATE TABLE CALENDAR_EVENTS
(
ID NUMERIC,
SYEAR NUMERIC(4,0),
SCHOOL_ID NUMERIC,
SCHOOL_DATE DATE,
TITLE VARCHAR(50),
DESCRIPTION VARCHAR(500)
);

CREATE SEQUENCE CALENDAR_EVENTS_SEQ START 1 INCREMENT 1;

alter table gradebook_assignments drop column period_id;

drop table saved_results;

alter table config add column LOGIN VARCHAR(3);
UPDATE CONFIG SET LOGIN='No';