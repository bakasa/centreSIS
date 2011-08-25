alter table custom_fields add column select_options2 varchar(10000);
update custom_fields set select_options2 = select_options;
alter table custom_fields drop column select_options;
alter table custom_fields rename select_options2 to select_options;

alter table students add column language2 varchar(100);
update students set language2=language;
alter table students drop column language;
alter table students rename column language2 to language;

ALTER TABLE STUDENTS ADD COLUMN NAME_SUFFIX VARCHAR(3);
ALTER TABLE STUDENTS ADD COLUMN NICKNAME VARCHAR(50);