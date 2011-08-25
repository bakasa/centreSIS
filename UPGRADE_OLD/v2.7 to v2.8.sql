-- correct error in highlight color preference
UPDATE program_user_config SET value='660000' where program='Preferences' AND title='HIGHLIGHT' AND value='66000';

-- change color settings to include hash
UPDATE program_user_config SET value='#'||value WHERE title='HIGHLIGHT';
UPDATE program_user_config SET value='#'||value WHERE title='COLOR';

-- change standard student fields titles to tab labels since they are now used as the tab labels
UPDATE student_field_categories SET title='General Info' WHERE id=1;
UPDATE student_field_categories SET title='Addresses & Contacts' WHERE id=3;
UPDATE student_field_categories SET title='Medical' WHERE id=2;

-- change school state and phone to strings consistennt with address table
-- change school phones to string from numeric area code and phone
ALTER TABLE schools ADD column phonex character varying(30);
UPDATE schools set phonex='('||area_code||') '||substring(phone,1,3)||'-'||substring(phone,4,7);
ALTER TABLE schools DROP COLUMN phone;
ALTER TABLE schools RENAME phonex TO phone;
-- change state to longer string
ALTER TABLE schools ADD column statex character varying(10);
UPDATE schools set statex=state;
ALTER TABLE schools DROP COLUMN state;
ALTER TABLE schools RENAME statex TO state;

-- add website to school info
ALTER TABLE schools ADD COLUMN www_address character varying(100);

-- delete orphaned studentss_join_staff records (due to bug)
DELETE FROM students_join_users WHERE NOT exists (SELECT * FROM staff s WHERE s.staff_id=students_join_users.staff_id);

-- make room for standard profiles
SELECT nextval('USER_PROFILES_SEQ');
SELECT nextval('USER_PROFILES_SEQ');
SELECT nextval('USER_PROFILES_SEQ');
UPDATE user_profiles SET id=id+3;
UPDATE profile_exceptions SET profile_id=profile_id+3;
UPDATE staff SET profile_id=profile_id+3 WHERE profile_id IS NOT NULL;
-- tag existing profiles as admin
UPDATE user_profiles SET profile='admin' WHERE profile IS NULL;
-- insert the standard profiles
INSERT INTO user_profiles (id,profile,title) VALUES (0,'student','Student');
INSERT INTO user_profiles (id,profile,title) VALUES (1,'admin','Administrator');
INSERT INTO user_profiles (id,profile,title) VALUES (2,'teacher','Teacher');
INSERT INTO user_profiles (id,profile,title) VALUES (3,'parent','Parent');

-- set teacher and parent users to standard profiles
UPDATE staff SET profile_id=2 WHERE profile='teacher';
UPDATE staff SET profile_id=3 WHERE profile='parent';

