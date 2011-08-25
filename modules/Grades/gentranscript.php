<?php
require('fpdf.php');
require_once('../../config.inc.php');
require_once('../../database.inc.php');

// Load functions.
if($handle = opendir("$CentrePath/functions"))
{
    if(!is_array($IgnoreFiles))
        $IgnoreFiles=Array();

    while (false !== ($file = readdir($handle)))
    {
        // if filename isn't '.' '..' or in the Ignore list... load it.
        if($file != "." && $file != ".." && !in_array($file,$IgnoreFiles))
            require_once("$CentrePath/functions/$file");
    }
}

//these will be replaced by checkboxes on form
$creditmp='semester';
$gpamp = 'semester';
$overall_cumulative_gpa = 'CUM_UNWEIGHTED_GPA';
$showStudentPic = $_REQUEST['showstudentpic'];
$showSAT = $_REQUEST['showsat'];;
$showMPcomments = $_REQUEST['showmpcomments'];;

$studataquery = "select
s.student_id 
, s.first_name
, s.last_name
, s.middle_name
, s.custom_200000000 as gender
, s.custom_200000004 as birthdate".
//, s.custom_200000012 as estgraddate
", a.address
, a.city
, a.state
, a.zipcode
, a.phone
, a.mail_address
, a.mail_city
, a.mail_state
, a.mail_zipcode
, (select start_date from student_enrollment where student_id = s.student_id order by syear, start_date limit 1) as init_enroll
, (select sgl.short_name from school_gradelevels sgl join student_enrollment se on (sgl.id = se.grade_id) where se.syear = ".$_REQUEST['syear']." and se.student_id = s.student_id and (se.end_date is null or se.start_date < se.end_date) order by se.start_date desc limit 1) as grade_level
from 
students s  
left outer join students_join_address sja on (sja.student_id = s.student_id)
left outer join address a on (a.address_id = sja.address_id) "; 

function transcript_mp_cmp($a, $b){
    //need to return -1. 0 or 1 as less, equal or greater
    $a_id = $a[1]['MARKING_PERIOD_ID'];
    $b_id = $b[1]['MARKING_PERIOD_ID'];
    $a_parent = $a[1]['PARENT_ID'];
    $b_parent = $b[1]['PARENT_ID'];
    $a_end = $a[1]['END_DATE'];
    $b_end = $b[1]['END_DATE'];
    $test = 1;
    if ($b_id == $a_parent || $b_parent == -1)
        return -1;
    elseif ($a_id == $b_parent || $a_parent == -1)
        return 1;
    elseif ($a_end < $b_end)
        return -1;
    elseif ($b_end < $a_end)
        return 1;
    else 
        return 0;
}

class PDF extends FPDF{

    var $styles;
    

    function SetStyle($style){
        
        $this->SetTextColor($this->styles[$style]['fontcolor'][0],
                            $this->styles[$style]['fontcolor'][1],
                            $this->styles[$style]['fontcolor'][2]);
        $this->SetFillColor($this->styles[$style]['bgcolor'][0],
                            $this->styles[$style]['bgcolor'][1],
                            $this->styles[$style]['bgcolor'][2]);
        $this->SetFont($this->styles[$style]['font'],
                       $this->styles[$style]['fontstyle'],
                       $this->styles[$style]['fontsize']);
    }
    function AddStyle($name, $style_arr){
        $this->styles[$name] = $style_arr;
    }
    
