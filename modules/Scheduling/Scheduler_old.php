<?php
if($_REQUEST['modname']=='Scheduling/Scheduler.php')
	DrawHeader(ProgramTitle());

if($_REQUEST['modname']=='Scheduling/Scheduler.php' && !$_REQUEST['run'])
	$function = 'Prompt';
else
	$function = 'returnTrue';

if($function('Confirm Scheduler Run','Are you sure you want to run the scheduler','<TABLE><TR><TD><INPUT type=checkbox name=dont_run value=Y></TD><TD>Test Mode</TD></TR><TR><TD><INPUT type=checkbox name=delete value=Y></TD><TD>Delete Current Schedules</TD></TR></TABLE>'))
{
	if($_REQUEST['delete']=='Y')
	{
		DBQuery("DELETE FROM SCHEDULE WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'");
		DBQuery("UPDATE COURSE_PERIODS SET FILLED_SEATS='0' WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'");
	}

	flush();
	ini_set('MAX_EXECUTION_TIME',0);
	$_CENTRE['DEBUG'] = false;
	if($_REQUEST['dont_run'] && !$_REQUEST['run'])
		$_SCHEDULER['dont_run'] = true;
	// DOESN'T HANDLE SLICES

	// GET REQUESTS
	$sql = "SELECT
				REQUEST_ID,STUDENT_ID,COURSE_ID,COURSE_WEIGHT,
				MARKING_PERIOD_ID FROM SCHEDULE_REQUESTS r
			WHERE
				SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'
			";
	//if(!$_SCHEDULER['dont_run'])
	$sql .= "AND NOT EXISTS (SELECT '' FROM SCHEDULE s WHERE s.SYEAR=r.SYEAR AND s.STUDENT_ID=r.STUDENT_ID AND s.COURSE_ID=r.COURSE_ID AND s.COURSE_WEIGHT=r.COURSE_WEIGHT AND ('".DBDate()."' BETWEEN s.START_DATE AND s.END_DATE OR s.END_DATE IS NULL)) ";

	if($_REQUEST['_SCHEDULER_student_id'])
		$_SCHEDULER['student_id'] = $_REQUEST['_SCHEDULER_student_id'];

	if($_SCHEDULER['student_id'])
		$sql .= " AND STUDENT_ID='$_SCHEDULER[student_id]'";

	$QI = DBQuery($sql);
	$requests_RET = DBGet($QI);

	// GET SEMESTERS & QUARTERS
	$QI = DBQuery("SELECT MARKING_PERIOD_ID,PARENT_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'");
	$mp_RET = DBGet($QI,array(),array('PARENT_ID'));

	// GET A MARKING_PERIOD_ID TO CHECK FOR FY
	foreach($mp_RET as $semester_id=>$mp)
		$first_quarter_id[] = $mp[1]['MARKING_PERIOD_ID'];

	// GET LIST OF COURSE PERIODS
	$QI = DBQuery("SELECT COURSE_PERIOD_ID,COURSE_ID,COURSE_WEIGHT,PERIOD_ID,MP,MARKING_PERIOD_ID,COALESCE(TOTAL_SEATS-COALESCE(FILLED_SEATS,0),0) as AVAILABLE_SEATS,
						MP,MARKING_PERIOD_ID,HOUSE_RESTRICTION,GENDER_RESTRICTION
				   FROM COURSE_PERIODS cp WHERE SYEAR='".UserSyear()."' ORDER BY AVAILABILITY");
	$periods_RET = DBGet($QI,array(),array('COURSE_ID','COURSE_WEIGHT','COURSE_PERIOD_ID'));

	// GET LIST OF STUDENTS WITH THEIR GENDER & HOUSE_CODE
	$sql = "SELECT s.STUDENT_ID,s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,s.GENDER FROM STUDENTS s,STUDENT_ENROLLMENT ssm WHERE (('".DBDate()."' BETWEEN ssm.START_DATE AND ssm.END_DATE OR ssm.END_DATE IS NULL)) AND ssm.SCHOOL_ID='".UserSchool()."' AND ssm.SYEAR='".UserSyear()."' AND s.STUDENT_ID=ssm.STUDENT_ID";
	if($_SCHEDULER['student_id'])
		$sql .= " AND ssm.STUDENT_ID='$_SCHEDULER[student_id]'";
	$QI = DBQuery($sql);
	$students_RET = DBGet($QI,array(),array('STUDENT_ID'));

	// GET FILLED REQUESTS & FILL THE PERIODS
	$sql = "SELECT
				s.STUDENT_ID,s.COURSE_ID,s.COURSE_WEIGHT,s.COURSE_PERIOD_ID,
				s.MP,s.MARKING_PERIOD_ID,cp.PERIOD_ID
			FROM
				SCHEDULE_REQUESTS r,SCHEDULE s,COURSE_PERIODS cp
			WHERE
				r.SYEAR='".UserSyear()."' AND r.SCHOOL_ID='".UserSchool()."'
				AND s.COURSE_ID=r.COURSE_ID AND s.COURSE_WEIGHT=r.COURSE_WEIGHT
				AND s.COURSE_PERIOD_ID = cp.COURSE_PERIOD_ID AND r.STUDENT_ID = s.STUDENT_ID
				AND ('".DBDate()."' BETWEEN s.START_DATE AND s.END_DATE OR s.END_DATE IS NULL)
			";

	if($_SCHEDULER['student_id'])
		$sql .= " AND r.STUDENT_ID='$_SCHEDULER[student_id]'";
	$QI = DBQuery($sql);
	$filled_requests_RET = DBGet($QI);

	if(count($filled_requests_RET))
	{
		foreach($filled_requests_RET as $request)
		{
			// SET THE CURRENT PERIOD AS FILLED -- THIS RECORD GENERATES THE SQL.  NOTICE, MORE THAN ONE WILL BE CREATED FOR FY,SEMESTER COURSES
			if($request['MP']=='FY')
			{
				foreach($mp_RET as $sem=>$mps)
				{
					if(count($mps))
					{
						foreach($mps as $mp)
							$periods_done[$request['STUDENT_ID']][$mp['MARKING_PERIOD_ID']][$request['PERIOD_ID']] = $periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']][$request['COURSE_PERIOD_ID']][1] + array('REQUEST_ID'=>'LOCKED');
					}
				}
			}
			elseif($request['MP']=='SEM')
			{
				foreach($mp_RET[$request['MARKING_PERIOD_ID']] as $mp)
					$periods_done[$request['STUDENT_ID']][$mp['MARKING_PERIOD_ID']][$request['PERIOD_ID']] = $periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']][$request['COURSE_PERIOD_ID']][1] + array('REQUEST_ID'=>'LOCKED');
			}
			else
				$periods_done[$request['STUDENT_ID']][$request['MARKING_PERIOD_ID']][$request['PERIOD_ID']] = $periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']][$request['COURSE_PERIOD_ID']][1] + array('REQUEST_ID'=>'LOCKED');
		}
	}

	echo '<!-- ';
	if(count($requests_RET))
	{
		foreach($requests_RET as $request)
		{
			$request_id = $request['REQUEST_ID'];
			if(count($periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']]))
			{
				foreach($periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']] as $period_key=>$period)
				{
					// CHECK AVAILABLE SEATS
					if($period[1]['AVAILABLE_SEATS']<=0)
						continue;

					// CHECK HOUSE & GENDER RESTRICTIONS
					//if($period[1]['HOUSE_RESTRICTION'] && $period[1]['HOUSE_RESTRICTION']!=$students_RET[$request['STUDENT_ID']][1]['HOUSE'])
						//continue;
					if($period[1]['GENDER_RESTRICTION']!='N' && $period[1]['GENDER_RESTRICTION']!=$students_RET[$request['STUDENT_ID']][1]['GENDER'])
						continue;

					// CHECK IF THE CURRENT PERIOD IS FILLED
					if($period[1]['MP']=='FY')
					{
						foreach($mp_RET as $sem=>$mps)
						{
							foreach($mps as $mp)
							{
								if($periods_done[$request['STUDENT_ID']][$mp['MARKING_PERIOD_ID']][$period[1]['PERIOD_ID']])
									continue 3;
							}
						}
					}
					elseif($period[1]['MP']=='SEM')
					{
						foreach($mp_RET[$period[1]['MARKING_PERIOD_ID']] as $mp)
						{
							if($periods_done[$request['STUDENT_ID']][$mp['MARKING_PERIOD_ID']][$period[1]['PERIOD_ID']])
								continue 2;
						}
					}
					elseif($periods_done[$request['STUDENT_ID']][$period[1]['MARKING_PERIOD_ID']][$period[1]['PERIOD_ID']])
						continue;

					$match[$request_id][] = $period;
				}
			}

			findMatch($match[$request_id],$request_id,$request);
			$c++;
			if($c%5000==0)
			{
				echo '1';
				flush();
			}
		}
		echo "\n\nDone With 1st Run\n\n";
	}

	// --- 2ND RUN ---
	if(count($requests_undone))
	{
		// UNDONE REQUESTS ---
		foreach($requests_undone as $student_id=>$requests)
		{
			// REQUEST IS AN UNDONE REQUEST
			foreach($requests as $request_id=>$request)
			{
				if(count($periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']]))
				{
					// FILLED MATCHES -- $period IS THE PERIOD WE'RE TRYING TO SCHEDULE THE UNDONE REQUEST INTO --- CODE IS COPIED FROM ABOVE ---
					foreach($periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']] as $period_key=>$period)
					{
						// CHECK AVAILABLE SEATS
						if($period[1]['AVAILABLE_SEATS']<=0)
							continue;

						// WE KNOW THE PERIOD IS FILLED -- TRY EMPTYING IT
						$go = false;
						if($periods_done[$request['STUDENT_ID']][$first_quarter_id[0]][$period[1]['PERIOD_ID']]['MP']=='FY')
						{
							$go = true;
							if($periods_done[$request['STUDENT_ID']][$first_quarter_id[0]][$period[1]['PERIOD_ID']]['REQUEST_ID']=='LOCKED')
								$go = false;
							$mp_id = $first_quarter_id[0];
						}
						else
						{
							if($period[1]['MP']=='FY')
							{
								foreach($first_quarter_id as $id)
								{
									if($periods_done[$request['STUDENT_ID']][$id][$period[1]['PERIOD_ID']]['MP']=='SEM')
									{
										if(!$go && $periods_done[$request['STUDENT_ID']][$id][$period[1]['PERIOD_ID']]['REQUEST_ID']!='LOCKED')
										{
											$go = true;
											$mp_id = $id;
										}
										else
										{
											$go = false;
											break;
										}
									}
								}
							}
							elseif($period[1]['MP']=='SEM')
							{
								$id = $period[1]['MARKING_PERIOD_ID'];
								if($periods_done[$request['STUDENT_ID']][$id][$period[1]['PERIOD_ID']]['MP']=='SEM')
								{
									if($periods_done[$request['STUDENT_ID']][$id][$period[1]['PERIOD_ID']]['REQUEST_ID']!='LOCKED')
									{
										$go = true;
										$mp_id = $id;
									}
									else
										$go = false;
								}
							}
						}

						// IF THERE IS ONLY ONE COURSE TO MOVE IN A PERIOD
						if($go)
						{
							$request_id2 = $periods_done[$request['STUDENT_ID']][$mp_id][$period[1]['PERIOD_ID']]['REQUEST_ID'];
							$to_move = $requests_done[$request['STUDENT_ID']][$request_id2];
							$to_move_course = $periods_done[$request['STUDENT_ID']][$mp_id][$period[1]['PERIOD_ID']];

							unset($match[$request_id2]);
							// MOVEABLE SPOT
							foreach($periods_RET[$to_move['COURSE_ID']][$to_move['COURSE_WEIGHT']] as $period2)
							{
								// CHECK AVAILABLE SEATS
								if($period2[1]['AVAILABLE_SEATS']<=0)
									continue;

								// CHECK IF THE CURRENT PERIOD IS FILLED
								if($period2[1]['MP']=='FY')
								{
									foreach($mp_RET as $sem=>$mps)
									{
										foreach($mps as $mp)
										{
											if($periods_done[$to_move['STUDENT_ID']][$mp['MARKING_PERIOD_ID']][$period2[1]['PERIOD_ID']])
												continue 3;
										}
									}
								}
								elseif($period2[1]['MP']=='SEM')
								{
									foreach($mp_RET[$period2[1]['MARKING_PERIOD_ID']] as $mp)
									{
										if($periods_done[$to_move['STUDENT_ID']][$mp['MARKING_PERIOD_ID']][$period2[1]['PERIOD_ID']])
											continue 2;
									}
								}
								elseif($periods_done[$to_move['STUDENT_ID']][$period2[1]['MARKING_PERIOD_ID']][$period2[1]['PERIOD_ID']])
									continue;

								$match[$request_id2][] = $period2;
							}

							// SCHEDULE THE NEW MATCH -- THIS IS MOVING THE COURSE
							if(count($match[$request_id2]))
								$moved = findMatch($match[$request_id2],$request_id2,$to_move);
							else
								$moved = false;

							if($moved)
							{
								unset($match[$request_id]);
								// DELETE THE OLD LOCATION OF THE COURSE
								if($to_move_course['MP']=='FY')
								{
									foreach($mp_RET as $sem=>$mps)
									{
										foreach($mps as $mp)
											unset($periods_done[$to_move['STUDENT_ID']][$mp['MARKING_PERIOD_ID']][$to_move_course['PERIOD_ID']]);
									}
								}
								elseif($to_move_course['MP']=='SEM')
								{
									foreach($mp_RET[$to_move_course['MARKING_PERIOD_ID']] as $mp)
										unset($periods_done[$to_move['STUDENT_ID']][$mp['MARKING_PERIOD_ID']][$to_move_course['PERIOD_ID']]);
								}
								else
									unset($periods_done[$to_move['STUDENT_ID']][$to_move_course['MARKING_PERIOD_ID']][$to_move_course['PERIOD_ID']]);

								//$periods_RET[$to_move['COURSE_ID']][$to_move['COURSE_WEIGHT']][$to_move_course['COURSE_PERIOD_ID']][1]['AVAILABLE_SEATS']++;
								unset($requests_undone[$request['STUDENT_ID']][$request['REQUEST_ID']]);
								$match[$request_id][] = $period;
								findMatch($match[$request_id],$request_id,$request);
								continue 2;
							}
						}
					}
				}
			}

			$c++;
			if($c%5000==0)
			{
				echo '1';
				flush();
			}
		}
	}
	echo "Done With 2nd Run\n\n";

	// DO THE SEMESTER COURSES
	if(count($requests_undone))
	{
		// GET LIST OF SEMESTER COURSE PERIODS
		$QI = DBQuery("SELECT COURSE_PERIOD_ID,COURSE_ID,COURSE_WEIGHT,PERIOD_ID,MP,MARKING_PERIOD_ID,COALESCE(TOTAL_SEATS-COALESCE(FILLED_SEATS,0),0) as AVAILABLE_SEATS,
							MP,MARKING_PERIOD_ID,HOUSE_RESTRICTION,GENDER_RESTRICTION
					   FROM COURSE_PERIODS WHERE SYEAR='".UserSyear()."' AND MARKING_PERIOD_ID!='0' ORDER BY AVAILABILITY");
		$periods_RET = DBGet($QI,array(),array('COURSE_ID','COURSE_WEIGHT','COURSE_PERIOD_ID'));

		foreach($requests_undone as $student_id=>$requests)
		{
			foreach($requests as $request_id=>$request)
			{
				if(count($periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']]))
				{
					foreach($periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']] as $period_key=>$period)
					{
						// CHECK AVAILABLE SEATS
						if($period[1]['AVAILABLE_SEATS']<=0)
							continue;

						// CHECK HOUSE & GENDER RESTRICTIONS
						//if($period[1]['HOUSE_RESTRICTION'] && $period[1]['HOUSE_RESTRICTION']!=$students_RET[$request['STUDENT_ID']][1]['HOUSE'])
							//continue;
						if($period[1]['GENDER_RESTRICTION']!='N' && $period[1]['GENDER_RESTRICTION']!=$students_RET[$request['STUDENT_ID']][1]['GENDER'])
							continue;

						// CHECK IF THE CURRENT PERIOD IS FILLED
						if($period[1]['MP']=='FY')
						{
							foreach($mp_RET as $sem=>$mps)
							{
								foreach($mps as $mp)
								{
									if($periods_done[$request['STUDENT_ID']][$mp['MARKING_PERIOD_ID']][$period[1]['PERIOD_ID']])
										continue 3;
								}
							}
						}
						elseif($period[1]['MP']=='SEM')
						{
							foreach($mp_RET[$period[1]['MARKING_PERIOD_ID']] as $mp)
							{
								if($periods_done[$request['STUDENT_ID']][$mp['MARKING_PERIOD_ID']][$period[1]['PERIOD_ID']])
									continue 2;
							}
						}
						elseif($periods_done[$request['STUDENT_ID']][$period[1]['MARKING_PERIOD_ID']][$period[1]['PERIOD_ID']])
							continue;

						$match[$request_id][] = $period;
					}
				}

				if(findMatch($match[$request_id],$request_id,$request))
					unset($requests_undone[$request['STUDENT_ID']][$request['REQUEST_ID']]);

				$c++;
				if($c%5000==0)
				{
					echo '1';
					flush();
				}
			}
		}
		echo "\n\nDone With Semesters Run\n\n";
	}

	// --- SEMESTERS 2ND RUN ---
	$semester_periods_RET = $periods_RET;
	$QI = DBQuery("SELECT COURSE_PERIOD_ID,COURSE_ID,COURSE_WEIGHT,PERIOD_ID,MP,MARKING_PERIOD_ID,COALESCE(TOTAL_SEATS-COALESCE(FILLED_SEATS,0),0) as AVAILABLE_SEATS,
						MP,MARKING_PERIOD_ID,HOUSE_RESTRICTION,GENDER_RESTRICTION
				   FROM COURSE_PERIODS WHERE SYEAR='".UserSyear()."' ORDER BY AVAILABILITY");
	$periods_RET = DBGet($QI,array(),array('COURSE_ID','COURSE_WEIGHT','COURSE_PERIOD_ID'));

	if(count($requests_undone))
	{
		// UNDONE REQUESTS ---
		foreach($requests_undone as $student_id=>$requests)
		{
			// REQUEST IS AN UNDONE REQUEST
			foreach($requests as $request_id=>$request)
			{
				if(count($semester_periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']]))
				{
					// FILLED MATCHES -- $period IS THE PERIOD WE'RE TRYING TO SCHEDULE THE UNDONE REQUEST INTO --- CODE IS COPIED FROM ABOVE ---
					foreach($semester_periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']] as $period_key=>$period)
					{
						// CHECK AVAILABLE SEATS
						if($period[1]['AVAILABLE_SEATS']<=0)
							continue;

						// WE KNOW THE PERIOD IS FILLED -- TRY EMPTYING IT
						$go = false;
						if($periods_done[$request['STUDENT_ID']][$first_quarter_id[0]][$period[1]['PERIOD_ID']]['MP']=='FY')
						{
							$go = true;
							if($periods_done[$request['STUDENT_ID']][$first_quarter_id[0]][$period[1]['PERIOD_ID']]['REQUEST_ID']=='LOCKED')
								$go = false;
							$mp_id = $first_quarter_id[0];
						}
						else
						{
							if($period[1]['MP']=='FY')
							{
								foreach($first_quarter_id as $id)
								{
									if($periods_done[$request['STUDENT_ID']][$id][$period[1]['PERIOD_ID']]['MP']=='SEM')
									{
										if(!$go && $periods_done[$request['STUDENT_ID']][$id][$period[1]['PERIOD_ID']]['REQUEST_ID']!='LOCKED')
										{
											$go = true;
											$mp_id = $id;
										}
										else
										{
											$go = false;
											break;
										}
									}
								}
							}
							elseif($period[1]['MP']=='SEM')
							{
								$id = $period[1]['MARKING_PERIOD_ID'];
								if($periods_done[$request['STUDENT_ID']][$id][$period[1]['PERIOD_ID']]['MP']=='SEM')
								{
									if($periods_done[$request['STUDENT_ID']][$id][$period[1]['PERIOD_ID']]['REQUEST_ID']!='LOCKED')
									{
										$go = true;
										$mp_id = $id;
									}
									else
										$go = false;
								}
							}
						}

						// IF THERE IS ONLY ONE COURSE TO MOVE IN A PERIOD
						if($go)
						{
							$request_id2 = $periods_done[$request['STUDENT_ID']][$mp_id][$period[1]['PERIOD_ID']]['REQUEST_ID'];
							$to_move = $requests_done[$request['STUDENT_ID']][$request_id2];
							$to_move_course = $periods_done[$request['STUDENT_ID']][$mp_id][$period[1]['PERIOD_ID']];

							unset($match[$request_id2]);
							// MOVEABLE SPOT
							foreach($periods_RET[$to_move['COURSE_ID']][$to_move['COURSE_WEIGHT']] as $period2)
							{
								// CHECK AVAILABLE SEATS
								if($period2[1]['AVAILABLE_SEATS']<=0)
									continue;

								// CHECK IF THE CURRENT PERIOD IS FILLED
								if($period2[1]['MP']=='FY')
								{
									foreach($mp_RET as $sem=>$mps)
									{
										foreach($mps as $mp)
										{
											if($periods_done[$to_move['STUDENT_ID']][$mp['MARKING_PERIOD_ID']][$period2[1]['PERIOD_ID']])
												continue 3;
										}
									}
								}
								elseif($period2[1]['MP']=='SEM')
								{
									foreach($mp_RET[$period2[1]['MARKING_PERIOD_ID']] as $mp)
									{
										if($periods_done[$to_move['STUDENT_ID']][$mp['MARKING_PERIOD_ID']][$period2[1]['PERIOD_ID']])
											continue 2;
									}
								}
								elseif($periods_done[$to_move['STUDENT_ID']][$period2[1]['MARKING_PERIOD_ID']][$period2[1]['PERIOD_ID']])
									continue;

								$match[$request_id2][] = $period2;
							}

							// SCHEDULE THE NEW MATCH -- THIS IS MOVING THE COURSE
							if(count($match[$request_id2]))
								$moved = findMatch($match[$request_id2],$request_id2,$to_move);
							else
								$moved = false;

							if($moved)
							{
								unset($match[$request_id]);
								// DELETE THE OLD LOCATION OF THE COURSE
								if($to_move_course['MP']=='FY')
								{
									foreach($mp_RET as $sem=>$mps)
									{
										foreach($mps as $mp)
											unset($periods_done[$to_move['STUDENT_ID']][$mp['MARKING_PERIOD_ID']][$to_move_course['PERIOD_ID']]);
									}
								}
								elseif($to_move_course['MP']=='SEM')
								{
									foreach($mp_RET[$to_move_course['MARKING_PERIOD_ID']] as $mp)
										unset($periods_done[$to_move['STUDENT_ID']][$mp['MARKING_PERIOD_ID']][$to_move_course['PERIOD_ID']]);
								}
								else
									unset($periods_done[$to_move['STUDENT_ID']][$to_move_course['MARKING_PERIOD_ID']][$to_move_course['PERIOD_ID']]);

								//$periods_RET[$to_move['COURSE_ID']][$to_move['COURSE_WEIGHT']][$to_move_course['COURSE_PERIOD_ID']][1]['AVAILABLE_SEATS']++;
								unset($requests_undone[$request['STUDENT_ID']][$request['REQUEST_ID']]);
								$match[$request_id][] = $period;
								findMatch($match[$request_id],$request_id,$request);
								continue 2;
							}
						}
					}
				}
			}

			$c++;
			if($c%5000==0)
			{
				echo '1';
				flush();
			}
		}
	}
	echo "Done With Semesters 2nd Run\n\n";
	echo '-->';

	if(count($requests_undone))
	{
		foreach($requests_undone as $student_id=>$requests)
		{
			if(count($requests))
			{
				$count++;
				$count_requests += count($requests);
				$table[$count]['STUDENT_ID'] = '<A HREF=Modules.php?modname=Scheduling/Schedule.php&student_id='.$student_id.'>'.$student_id.' - '.$students_RET[$student_id][1]['FULL_NAME'].'</A>';
				$table[$count]['UNSCHEDULED'] = '<TABLE class=LO_field>';
				foreach($requests as $request)
				{
					$seats = 0;
					if(count($periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']]))
					{
						foreach($periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']] as $course_period_id=>$period)
							$seats += $period[1]['AVAILABLE_SEATS'];
					}
					if($seats<1)
						$course_seats[$request['COURSE_ID'].'-'.$request['COURSE_WEIGHT']]++;
					$table[$count]['UNSCHEDULED'].='<TR><TD><LI>'._getCourse($request['COURSE_ID']).' '.$request['COURSE_WEIGHT'].' ('.$seats.' seats / '.count($periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']]).' periods)</LI> </TD></TR>';
					$courses[$request['COURSE_ID'].'-'.$request['COURSE_WEIGHT']]++;
				}
				$table[$count]['UNSCHEDULED'].='</TR></TABLE>';
			}
			else
				unset($requests_undone[$student_id]);
		}
	}
	if($count)
	{
		echo '<b>'.(100-($count*100/count($students_RET))).'% of students could	be scheduled completely<BR>';
		echo 'Total Unfinished Students/Requests: '.$count.' / '.$count_requests.'</b>';
		echo '<TABLE><TR><TD valign=top>';
		ListOutput($table,array('STUDENT_ID'=>'Student','UNSCHEDULED'=>'Unfilled Requests'),'.','.','','',array('sort'=>false,'search'=>false));
		echo '</TD><TD valign=top>';
		$i = 0;
		foreach($courses as $course_id=>$count)
			$courses_list[++$i] = array('COURSE'=>_getCourse(substr($course_id,0,strpos($course_id,'-'))).substr($course_id,strpos($course_id,'-')),'COUNT'=>$count);
		ListOutput($courses_list,array('COURSE'=>'Course','COUNT'=>'#'),'Course with Unscheduled Requests','Courses with Unscheduled Requests','','',array('sort'=>false,'search'=>false));
		$i = 0;
		$courses_list = array();
		if(count($course_seats))
		{
			foreach($course_seats as $course_id=>$count)
				$courses_list[++$i] = array('COURSE'=>_getCourse(substr($course_id,0,strpos($course_id,'-'))).substr($course_id,strpos($course_id,'-')),'COUNT'=>$count);
		}
		ListOutput($courses_list,array('COURSE'=>'Course','COUNT'=>'#'),'Course with No Seats','Courses with No Seats','','',array('sort'=>false,'search'=>false));
		echo '</TD></TR></TABLE>';
	}

	if(!$_SCHEDULER['dont_run'])
	{
		$connection=db_start();
		db_trans_start($connection);

		unset($_SESSION['SCHEDULE']);
		if(count($insert))
		{
			foreach($insert as $student_id=>$requests)
			{
				foreach($requests as $request)
				{
					$sql = "INSERT INTO SCHEDULE (SYEAR,SCHOOL_ID,STUDENT_ID,START_DATE,MODIFIED_DATE,COURSE_ID,COURSE_WEIGHT,COURSE_PERIOD_ID,MP,MARKING_PERIOD_ID) values('".UserSyear()."','".UserSchool()."','".$request['STUDENT_ID']."','".DBDate()."','".DBDate()."','".$request['COURSE_ID']."','".$request['COURSE_WEIGHT']."','".$request['COURSE_PERIOD_ID']."','".$request['MP']."','".$request['MARKING_PERIOD_ID']."')";
					db_trans_query($connection,$sql);
				}
			}
		}
		foreach($periods_RET as $course_id=>$weights)
		{
			foreach($weights as $weight=>$periods)
			{
				foreach($periods as $course_period_id=>$period)
				{
					$sql = "UPDATE COURSE_PERIODS SET FILLED_SEATS=COALESCE(TOTAL_SEATS,0)-'".$period[1]['AVAILABLE_SEATS']."' WHERE COURSE_PERIOD_ID='$course_period_id'";
					db_trans_query($connection,$sql);
				}
			}
		}
		db_trans_commit($connection);

		if(!$count)
			$message = "<IMG SRC=assets/check.gif>All the requests were filled";
		if($_SCHEDULER['student_id'])
			$link = "<A HREF=Modules.php?modname=Scheduling/Schedule.php>View this student's Schedule</A>";
		else
			$link = "<A HREF=Modules.php?modname=Scheduling/ScheduleReport.php>View the Schedule Report</A>";
		DrawHeader($message,$link);
	}
	elseif($_SCHEDULER['student_id'])
	{
		if(!$count)
			$message = "<IMG SRC=assets/check.gif>All of this student's requests can be filled";
		DrawHeader($message,"<A HREF=Modules.php?modname=Scheduling/Scheduler.php&run=true&_SCHEDULER_student_id=$_SCHEDULER[student_id]>Run the Scheduler for this student</A>");
	}
	else
	{
		if(!$count)
			$message = '<IMG SRC=assets/check.gif>All students\' requests can be filled';
		DrawHeader($message,"<A HREF=Modules.php?modname=Scheduling/Scheduler.php&run=true>Run the Scheduler</A>");
	}
}


function findMatch($matches,$request_id,$request)
{	global $insert,$requests_done,$requests_undone,$periods_RET,$mp_RET,$periods_done;

	// FIND THE BEST MATCH
	if(count($matches))
	{
		$best = $matches[0];
		foreach($matches as $period)
		{
			if($period[1]['AVAILABLE_SEATS']>$best[1]['AVAILABLE_SEATS'])
				$best = $period;
		}

		// PERIOD HAS MEET ALL OF THE CRITERIA, SO ADD IT.
		$periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']][$best[1]['COURSE_PERIOD_ID']][1]['AVAILABLE_SEATS']--;

		//print_r($periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']][$best[1]['COURSE_PERIOD_ID']]);

		// SET THE REQUEST AS FILLED
		$insert[$request['STUDENT_ID']][$request['REQUEST_ID']] = $periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']][$best[1]['COURSE_PERIOD_ID']][1] + array('STUDENT_ID'=>$request['STUDENT_ID']);
		$requests_done[$request['STUDENT_ID']][$request['REQUEST_ID']] = $request;

		// SET THE CURRENT PERIOD AS FILLED -- THIS RECORD GENERATES THE SQL.  NOTICE, MORE THAN ONE WILL BE CREATED FOR FY,SEMESTER COURSES
		if($best[1]['MP']=='FY')
		{
			foreach($mp_RET as $sem=>$mps)
			{
				foreach($mps as $mp)
					$periods_done[$request['STUDENT_ID']][$mp['MARKING_PERIOD_ID']][$best[1]['PERIOD_ID']] = $periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']][$best[1]['COURSE_PERIOD_ID']][1] + array('REQUEST_ID'=>$request_id);
			}
		}
		elseif($best[1]['MP']=='SEM')
		{
			foreach($mp_RET[$best[1]['MARKING_PERIOD_ID']] as $mp)
				$periods_done[$request['STUDENT_ID']][$mp['MARKING_PERIOD_ID']][$best[1]['PERIOD_ID']] = $periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']][$best[1]['COURSE_PERIOD_ID']][1] + array('REQUEST_ID'=>$request_id);
		}
		else
			$periods_done[$request['STUDENT_ID']][$best[1]['MARKING_PERIOD_ID']][$best[1]['PERIOD_ID']] = $periods_RET[$request['COURSE_ID']][$request['COURSE_WEIGHT']][$best[1]['COURSE_PERIOD_ID']][1] + array('REQUEST_ID'=>$request_id);

		return true;
	}
	else
	{
		$requests_undone[$request['STUDENT_ID']][$request['REQUEST_ID']] = $request;
		return false;
	}
}

function _getCourse($course_id)
{	global $_CENTRE;

	if(!$_CENTRE['_getCourse'])
	{
		$_CENTRE['_getCourse'] = DBGet(DBQuery("SELECT COURSE_ID,TITLE FROM COURSES WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"),array(),array('COURSE_ID'));
	}

	return $_CENTRE['_getCourse'][$course_id][1]['TITLE'];
}


function returnTrue($arg1,$arg2='',$arg3='')
{
	return true;
}
?>