COPY profile_exceptions (profile_id, modname, can_use, can_edit) FROM stdin;
0	School_Setup/Schools.php	Y	\N
0	School_Setup/Calendar.php	Y	\N
0	Students/Student.php	Y	\N
0	Students/Student.php&category_id=1	Y	\N
0	Students/Student.php&category_id=3	Y	\N
0	Scheduling/Schedule.php	Y	\N
0	Scheduling/Requests.php	Y	\N
0	Grades/StudentGrades.php	Y	\N
0	Grades/FinalGrades.php	Y	\N
0	Grades/ReportCards.php	Y	\N
0	Grades/Transcripts.php	Y	\N
0	Grades/GPARankList.php	Y	\N
0	Attendance/StudentSummary.php	Y	\N
0	Attendance/DailySummary.php	Y	\N
0	Eligibility/Student.php	Y	\N
0	Eligibility/StudentList.php	Y	\N
1	School_Setup/PortalNotes.php	Y	Y
1	School_Setup/Schools.php	Y	Y
1	School_Setup/Schools.php?new_school=true	Y	Y
1	School_Setup/CopySchool.php	Y	Y
1	School_Setup/MarkingPeriods.php	Y	Y
1	School_Setup/Calendar.php	Y	Y
1	School_Setup/Periods.php	Y	Y
1	School_Setup/GradeLevels.php	Y	Y
1	School_Setup/Rollover.php	Y	Y
1	Students/Student.php	Y	Y
1	Students/Student.php&include=General_Info&student_id=new	Y	Y
1	Students/AssignOtherInfo.php	Y	Y
1	Students/AddUsers.php	Y	Y
1	misc/Export.php	Y	Y
1	Students/AddDrop.php	Y	Y
1	Students/Letters.php	Y	Y
1	Students/MailingLabels.php	Y	Y
1	Students/PrintStudentInfo.php	Y	Y
1	Students/StudentFields.php	Y	Y
1	Students/EnrollmentCodes.php	Y	Y
1	Students/Student.php&category_id=1	Y	Y
1	Students/Student.php&category_id=3	Y	Y
1	Students/Student.php&category_id=2	Y	Y
1	Users/User.php	Y	Y
1	Users/User.php?staff_id=new	Y	Y
1	Users/AddStudents.php	Y	Y
1	Users/Preferences.php	Y	Y
1	Users/Profiles.php	Y	Y
1	Users/Exceptions.php	Y	Y
1	Users/TeacherPrograms.php?include=Grades/InputFinalGrades.php	Y	Y
1	Users/TeacherPrograms.php?include=Grades/Grades.php	Y	Y
1	Users/TeacherPrograms.php?include=Attendance/TakeAttendance.php	Y	Y
1	Users/TeacherPrograms.php?include=Eligibility/EnterEligibility.php	Y	Y
1	Scheduling/Schedule.php	Y	Y
1	Scheduling/Requests.php	Y	Y
1	Scheduling/MassSchedule.php	Y	Y
1	Scheduling/MassRequests.php	Y	Y
1	Scheduling/MassDrops.php	Y	Y
1	Scheduling/ScheduleReport.php	Y	Y
1	Scheduling/RequestsReport.php	Y	Y
1	Scheduling/UnfilledRequests.php	Y	Y
1	Scheduling/IncompleteSchedules.php	Y	Y
1	Scheduling/AddDrop.php	Y	Y
1	Scheduling/PrintSchedules.php	Y	Y
1	Scheduling/PrintRequests.php	Y	Y
1	Scheduling/PrintClassLists.php	Y	Y
1	Scheduling/Courses.php	Y	Y
1	Scheduling/Scheduler.php	Y	Y
1	Grades/ReportCards.php	Y	Y
1	Grades/CalcGPA.php	Y	Y
1	Grades/Transcripts.php	Y	Y
1	Grades/TeacherCompletion.php	Y	Y
1	Grades/GradeBreakdown.php	Y	Y
1	Grades/FinalGrades.php	Y	Y
1	Grades/GPARankList.php	Y	Y
1	Grades/ReportCardCodes.php	Y	Y
1	Grades/FixGPA.php	Y	Y
1	Attendance/Administration.php	Y	Y
1	Attendance/AddAbsences.php	Y	Y
1	Attendance/Percent.php	Y	Y
1	Attendance/Percent.php?list_by_day=true	Y	Y
1	Attendance/DailySummary.php	Y	Y
1	Attendance/StudentSummary.php	Y	Y
1	Attendance/TeacherCompletion.php	Y	Y
1	Attendance/DuplicateAttendance.php	Y	Y
1	Attendance/AttendanceCodes.php	Y	Y
1	Attendance/FixDailyAttendance.php	Y	Y
1	Eligibility/Student.php	Y	Y
1	Eligibility/AddActivity.php	Y	Y
1	Eligibility/StudentList.php	Y	Y
1	Eligibility/TeacherCompletion.php	Y	Y
1	Eligibility/Activities.php	Y	Y
1	Eligibility/EntryTimes.php	Y	Y
2	School_Setup/Schools.php	Y	\N
2	School_Setup/MarkingPeriods.php	Y	\N
2	School_Setup/Calendar.php	Y	\N
2	Students/Student.php	Y	\N
2	Students/AddUsers.php	Y	\N
2	misc/Export.php	Y	\N
2	Students/Student.php&category_id=1	Y	\N
2	Students/Student.php&category_id=3	Y	\N
2	Users/User.php	Y	\N
2	Users/Preferences.php	Y	\N
2	Scheduling/Schedule.php	Y	\N
2	Scheduling/PrintClassLists.php	Y	\N
2	Grades/InputFinalGrades.php	Y	\N
2	Students/Student.php?include=Comments	Y	\N
2	Students/Student.php?include=Comments	Y	\N
2	Grades/ReportCards.php	Y	\N
2	Grades/Grades.php	Y	\N
2	Grades/Assignments.php	Y	\N
2	Grades/AnomalousGrades.php	Y	\N
2	Grades/Configuration.php	Y	\N
2	Grades/ProgressReports.php	Y	\N
2	Grades/StudentGrades.php	Y	\N
2	Grades/FinalGrades.php	Y	\N
2	Grades/ReportCardCodes.php	Y	\N
2	Attendance/TakeAttendance.php	Y	\N
2	Attendance/DailySummary.php	Y	\N
2	Attendance/StudentSummary.php	Y	\N
2	Eligibility/EnterEligibility.php	Y	\N
3	School_Setup/Schools.php	Y	\N
3	School_Setup/Calendar.php	Y	\N
3	Students/Student.php	Y	\N
3	Students/Student.php&category_id=1	Y	\N
3	Students/Student.php&category_id=3	Y	\N
3	Users/User.php	Y	\N
3	Users/Preferences.php	Y	\N
3	Scheduling/Schedule.php	Y	\N
3	Scheduling/Requests.php	Y	\N
3	Grades/StudentGrades.php	Y	\N
3	Grades/FinalGrades.php	Y	\N
3	Grades/ReportCards.php	Y	\N
3	Grades/Transcripts.php	Y	\N
3	Grades/GPARankList.php	Y	\N
3	Attendance/StudentSummary.php	Y	\N
3	Attendance/DailySummary.php	Y	\N
3	Eligibility/Student.php	Y	\N
3	Eligibility/StudentList.php	Y	\N
\.

