<?php
	#include namespaces
	include_once('../lib/db.class.php');
	include('../lib/student.php');
	require('../lib/fpdf181/fpdf.php');

	class PDF extends fpdf
	{
		function MarksCell($w, $h, $txt, $border, $ln, $align) {
			if($txt == 0)
				$txt = '-';
			$this->Cell($w, $h, $txt, $border, $ln, $align);
		}

		function EvalCell($w, $h, $txt, $border, $ln, $align) {
			if($txt == '')
				$txt = '-';
			$this->Cell($w, $h, $txt, $border, $ln, $align);
		}
	}

	/**
	* Class to create one page for a student
	*/
	class StudentMarkSheet
	{
		public $studentNo;
		public $acYear;
		public $pdfObj;

		public $hasValidReport = false;
		public $reportData;
		public $studentObj;
		
		function __construct($stdNo, $year, $pdfObj)
		{
			$this->studentNo = $stdNo;
			$this->acYear = $year;
			$this->pdfObj = $pdfObj;

			$this->hasValidReport = true;
			$this->studentObj = new Student();
			$this->reportData = $this->studentObj->GenerateStudentReportData($stdNo, $year);

			if( count($this->reportData) == 0 || count($this->reportData['marksdata']) == 0 ) {
				$this->hasValidReport = false;
			}
		}

		function addMarkPage()
		{
			if(!$this->hasValidReport) {
				return;
			}
			$pdf = $this->pdfObj;
			$rpd = $this->reportData;

			$pageSideMargin = 15;
			$pageTopMargin = 10;
			$pageWidth = 210;
			$contentWidth = $pageWidth - $pageSideMargin * 2;
			$curLeft = 0;

			$staticTexts = array (
				"RÉPUBLIQUE DÉMOCRATIQUE DU CONGO",
				"MINISTERE DE L'ENSEIGNEMENT PRIMAIRE, SECONDAIRE ET PROFESSIONNEL",
				"Signature de L’élève", "Sceau de l'école",
				"Biffer la mention inutile.", "Note importante: Le bulletin est sans valeur s’il est raturé ou surcharge.",
				"Fait à " . $rpd['sCity'].", le " . date("D M d, Y"), "Le Chef D'Établissement,"
			);

			$pdf->AddPage();
			$pdf->SetAutoPageBreak(false);
			$pdf->SetMargins( $pageSideMargin, $pageTopMargin, $pageSideMargin );
			$pdf->SetXY( $pageSideMargin, $pageTopMargin );

			//pane of title
			$pdf->Image('cg_flag.png', $pageSideMargin, $pageTopMargin, 20, 16);
			$pdf->Cell(20, 16, '', 1, 0);
			$pdf->SetFont('Arial','',12);
			$pdf->Cell( $contentWidth - 20 * 2, 8 , $this->frnConv($staticTexts[0]), 'T', 2, 'C' );
			$pdf->SetFont('Arial','',10);
			$pdf->Cell( $contentWidth - 20 * 2, 8 , $this->frnConv($staticTexts[1]), 'B', 0, 'C' );
			$pdf->SetXY(175, 10);
			if( $rpd['Picture'] )
				$pdf->Image('../uploads/pictures/' . $rpd['Picture'], $pageWidth - $pageSideMargin - 20, $pageTopMargin, 20, 26);
			$pdf->Cell(20, 26, ' ', 1, 1);
			
			//pane of personal info
			$pdf->SetXY($pageSideMargin, $pageTopMargin + 16);
			$pdf->Cell( $contentWidth, 10, 'No ID.  ' . $rpd['StudentNo'], 'LR', 1, 'C' );
			$pdf->MultiCell( $contentWidth / 2, 6, 
				" PROVINCE:   ".$rpd['sProvince'].
				"\n VILLE:   ".$rpd['sCity'].
				"\n COMMUNE / TER:  ".$rpd['sCommune'].
				"\n ECOLE:   ".$rpd['SchName'].
				"\n CODE:   ".$rpd['SchCode'], 'LTRB' );
			$pdf->SetXY( $pageSideMargin + $contentWidth /2, 36 );
			$pdf->MultiCell( $contentWidth / 2, 7.5, 
				" ELEVE : ".$rpd['Name'].' '.$rpd['Surname'].
				" SEXE : ".$rpd['Gender'].
				"\n NE(E)A : ".$rpd['placeofbirth']." Le ".$rpd['Birth'].
				"\n CLASSE : ".$rpd['ClassName'].
				"\n No PERM. : ".$rpd['StudentNo'], 'TRB');

			$pdf->SetFont('Arial', 'B', 11);
			$pdf->Cell( $contentWidth, 10, 
				"BULLETIN DE LA : ".$rpd['ClassCode'].' '.$rpd['ClassName'].$this->multiSpaces(5).
				"ANNEE SCOLAIRE : ".$rpd['Year'], 'LTR', 1, 'C' );

			//marks data header
			$mRowHt = 5;
			$hdrHt = 66;
			$mColWds = array(40, 10, 10, 14, 14, 10, 10, 14, 14, 14, 4, 6, 20);
			$pdf->Cell( $mColWds[0], $mRowHt * 3, "BRANCHES", 'LTR', 0, 'C' );

			$pdf->SetFont('Arial', 'B', 8);
			$pdf->Cell( $mColWds[1] + $mColWds[2] + $mColWds[3] + $mColWds[4], 
				$mRowHt, "PREMIER SEMESTRE", 'TR', 2, 'C' );
			$pdf->Cell( $mColWds[1] + $mColWds[2], $mRowHt, "TR. JOURNAL", 'TR', 2, 'C' );
			$pdf->Cell( $mColWds[1], $mRowHt, "1e P.", 'TR', 0, 'C' );
			$pdf->Cell( $mColWds[2], $mRowHt, "2e P.", 'TR', 0, 'C' );
			$curLeft = $pageSideMargin + $mColWds[0] + $mColWds[1] + $mColWds[2];
			$pdf->SetXY( $curLeft, $pageTopMargin + $hdrHt + $mRowHt );
			$pdf->Cell( $mColWds[3], $mRowHt * 2, "EXAM.", 'TR', 0, 'C' );
			$pdf->Cell( $mColWds[4], $mRowHt * 2, "TOT.", 'TR', 0, 'C' );

			$curLeft += $mColWds[3] + $mColWds[4];
			$pdf->SetXY($curLeft, $pageTopMargin + $hdrHt);
			$pdf->Cell( $mColWds[5] + $mColWds[6] + $mColWds[7] + $mColWds[8], 
				$mRowHt, "SECOND SEMESTRE", 'TR', 2, 'C' );
			$pdf->Cell( $mColWds[5] + $mColWds[6], $mRowHt, "TR. JOURNAL", 'TR', 2, 'C' );
			$pdf->Cell( $mColWds[5], $mRowHt, "3e P.", 'TR', 0, 'C' );
			$pdf->Cell( $mColWds[6], $mRowHt, "4e P.", 'TR', 0, 'C');

			$curLeft += $mColWds[5] + $mColWds[6];
			$pdf->SetXY($curLeft, $pageTopMargin + $hdrHt + $mRowHt);
			$pdf->Cell( $mColWds[7], $mRowHt * 2, "EXAM.", 'TR', 0, 'C');
			$pdf->Cell( $mColWds[8], $mRowHt * 2, "TOT.", 'TR', 0, 'C');

			$curLeft += $mColWds[7] + $mColWds[8];
			$pdf->SetXY( $curLeft, $pageTopMargin + $hdrHt );
			$pdf->Cell( $mColWds[9], $mRowHt * 3, "T.G.", 'TR', 0, 'C' );
			$pdf->Cell( $mColWds[10], $mRowHt * 3, "", 'TR', 0, 'C', true );

			$pdf->Cell( $mColWds[11] + $mColWds[12], $mRowHt, "EXAMEN DE", 'TR', 2, 'C' );
			$pdf->Cell( $mColWds[11] + $mColWds[12], $mRowHt, "REPECHAGE", 'R', 2, 'C');
			$pdf->Cell( $mColWds[11], $mRowHt, "%", 'TR', 0, 'C');
			$pdf->Cell( $mColWds[12], $mRowHt, "SIGN. PROF. ", 'TR', 0, 'C');

			//marks data by maxima and subject
			$numOfMarkRows = 0;
			foreach ($rpd['marksdata'] as $mxmId => $mxm) {
				if ( count($mxm['sbjMarks']) == 0 )
					continue;

				$pdf->SetFont('Arial', 'B', 9);
				$pdf->Ln();
				$pdf->Cell($mColWds[0], $mRowHt, 'MAXIMA', 'LTR', 0);

				for ($i = 0; $i < count($mxm['maxima']); $i++){
					$pdf->MarksCell( $mColWds[$i+1], $mRowHt, $mxm['maxima'][$i], 'TR', 0, 'C' );	
				}
				$pdf->Cell( $mColWds[10]+$mColWds[11]+$mColWds[12], $mRowHt, "", 'TR', 0, 'C', true );

				foreach ($mxm['sbjMarks'] as $sbjId => $sbjMark) {
					$pdf->SetFont('Arial', '', 9);
					$pdf->Ln();
					$pdf->Cell($mColWds[0], $mRowHt, $sbjMark[0], 'LTR', 0);
					for ($i=0; $i<9; $i++) {
						$pdf->MarksCell($mColWds[$i+1], $mRowHt, $sbjMark[1][$i], 'TR', 0, 'C');		
					}
					$pdf->Cell($mColWds[10], $mRowHt, '', 'TR', 0, 'C', true);
					$pdf->Cell($mColWds[11], $mRowHt, '', 'TR', 0, 'C', false);
					$pdf->Cell($mColWds[12], $mRowHt, '', 'TR', 0, 'C', false);
				}
				$numOfMarkRows += 1 + count($mxm['sbjMarks']);
			}

			//marks statistics data
			$pdf->Ln();
			$pdf->SetFont('Arial', 'B', 9);
			$pdf->Cell($mColWds[0], $mRowHt, 'MAXIMA GENER.', 'LTR', 0);
			for ($i=0; $i<9; $i++) {
				$pdf->MarksCell($mColWds[$i+1], $mRowHt, $rpd['statdata']['totalmaxima'][$i], 'TR', 0, 'C');			
			}
			$pdf->Cell($mColWds[10], $mRowHt, '', 'TR', 0, 'C', true);
			$pdf->Cell($mColWds[11], $mRowHt, '', 'TR', 0, 'C', true);
			$pdf->Cell($mColWds[12], $mRowHt, '', 'TR', 0, 'C', true);

			$pdf->Ln();
			$pdf->Cell($mColWds[0], $mRowHt, 'TOTAUX', 'LTR', 0);
			for ($i=0; $i<9; $i++) {
				$pdf->MarksCell($mColWds[$i+1], $mRowHt, $rpd['statdata']['totalmarks'][$i], 'TR', 0, 'C');			
			}
			$pdf->Cell($mColWds[10], $mRowHt, '', 'TR', 0, 'C', true);

			$pdf->Ln();
			$pdf->Cell($mColWds[0], $mRowHt, 'POURCENTAGE', 'LTR', 0);
			for ($i=0; $i<9; $i++) {
				$pdf->MarksCell($mColWds[$i+1], $mRowHt, $rpd['statdata']['percentage'][$i], 'TR', 0, 'C');			
			}
			$pdf->Cell($mColWds[10], $mRowHt, '', 'TR', 0, 'C', true);

			$pdf->Ln();
			$pdf->Cell($mColWds[0], $mRowHt, 'PLACE / NBRE ELEVES', 'LTR', 0);
			for ($i=0; $i<9; $i++) {
				$pdf->Cell($mColWds[$i+1], $mRowHt, '', 'TR', 0, 'C');			
			}
			$pdf->Cell($mColWds[10], $mRowHt, '', 'TR', 0, 'C', true);

			$pdf->Ln();
			$pdf->Cell($mColWds[0], $mRowHt, 'APPLICATION', 'LTR', 0);
			for ($i=0; $i<9; $i++) {
				$appPeriodID = $i>3 ? $i : ( $i<2 ? $i+1 : 3 );
				$avlCells = array(1,2,4,5);
				if (in_array($appPeriodID, $avlCells)) {
					if ( array_key_exists($appPeriodID, $rpd['othereva']) )
						$pdf->EvalCell($mColWds[$i+1], $mRowHt, $rpd['othereva'][$appPeriodID][0], 'TR', 0, 'C');
					else
						$pdf->EvalCell($mColWds[$i+1], $mRowHt, '', 'TR', 0, 'C');
				}
				else
					$pdf->Cell($mColWds[$i+1], $mRowHt, $appPeriodID, 'TR', 0, 'C', true);
			}
			$pdf->Cell($mColWds[10], $mRowHt, '', 'TR', 0, 'C', true);

			$pdf->Ln();
			$pdf->Cell($mColWds[0], $mRowHt, 'CONDUITE', 'LTR', 0);
			for ($i=0; $i<9; $i++) {
				$appPeriodID = $i>3 ? $i : ( $i<2 ? $i+1 : 3 );
				$avlCells = array(1,2,4,5);
				if (in_array($appPeriodID, $avlCells)) {
					if ( array_key_exists($appPeriodID, $rpd['othereva']) )
						$pdf->EvalCell($mColWds[$i+1], $mRowHt, $rpd['othereva'][$appPeriodID][1], 'TR', 0, 'C');
					else
						$pdf->EvalCell($mColWds[$i+1], $mRowHt, '', 'TR', 0, 'C');
				}
				else
					$pdf->Cell($mColWds[$i+1], $mRowHt, $appPeriodID, 'TR', 0, 'C', true);			
			}
			$pdf->Cell($mColWds[10], $mRowHt, '', 'TR', 0, 'C', true);

			$pdf->Ln();
			$pdf->SetFont('Arial', '', 9);
			$pdf->Cell( $mColWds[0], $mRowHt * 2, "SIGN. DU RESPONSABLE", 'LTRB', 0 );
			$pdf->Cell( $mColWds[1]+$mColWds[2]+$mColWds[3]+$mColWds[4], $mRowHt * 2, "", 'TRB', 0 );
			$pdf->Cell( $mColWds[5]+$mColWds[6]+$mColWds[7]+$mColWds[8]+$mColWds[9], $mRowHt * 2, "", 'TRB', 0 );
			$pdf->Cell( $mColWds[10], $mRowHt * 2, '', 'TRB', 0, 'C', true );

			$paneLeft = $pageSideMargin + array_sum( array_slice($mColWds, 0, 11) );
			$paneTop = $pageTopMargin + $hdrHt + $mRowHt * ($numOfMarkRows + 4);
			$pdf->SetXY($paneLeft, $paneTop);
			$pdf->SetFont('Arial', '', 8);
			$pdf->MultiCell( $mColWds[11]+$mColWds[12], $mRowHt, 
				" .Passe \n".
				" .Double \n".
				" .A echoue \n".
				" Le...........\n".
				" Le Chef\n".
				" d'Etablissement \n".
				" Sceau de l'ecole", 
				'TRB', 'L' );

			//report footer
			$pdf->SetXY( $pageSideMargin, $paneTop + $mRowHt * 7 );

			$pdf->SetFont('Arial', '', 12);
			$pdf->Cell(50, 40, $this->frnConv($staticTexts[2]), 'L', 0, 'C');
			$pdf->Cell(50, 40, $this->frnConv($staticTexts[3]), 0, 1, 'C');

			$pdf->SetFont('Arial', '', 9);
			$pdf->Cell(100, 5, $this->frnConv($staticTexts[4]), 'L', 2, 'L');
			$pdf->Cell(100, 5, $this->frnConv($staticTexts[5]), 'L', 2, 'L');
			$pdf->Cell(100, 5, "", 'LB', 2, 'L');

			$pdf->SetXY( $pageSideMargin+100, $pageTopMargin + $hdrHt + $mRowHt*($numOfMarkRows+11) );
			$pdf->Cell(80, 10, $this->frnConv($staticTexts[6]), 'R', 2, 'C');
			$pdf->SetFont('Arial', '', 12);
			$pdf->Cell(80, 10, $this->frnConv($staticTexts[7]), 'R', 2, 'C');

			$pdf->Cell(80, 20, "Nom et Signature", 'R', 2, 'C');
			$pdf->Cell(80, 10, "", 'R', 2, 'R');
			$pdf->SetFont('Arial', 'I', 12);
			$pdf->Cell(80, 5, $rpd['epsp'] . $this->multiSpaces(5), 'RB', 2, 'R');
			
		}

		function frnConv($str) {
			if (is_numeric($str))
				return $str;
			else
				return iconv('UTF-8', 'windows-1252', $str);
		}

		function multiSpaces($spnum) {
			$str = '';
			for ($i = 0; $i < $spnum; $i++)
				$str .= ' ';
			return $str;
		}

		function multiDots($spnum) {
			$str = '';
			for ($i = 0; $i < $spnum; $i++)
				$str .= '.';
			return $str;
		}

	}

	
	$pdf = new PDF('P','mm','A4');

	if( $_POST['Student'] == 0 ) {
		$arrStudents = Student::GetStudentsNos( $_POST['Year'], $_POST['Program'] );
		foreach ($arrStudents as $stdNo) {
			$smsObj = new StudentMarkSheet($stdNo, $_POST['Year'], $pdf);
			$smsObj->addMarkPage();
		}
		$pdf->Output();
	}else {
		$smsObj = new StudentMarkSheet($_POST['Student'], $_POST['Year'], $pdf);
		if (!$smsObj->hasValidReport) {
			echo $smsObj->frnConv("Votre bulletin de la période, semestre ou année n'est pas encore prêt");
			return;
		}
		$smsObj->addMarkPage();
		$pdf->Output();
	}


	//helper functions
	function debug() {
	    $trace = debug_backtrace();
	    $rootPath = dirname(dirname(__FILE__));
	    $file = str_replace($rootPath, '', $trace[0]['file']);
	    $line = $trace[0]['line'];
	    $var = $trace[0]['args'][0];
	    $lineInfo = sprintf('<div><strong>%s</strong> (line <strong>%s</strong>)</div>', $file, $line);
	    $debugInfo = sprintf('<pre>%s</pre>', print_r($var, true));
	    print_r($lineInfo.$debugInfo);
	}

?>