    function RoundedRect($x, $y, $w, $h,$r, $text = '', $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        $lx = $x;//left x
        $uy = $y;//upper y
        $rx = $x + $w;//right x
        $ly = $y + $h;//lower y
        $tlx = $lx + $r;
        $tly = $uy + $this->FontSizePt + $r;
        
        if($style=='F')
            $op='f';
        elseif($style=='FD' or $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2f %.2f m',($x+$r)*$k,($hp-$y)*$k ));
        $xc = $x+$w-$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2f %.2f l', $xc*$k,($hp-$y)*$k ));

        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2f %.2f l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2f %.2f l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2f %.2f l',($x)*$k,($hp-$yc)*$k ));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
        $this->Text($tlx, $tly, $text);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }
    function drawTextBox($strText, $w, $h, $xi=null, $yi=null, $align='L', $valign='T', $border=1)
    {
        if (!$xi)
            $xi=$this->GetX();
        if (!$yi)
            $yi=$this->GetY();
        
        $hrow=$this->FontSize;
        $textrows=$this->drawRows($w,$hrow,$strText,0,$align,0,0,0);
        $maxrows=floor($h/$this->FontSize);
        $rows=min($textrows,$maxrows);

        $dy=0;
        if (strtoupper($valign)=='M')
            $dy=($h-$rows*$this->FontSize)/2;
        if (strtoupper($valign)=='B')
            $dy=$h-$rows*$this->FontSize;

        $this->SetY($yi+$dy);
        $this->SetX($xi);

        $this->drawRows($w,$hrow,$strText,0,$align,0,$rows,1);

        if ($border==1)
            $this->Rect($xi,$yi,$w,$h);
    }

    function drawRows($w,$h,$txt,$border=0,$align='J',$fill=0,$maxline=0,$prn=0)
    {
        $cw=&$this->CurrentFont['cw'];
        if($w==0)
            $w=$this->w-$this->rMargin-$this->x;
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n")
            $nb--;
        $b=0;
        if($border)
        {
            if($border==1)
            {
                $border='LTRB';
                $b='LRT';
                $b2='LR';
            }
            else
            {
                $b2='';
                if(is_int(strpos($border,'L')))
                    $b2.='L';
                if(is_int(strpos($border,'R')))
                    $b2.='R';
                $b=is_int(strpos($border,'T')) ? $b2.'T' : $b2;
            }
        }
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $ns=0;
        $nl=1;
        while($i<$nb)
        {
            //Get next character
            $c=$s[$i];
            if($c=="\n")
            {
                //Explicit line break
                if($this->ws>0)
                {
                    $this->ws=0;
                    if ($prn==1) $this->_out('0 Tw');
                }
                if ($prn==1) {
                    $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
                }
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $ns=0;
                $nl++;
                if($border and $nl==2)
                    $b=$b2;
                if ( $maxline && $nl > $maxline )
                    return substr($s,$i);
                continue;
            }
            if($c==' ')
            {
                $sep=$i;
                $ls=$l;
                $ns++;
            }
            $l+=$cw[$c];
            if($l>$wmax)
            {
                //Automatic line break
                if($sep==-1)
                {
                    if($i==$j)
                        $i++;
                    if($this->ws>0)
                    {
                        $this->ws=0;
                        if ($prn==1) $this->_out('0 Tw');
                    }
                    if ($prn==1) {
                        $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
                    }
                }
                else
                {
                    if($align=='J')
                    {
                        $this->ws=($ns>1) ? ($wmax-$ls)/1000*$this->FontSize/($ns-1) : 0;
                        if ($prn==1) $this->_out(sprintf('%.3f Tw',$this->ws*$this->k));
                    }
                    if ($prn==1){
                        $this->Cell($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill);
                    }
                    $i=$sep+1;
                }
                $sep=-1;
                $j=$i;
                $l=0;
                $ns=0;
                $nl++;
                if($border and $nl==2)
                    $b=$b2;
                if ( $maxline && $nl > $maxline )
                    return substr($s,$i);
            }
            else
                $i++;
        }
        //Last chunk
        if($this->ws>0)
        {
            $this->ws=0;
            if ($prn==1) $this->_out('0 Tw');
        }
        if($border and is_int(strpos($border,'B')))
            $b.='B';
        if ($prn==1) {
            $this->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
        }
        $this->x=$this->lMargin;
        return $nl;
    }
}

$style_h = array('font'=>'Helvetica',
                 'fontcolor'=>array(0,0,0),
                 'bgcolor'=>array(255,255,255),
                 'fontsize'=>18,
                 'fonttype'=>'BI',
                 'borders'=>'B',
                 'alignment'=>'C');
                 
$pdf=new PDF('P', 'pt', 'Letter');
$pdf->AddStyle('header', array('font'=>'Helvetica',
                               'fontcolor'=>array(255,0,0),
                               'bgcolor'=>array(255,255,255),
                               'fontsize'=>18,
                               'fontstyle'=>'BI'));
$pdf->AddStyle('other', array('font'=>'Arial',
                                'fontcolor'=>array(0,0,0),
                                'fontsize'=>6,
                                'fontstyle'=>'B'));
$pdf->AddStyle('other2', array('font'=>'Arial',
                                'bgcolor'=>array(200,200,200),
                                'fontcolor'=>array(0,0,0),
                                'fontsize'=>10,
                                'fontstyle'=>''
                                ));
$pdf->AddStyle('celllabel', array('font'=>'Arial',
                                    'bgcolor'=>array(255,255,255),
                                    'fontcolor'=>array(0,0,0),
                                    'fontsize'=>6,
                                    'fontstyle'=>'B'));
$pdf->AddStyle('celldata', array('font'=>'Arial',
                                    'bgcolor'=>array(255,255,255),
                                    'fontcolor'=>array(0,0,0),
                                    'fontsize'=>8,
                                    'fontstyle'=>''));
$pdf->AddStyle('address', array('font'=>'Arial',
                                    'bgcolor'=>array(255,255,255),
                                    'fontcolor'=>array(0,0,0),
                                    'fontsize'=>10,
                                    'fontstyle'=>''));
$pdf->AddStyle('studentname', array('font'=>'Arial',
                                    'bgcolor'=>array(255,255,255),
                                    'fontcolor'=>array(0,0,0),
                                    'fontsize'=>14,
                                    'fontstyle'=>''));
$pdf->AddStyle('principal', array('font'=>'Arial',
                                    'bgcolor'=>array(255,255,255),
                                    'fontcolor'=>array(0,0,0),
                                    'fontsize'=>12,
                                    'fontstyle'=>'I'));
$pdf->AddStyle('schoolname', array('font'=>'Times',
                                    'bgcolor'=>array(255,255,255),
                                    'fontcolor'=>array(0,0,0),
                                    'fontsize'=>16,
                                    'fontstyle'=>'I'));
                                    

$pdf->SetMargins(18, 18, 594);
$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';
$mp_list = '\''.implode('\',\'',$_REQUEST['mp_type_arr']).'\'';
$sch_info = DBGet(DBQuery('select * from schools where syear = '.$_REQUEST['syear'].' AND id = '.$_REQUEST['SCHOOL_ID']));
$sch_info = $sch_info[1];

$t_grades = DBGet(DBQuery('select * from transcript_grades where student_id in ('.$st_list.') and mp_type in ('.$mp_list.') order by end_date, course_title'),array(),array('STUDENT_ID', 'SYEAR', 'MARKING_PERIOD_ID'));                                     
$s_data = DBGet(DBQuery($studataquery.' where s.student_id in ('.$st_list.') order by last_name, first_name'),array(),array('STUDENT_ID'));
foreach($s_data as $student_id=>$s_info){

    $s_info = $s_info[1];
    $mp_cum_stats = array();
    $grades = $t_grades[$student_id];
    $pdf->AddPage();


    $pdf->SetLineWidth(0.5);

    $blockY = $pdf->GetY();
    $blockX = $pdf->GetX();
    $stu_pic =  $CentrePath.'/'.$StudentPicturesPath.$DefaultSyear.'/'.$student_id.'.JPG';
    $picwidth = 70;
    if (file_exists($stu_pic) && $showStudentPic){
        $pdf->Image($stu_pic,$blockX, $blockY,$picwidth);
        $blockX = $pdf->GetX()+$picwidth+2;
    }
    
    $pdf->SetXY($blockX, $blockY);

    $pdf->SetStyle('studentname');
    $pdf->Cell(168, $pdf->styles['studentname']['fontsize']+2, $s_info['LAST_NAME'].", ".$s_info['FIRST_NAME'],0,1);
    $pdf->SetStyle('address');
    $pdf->SetXY($blockX, $pdf->GetY()+1);
    $pdf->Cell(168, $pdf->styles['address']['fontsize']+2, $s_info['ADDRESS'],0,1);
    $pdf->SetXY($blockX, $pdf->GetY()+1);
    $pdf->Cell(168, $pdf->styles['address']['fontsize']+2, $s_info['CITY'].", ".$s_info['STATE']."  ".$s_info['ZIPCODE'],0,1);
          
    $pdf->SetXY($blockX, $pdf->GetY()+1);                                                                 
    $pdf->SetStyle('celllabel');
    $pdf->Cell(68, 10, 'Date of Birth',1,0,'C',1);
    $pdf->Cell(50, 10, 'Gender',1,0,'C',1);
    $pdf->Cell(50, 10, 'Grade',1,0,'C',1);
    $pdf->Ln();
    $pdf->SetXY($blockX, $pdf->GetY()+1);
    $pdf->SetStyle('celldata');
    $dob = explode('-', $s_info['BIRTHDATE']);
    $pdf->Cell(68, 10, $dob[1].'/'.$dob[2].'/'.$dob[0],0,0,'C',1);   
    $pdf->Cell(50, 10, $s_info['GENDER'],0,0,'C',1);
    $pdf->Cell(50, 10, $s_info['GRADE_LEVEL'],0,0,'C',1);
        
    $logo_pic =  $CentrePath.'/assets/transcript_logo.JPG';
    $picwidth = 70;
    if (file_exists($logo_pic)){
        $pdf->Image($logo_pic,270, 18, 90);        
    }

    $blockX = $pdf->GetX();


    $pdf->SetXY($blockX, $blockY);

    $blockX = 384;
    $blockY = 19;
    $pdf->SetXY($blockX, $blockY);
    $pdf->SetStyle('schoolname');
    $pdf->MultiCell(210, $pdf->styles['schoolname']['fontsize']+2, $sch_info['TITLE'], 0, 1);
    $blockY = $pdf->GetY();
    $pdf->SetXY($blockX, $blockY);
    $pdf->SetStyle('address');
    $pdf->Cell(210, $pdf->styles['address']['fontsize']+2, $sch_info['ADDRESS'], 0, 1);
    $blockY = $pdf->GetY();
    $pdf->SetXY($blockX, $blockY);
    $pdf->Cell(210, $pdf->styles['address']['fontsize']+2, $sch_info['CITY'].", ".$sch_info['STATE']."  ".$sch_info['ZIPCODE'], 0, 1);
    $blockY = $pdf->GetY();
    $pdf->SetXY($blockX, $blockY);
    if($sch_info['PHONE']) $pdf->Cell(210, $pdf->styles['address']['fontsize']+2, "Phone: ".$sch_info['PHONE'], 0, 1);
    $blockY = $pdf->GetY();
    $pdf->SetXY($blockX, $blockY);
    if($sch_info['WWW_ADDRESS']) $pdf->Cell(210, $pdf->styles['address']['fontsize']+2, "Website: ".$sch_info['WWW_ADDRESS'], 0, 1);
    $blockY = $pdf->GetY();
    $pdf->SetXY($blockX, $blockY);
    if($sch_info['SCHOOL_NUMBER']) $pdf->Cell(210, $pdf->styles['address']['fontsize']+2, "CEEB: ".$sch_info['SCHOOL_NUMBER'], 0, 1);
    //$blockY = $pdf->GetY();
    //$pdf->SetXY($blockX, $blockY);
    //if($sch_info['CEEB']) $pdf->Cell(210, $pdf->styles['address']['fontsize']+2, "CEEB: ".$sch_info['CEEB'], 0, 1);
    $pdf->Ln();
    $blockY = $pdf->GetY();
    $pdf->SetXY($blockX, $blockY);
    $pdf->SetStyle('principal');
    $pdf->Cell(210, $pdf->styles['principal']['fontsize']+2, $sch_info['PRINCIPAL'], 0, 1);
    $gpablockY = ($pdf->GetY()-20 < 120)?120:$pdf->GetY()-20;
    $gradeblockY = $gpablockY + 39;
    //$gpablockY = ($gradeblockY - 39 < 120)?120:$gradeblockY-39;
	
    //put rounded box around school data
    $pdf->RoundedRect(372, 19, (8.5*72)-372-18, $pdf->GetY()-7, 5,'','');
    //put it around course specifics too
    $pdf->RoundedRect(18,$gradeblockY,576, 436, 5,'','');  //replaced 159 with $gradeblockY
    

    $garray = array();
    foreach($grades as $syear=>$rec){
        //when running through each one of these we need to sort the marking periods 
        //for the students' grades on a per student, per year basis
        uasort($rec, "transcript_mp_cmp");
        $mplist = array();
        $classgrades = array();
        foreach($rec as $mp_id=>$graderecs){
            //TODO:  add marking period comment to this?
            $mplist[$mp_id]['name'] = $graderecs[1]['SHORT_NAME'];
            $mplist[$mp_id]['comments'] = $graderecs[1]['MP_COMMENTS'];
            $mplist[$mp_id]['mptype'] = $graderecs[1]['MP_TYPE'];
            foreach($graderecs as $graderec){
                $classgrades[$graderec['COURSE_TITLE']][$mp_id] = $graderec;
                                
            }
            
            
            
        }
        $garray[$syear]['mp_list'] = $mplist;
        $garray[$syear]['grades'] = $classgrades;  
    }
    
    $xpos=19;
    $ypos=$gradeblockY+1;
    $yearcolnum = 0;
    
    //$pdf->Line($xpos+36, 190, $xpos+(8*72)-36, 190);
    $total_cred_att = 0;
    $total_cred_earned = 0;
    $total_ex_cred_att = 0;
    $total_ex_cred_earned = 0;
    $total_gradepoints = 0;
    $total_grade_count = 0;
    $origypos = $ypos;
    
    //now lets go through the grades
    foreach($garray as $syear=>$yinfo){
        $mplist = $yinfo['mp_list'];
        $grades = $yinfo['grades'];
        //lets start with 2" available for grades columns
        //$gcolx = (2*72)/count($mplist);
        $gcolx = 26;
        $growh = 10;
        
        $mp_comment = '';
        //$year_gradepoints = 0;
        foreach($mplist as $mpid=>$mpdata){
            if($mpdata['mptype']=='year')
                $mp_comment = $mpdata['comments'];
        }
        if($showMPcomments && $mp_comment)
            $headerheight = 30;
        else
            $headerheight = 15;
        //now we need to see if there is enough room for the year's data
        if (((count($grades)*$growh)+$ypos+$headerheight) > 528 && $yearcolnum == 0){
            //we need to move to the second column
            $colnum = 1;
            $xpos = 323;
            $ypos = $origypos;
        }
        $pdf->SetXY($xpos, $ypos);
        //store x,y positions for use as header
        $hxpos = $xpos;
        $hypos = $ypos;

        
        //now skip an area for the header, we'll fill it in later.

        $pdf->Ln($headerheight);
        $pdf->SetX($xpos);
        
                                                                        
        //add labels for grade columns
        $pdf->SetStyle('celllabel');
        
        $pdf->Cell(270, $growh, 'Course', 'B', '', 'L', 1);
        $colnum = 1;
        $pdf->SetX($xpos+270-((count($mplist)+1)*$gcolx));
        $year_cred_att = 0;
        $year_cred_earned = 0;
        $year_ex_cred_att = 0;
        $year_ex_cred_earned = 0;
        //$year_gradepoints = 0;
        foreach($mplist as $mpid=>$mpdata){
            if($colnum%2)
                $pdf->SetFillColor(240, 240, 240);
            else
                $pdf->SetFillColor(255, 255, 255);    
            $pdf->Cell($gcolx, $growh, $mpdata['name'], 'B', '', 'C', 1);
            $colnum += 1;
        }
        //credit column
        $pdf->SetFillColor(255, 255, 255);    
        $pdf->Cell($gcolx, $growh, 'Credit', 'B', '', 'C', 1);
        
        $pdf->Ln($growh+1);
        $pdf->SetX($xpos);
        $pdf->SetStyle('celldata');
        foreach($grades as $classname=>$classgrades){
            
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Cell((1.75*72), $growh, $classname, '', '', '', 1);
            $colnum = 1;
            $pdf->SetX($xpos+270-((count($mplist)+1)*$gcolx));
            $class_cred_earned = 0;
            $class_cred_att = 0;
            $class_ex_cred_earned = 0;
            $class_ex_cred_att = 0;
            //$class_gradepoints = 0;
            foreach($mplist as $mpid=>$mpname){
                if (!$mp_cum_stats[$mpid] && $classgrades[$mp_id])
                    $mp_cum_stats[$mpid] = array(
                                        'CUM_WEIGHTED_GPA'=>$classgrades[$mp_id]['CUM_WEIGHTED_GPA'],
                                        'CUM_UNWEIGHTED_GPA'=>$classgrades[$mp_id]['CUM_UNWEIGHTED_GPA'],
                                        'CUM_CR_WEIGHTED_GPA'=>$classgrades[$mp_id]['CUM_WEIGHTED_GPA'],
                                        'CUM_CR_UNWEIGHTED_GPA'=>$classgrades[$mp_id]['CUM_UNWEIGHTED_GPA'],
                                        'CUM_RANK'=>$classgrades[$mp_id]['CUM_RANK'],
                                        'CLASS_SIZE'=>$classgrades[$mp_id]['CLASS_SIZE']
                                        
                                        );
                if ($classgrades[$mpid]['GP_SCALE'] > 0){
                    if($classgrades[$mpid]['MP_TYPE']=='semester'){
                        $class_cred_att += $classgrades[$mpid]['CREDIT_ATTEMPTED'];
                        $class_cred_earned += $classgrades[$mpid]['CREDIT_EARNED'];
                      } elseif($classgrades[$mpid]['MP_TYPE']=='year')  {
                        $class_cred_att = $classgrades[$mpid]['CREDIT_ATTEMPTED'];
                        $class_cred_earned = $classgrades[$mpid]['CREDIT_EARNED'];
                       }
                } else {
                    $class_ex_cred_att += $classgrades[$mpid]['CREDIT_ATTEMPTED'];
                    $class_ex_cred_earned += $classgrades[$mpid]['CREDIT_EARNED'];
                    
                }

                if($colnum%2)
                    $pdf->SetFillColor(240, 240, 240);
                else
                    $pdf->SetFillColor(255, 255, 255);
                if ($classgrades[$mpid]['GRADE_PERCENT'])
                    $percentgrade = round($classgrades[$mpid]['GRADE_PERCENT']);
                else
                    $percentgrade = '';
                if (!$classgrades[$mpid]['GRADE_LETTER'])
                    $lettergrade = '';
                else
                    $lettergrade = $classgrades[$mpid]['GRADE_LETTER'];    
                $pdf->Cell($gcolx, $growh, $percentgrade." ".$lettergrade, '', '', '', 1);
                $colnum += 1;
                if ($classgrades[$mpid]['GRADE_LEVEL_SHORT']) $gradelevel = $classgrades[$mpid]['GRADE_LEVEL_SHORT'];

            }
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Cell($gcolx, $growh, ($class_cred_earned+$class_ex_cred_earned > 0) ? sprintf('%01.2f', $class_cred_earned+$class_ex_cred_earned) : '--', '', '', 'R', 1);
            
            $year_cred_att += $class_cred_att;
            $year_cred_earned += $class_cred_earned;
            $year_ex_cred_att += $class_ex_cred_att;
            $year_ex_cred_earned += $class_ex_cred_earned;
            $year_gradepoints += $class_gradepoints;
            $pdf->Ln();
            $pdf->SetX($xpos);    
        }
        $total_cred_att += $year_cred_att;
        $total_cred_earned += $year_cred_earned;
        $total_ex_cred_att += $year_ex_cred_att;
        $total_ex_cred_earned += $year_ex_cred_earned;
        //bjj not sure we should show GPA here -- $pdf->Cell(3.75*72,10, "Credit Attempted: ".sprintf('%01.2f', $year_cred_att+$year_ex_cred_att)."  Credit Earned: ".sprintf('%01.2f', $year_cred_earned+$year_ex_cred_earned)."   GPA: ".sprintf('%1.3f', $year_gradepoints/$year_cred_att),'', 'B', 'C');
        $pdf->Cell(3.75*72,10, "Credit Attempted: ".sprintf('%01.2f', $year_cred_att+$year_ex_cred_att)."  Credit Earned: ".sprintf('%01.2f', $year_cred_earned+$year_ex_cred_earned), 'B', 'B', 'C');
        $pdf->Ln(15);
        $ypos = $pdf->GetY();
        //now we'll fill in header
        $pdf->SetXY($hxpos, $hypos);
        $pdf->SetStyle('other2');
        $pdf->Cell((3.75*72), 14, sprintf('%s-%s'.'  Grade: '.$gradelevel, $syear, $syear+1), '', '', 'C');
        if ($showMPcomments && $mp_comment )
            $pdf->Ln();
            $pdf->SetX($xpos);
            $pdf->Cell((3.75*72), 14, $mp_comment, '', '', 'C');
        //on next loop, we'll be back where we left off

    }
    
    //output Cumulative GPA, class rank, credit attemtped and earned.
    $pdf->SetXY(18, $gpablockY); //159-120 = 39
    $pdf->SetStyle('celldata');
    // GPA and CLASS Rank IS DISPLAYED WITH THE NEXT LINE OF CODE.
    $pdf->Cell(3.75*72, 16, "GPA: ".sprintf('%01.2f', round($mp_cum_stats[$mp_id]['CUM_UNWEIGHTED_GPA'], 2)).(($mp_cum_stats[$mp_id]['CUM_RANK'])?"  Class Rank: ".$mp_cum_stats[$mp_id]['CUM_RANK']." of ".$mp_cum_stats[$mp_id]['CLASS_SIZE']:''), 0, 1, 'C');
    $pdf->Cell(3.75*72, 16, "Total Credit -- Attempted: ".sprintf('%01.2f', $total_cred_att+$total_ex_cred_att)."  Earned: ".sprintf('%01.2f', $total_cred_earned+$total_ex_cred_earned), 0, 'B', 'C');
    $pdf->RoundedRect(18,$gpablockY,3.75*72, 32, 5,'','');
                        
    //build SAT test scores box            
    if ($showSAT){
        $SAT_RET = DBGet(DBQuery("select sts.test_date, stc.category, sts.score from student_test_scores sts join student_test_categories stc on (stc.id = sts.test_category_id) where student_id = $student_id order by test_date"),array(),array('TEST_DATE', 'CATEGORY'));
        
        if ($SAT_RET){
            $blockX = 18;
            $blockY = 600;
            $pdf->SetXY($blockX, $blockY);
            $pdf->SetStyle('celllabel');
            $pdf->Cell(220, 14, 'SAT', 'B', 1, 'C');
            $pdf->Cell(70, 12, 'Date', 'BR', 0, 'C');
            $pdf->Cell(50, 12, 'Verbal', 'BR', 0, 'C');
            $pdf->Cell(50, 12, 'Math', 'BR', 0, 'C');
            $pdf->Cell(50, 12, 'Writing', 'B', 1, 'C');
            $pdf->SetStyle('celldata');
            
            foreach($SAT_RET as $testdate=>$category){
                $date_arr = explode('-', $testdate);
                $pdf->Cell(70, 12, $date_arr[1].'/'.$date_arr[0], 'RB', 0, 'C');
                $pdf->Cell(50, 12, $category['Verbal'][1]['SCORE'], 'RB', 0, 'C');
                $pdf->Cell(50, 12, $category['Math'][1]['SCORE'], 'RB', 0, 'C');
                $pdf->Cell(50, 12, $category['Writing'][1]['SCORE'], 'B', 1, 'C'); 
            }
        }
    }
     

    //594.774
    $pdf->SetXY(317,650);
    $pdf->SetStyle('celllabel');

    $pdf->Cell(260, 30, '', 'B', 0, 'C');
    $pdf->SetXY(317,680);
    $pdf->Cell(260, 8, 'Signature', '', 0, 'C');
    $pdf->SetXY(317,688);
    $pdf->Cell(260, 30, '', 'B', 0, 'C');
    $pdf->SetXY(317,718);
    $pdf->Cell(260, 8, 'Title', '', 0, 'C');

     
    //$pdf->Text(72, 74, 'TEST');
}
$pdf->Output(); 
?>