-- uncomment before upgrading if using food service
--COPY profile_exceptions (profile_id, modname, can_use, can_edit) FROM stdin;
--0	Food_Service/Accounts.php	Y	\N
--0	Food_Service/Statements.php	Y	\N
--0	Food_Service/DailyMenus.php	Y	\N
--0	Food_Service/MenuItems.php	Y	\N
--1	Food_Service/Accounts.php	Y	Y
--1	Food_Service/Statements.php	Y	Y
--1	Food_Service/Transactions.php	Y	Y
--1	Food_Service/ServeMenus.php	Y	Y
--1	Food_Service/ActivityReport.php	Y	Y
--1	Food_Service/TransactionsReport.php	Y	Y
--1	Food_Service/MenuReports.php	Y	Y
--1	Food_Service/Reminders.php	Y	Y
--1	Food_Service/DailyMenus.php	Y	Y
--1	Food_Service/MenuItems.php	Y	Y
--1	Food_Service/Menus.php	Y	Y
--1	Food_Service/Kiosk.php	Y	Y
--2	Food_Service/Accounts.php	Y	\N
--2	Food_Service/Statements.php	Y	\N
--2	Food_Service/DailyMenus.php	Y	\N
--2	Food_Service/MenuItems.php	Y	\N
--3	Food_Service/Accounts.php	Y	\N
--3	Food_Service/Statements.php	Y	\N
--3	Food_Service/DailyMenus.php	Y	\N
--3	Food_Service/MenuItems.php	Y	\N
--\.

-- uncomment before upgrading if using discipline
--COPY profile_exceptions (profile_id, modname, can_use, can_edit) FROM stdin;
--1	Discipline/MakeReferral.php	Y	Y
--1	Discipline/Referrals.php	Y	Y
--1	Discipline/CategoryBreakdown.php	Y	Y
--1	Discipline/CategoryBreakdownTime.php	Y	Y
--1	Discipline/StudentFieldBreakdown.php	Y	Y
--1	Discipline/ReferralLog.php	Y	Y
--1	Discipline/ReferralForm.php	Y	Y
--2	Discipline/MakeReferral.php	Y	Y
--2	Discipline/Referrals.php	Y	Y
--\.

-- uncomment before upgrading if using student billing
--COPY profile_exceptions (profile_id, modname, can_use, can_edit) FROM stdin;
--1	Student_Billing/StudentFees.php	Y	Y
--1	Student_Billing/StudentPayments.php	Y	Y
--1	Student_Billing/MassAssignFees.php	Y	Y
--1	Student_Billing/MassAssignPayments.php	Y	Y
--1	Student_Billing/StudentBalances.php	Y	Y
--1	Student_Billing/DailyTransactions.php	Y	Y
--1	Student_Billing/Statements.php	Y	Y
--1	Student_Billing/Fees.php	Y	Y
--\.

-- add permissions for existing non-standard student field categories
INSERT INTO profile_exceptions (profile_id,modname,can_use,can_edit) SELECT '0','Students/Student.php&category_id='||id,'Y',NULL FROM student_field_categories WHERE id>3;
INSERT INTO profile_exceptions (profile_id,modname,can_use,can_edit) SELECT '1','Students/Student.php&category_id='||id,'Y','Y' FROM student_field_categories WHERE id>3;
INSERT INTO profile_exceptions (profile_id,modname,can_use,can_edit) SELECT '2','Students/Student.php&category_id='||id,'Y',NULL FROM student_field_categories WHERE id>3;
INSERT INTO profile_exceptions (profile_id,modname,can_use,can_edit) SELECT '3','Students/Student.php&category_id='||id,'Y',NULL FROM student_field_categories WHERE id>3;

-- remove orphaned profiles and permissions records
DELETE FROM profile_exceptions WHERE modname NOT IN (SELECT modname FROM profile_exceptions WHERE profile_id=1) AND profile_id>3;
DELETE FROM staff_exceptions WHERE modname NOT IN (SELECT modname FROM profile_exceptions WHERE profile_id=1);

-- convert negative logic profiles and permissions to positive logic
INSERT INTO staff_exceptions (modname,can_use,can_edit,user_id) SELECT modname,'Y','Y',s.staff_id FROM profile_exceptions pe,staff s WHERE pe.profile_id=1 AND s.profile='admin' AND s.profile_id IS NULL;
INSERT INTO profile_exceptions (modname,can_use,can_edit,profile_id) SELECT DISTINCT pe.modname,'Y','Y',s.profile_id FROM profile_exceptions pe, profile_exceptions s WHERE pe.profile_id=1 AND s.profile_id>3;

UPDATE staff_exceptions SET can_use=NULL WHERE can_use='Y' AND EXISTS (SELECT se.modname FROM staff_exceptions se WHERE se.user_id=staff_exceptions.user_id AND se.modname=staff_exceptions.modname AND se.can_use='N');
UPDATE staff_exceptions SET can_edit=NULL WHERE can_edit='Y' AND EXISTS (SELECT se.modname FROM staff_exceptions se WHERE se.user_id=staff_exceptions.user_id AND se.modname=staff_exceptions.modname AND se.can_edit='N');
UPDATE profile_exceptions SET can_use=NULL WHERE can_use='Y' AND EXISTS (SELECT pe.modname FROM profile_exceptions pe WHERE pe.profile_id=profile_exceptions.profile_id AND pe.modname=profile_exceptions.modname AND pe.can_use='N') AND profile_id>3;
UPDATE profile_exceptions SET can_edit=NULL WHERE can_edit='Y' AND EXISTS (SELECT pe.modname FROM profile_exceptions pe WHERE pe.profile_id=profile_exceptions.profile_id AND pe.modname=profile_exceptions.modname AND pe.can_edit='N') AND profile_id>3;

UPDATE profile_exceptions SET can_edit=NULL FROM student_field_categories sfc WHERE profile_id=2 AND modname='Students/Student.php&category_id='||sfc.id AND sfc.allow_teacher_modify='Y';

DELETE FROM staff_exceptions WHERE can_use!='Y' OR can_edit!='Y';
DELETE FROM profile_exceptions WHERE can_use!='Y' OR can_edit!='Y';
