<?php

require_once(__DIR__ . '/fpdf/fpdf.php');

class PDF extends FPDF {
	function EAN13($x, $y, $barcode, $h=16, $w=.35)
	{
	   $this->Barcode($x, $y, $barcode, $h, $w, 13);
	}

	function UPC_A($x, $y, $barcode, $h=16, $w=.35)
	{
	   $this->Barcode($x, $y, $barcode, $h, $w, 12);
	}

	function GetCheckDigit($barcode)
	{
	   //Compute the check digit
	   $sum=0;
	   for($i=1;$i<=11;$i+=2)
	       $sum+=3*$barcode{$i};
	   for($i=0;$i<=10;$i+=2)
	       $sum+=$barcode{$i};
	   $r=$sum%10;
	   if($r>0)
	       $r=10-$r;
	   return $r;
	}

	function TestCheckDigit($barcode)
	{
	   //Test validity of check digit
	   $sum=0;
	   for($i=1;$i<=11;$i+=2)
	       $sum+=3*$barcode{$i};
	   for($i=0;$i<=10;$i+=2)
	       $sum+=$barcode{$i};
	   return ($sum+$barcode{12})%10==0;
	}

	function Barcode($x, $y, $barcode, $h, $w, $len)
	{
	   //Padding
	   $barcode=str_pad($barcode, $len-1, '0', STR_PAD_LEFT);
	   if($len==12)
	       $barcode='0'.$barcode;
	   //Add or control the check digit
	   if(strlen($barcode)==12)
	       $barcode.=$this->GetCheckDigit($barcode);
	   elseif(!$this->TestCheckDigit($barcode))
	       $this->Error('Incorrect check digit');
	   //Convert digits to bars
	   $codes=array(
	       'A'=>array(
	           '0'=>'0001101', '1'=>'0011001', '2'=>'0010011', '3'=>'0111101', '4'=>'0100011',
	           '5'=>'0110001', '6'=>'0101111', '7'=>'0111011', '8'=>'0110111', '9'=>'0001011'),
	       'B'=>array(
	           '0'=>'0100111', '1'=>'0110011', '2'=>'0011011', '3'=>'0100001', '4'=>'0011101',
	           '5'=>'0111001', '6'=>'0000101', '7'=>'0010001', '8'=>'0001001', '9'=>'0010111'),
	       'C'=>array(
	           '0'=>'1110010', '1'=>'1100110', '2'=>'1101100', '3'=>'1000010', '4'=>'1011100',
	           '5'=>'1001110', '6'=>'1010000', '7'=>'1000100', '8'=>'1001000', '9'=>'1110100')
	       );
	   $parities=array(
	       '0'=>array('A', 'A', 'A', 'A', 'A', 'A'),
	       '1'=>array('A', 'A', 'B', 'A', 'B', 'B'),
	       '2'=>array('A', 'A', 'B', 'B', 'A', 'B'),
	       '3'=>array('A', 'A', 'B', 'B', 'B', 'A'),
	       '4'=>array('A', 'B', 'A', 'A', 'B', 'B'),
	       '5'=>array('A', 'B', 'B', 'A', 'A', 'B'),
	       '6'=>array('A', 'B', 'B', 'B', 'A', 'A'),
	       '7'=>array('A', 'B', 'A', 'B', 'A', 'B'),
	       '8'=>array('A', 'B', 'A', 'B', 'B', 'A'),
	       '9'=>array('A', 'B', 'B', 'A', 'B', 'A')
	       );
	   $code='101';
	   $p=$parities[$barcode{0}];
	   for($i=1;$i<=6;$i++)
	       $code.=$codes[$p[$i-1]][$barcode{$i}];
	   $code.='01010';
	   for($i=7;$i<=12;$i++)
	       $code.=$codes['C'][$barcode{$i}];
	   $code.='101';
	   //Draw bars
	   for($i=0;$i<strlen($code);$i++)
	   {
	       if($code{$i}=='1')
	           $this->Rect($x+$i*$w, $y, $w, $h, 'F');
	   }
	   //Print text uder barcode
	   $this->SetFont('arial', '', 12);
	   $this->Text($x, $y+$h+11/$this->k, substr($barcode, -$len));
	}
	
	function Rotate($angle,$x=-1,$y=-1) { 

        if($x==-1) 
            $x=$this->x; 
        if($y==-1) 
            $y=$this->y; 
        if($this->angle!=0) 
            $this->_out('Q'); 
        $this->angle=$angle; 
        if($angle!=0) 

        { 
            $angle*=M_PI/180; 
            $c=cos($angle); 
            $s=sin($angle); 
            $cx=$x*$this->k; 
            $cy=($this->h-$y)*$this->k; 
             
            $this->_out(sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy)); 
        } 
    } 
}

class Place {

	public static function generate($nom_acheteur, $prenom_acheteur, $nom, $prenom, $codebillet, $numplace, $tarif, $commande, $intitule="", $tarif_desc="", $navette=1, $output_type='I')
	{
		$pdf=new PDF();
		$pdf->Open();
		//$pdf->AddFont('NeutraTextBold','','NeutraTextBold.php');
		//$pdf->AddFont('NeutraTextBook','','NeutraTextBook.php');

		$pdf->AddPage('L');
		$pdf->SetAutoPageBreak(true,'5');
		$pdf->Image('resources/raw_billet.jpg',0,0,297,210);
		$pdf->SetFont('arial','B','20');
		$pdf->SetTextColor(0,0,0);
		$pdf->SetXY(187,13);
		$pdf->Write(10,iconv("UTF-8", "ISO-8859-1",ucfirst(strtolower($prenom))));
		$pdf->SetXY(187,21);
		$pdf->Write(10,iconv("UTF-8", "ISO-8859-1",strtoupper($nom)));
		$pdf->SetFont('arial','','20');
		$pdf->SetXY(187,42);
		$pdf->Write(10,iconv("UTF-8", "ISO-8859-1",strtoupper($intitule)));

	
		// NAVETTE
		if($navette == 2) {
			// Compiègne
			$pdf->SetFont('arial','','11');
			$pdf->SetXY(95,190);
			$pdf->Write(10,iconv("UTF-8", "ISO-8859-1","Navette"));
	
			$pdf->SetFont('arial','','18');
			$pdf->SetXY(88,197);
			$pdf->Write(10,iconv("UTF-8", "ISO-8859-1","Compiègne"));		
		} else if($navette == 3) {
		// Paris
			$pdf->SetFont('arial','','11');
			$pdf->SetXY(95,190);
			$pdf->Write(10,iconv("UTF-8", "ISO-8859-1","Navette"));
		
			$pdf->SetFont('arial','','18');
			$pdf->SetXY(88,197);
			$pdf->Write(10,iconv("UTF-8", "ISO-8859-1","Paris"));
		} else {
		// Aucun
		
		}

		
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('arial','','11');
		$pdf->SetXY(187,6);
		$pdf->Write(10,"Num".chr(233)."ro de billet : ".$numplace);
		

		/*
		$pdf->SetXY(201,193);
		if($mineur == 'N')
		 	$pdf->Write(10,'Personne majeure');
		else
		 	$pdf->Write(10,'Personne mineure');
		*/


		
		$pdf->SetFont('arial','','11');
		$pdf->SetXY(187,28);
		$commande = explode("-", $commande);
		$jour = explode(" ", $commande[2]);
		$commande = "Date d'achat : ".$jour[0].'/'.$commande[1].'/'.$commande[0];
		$pdf->Write(10,$commande);

		$pdf->SetXY(187,32);
        //$pdf->SetFont('times','','30');
        $pdf->SetTextColor(0,0,0);
        $pdf->Write(10, "Prix TTC : $tarif ".chr(128));

		$pdf->SetXY(187,48);
        //$pdf->SetFont('times','','30');
        $pdf->SetTextColor(0,0,0);
        $pdf->Write(10, iconv("UTF-8", "ISO-8859-1",$tarif_desc));

		$pdf->SetXY(187,58);
		$pdf->Write(10,"Billet achet".chr(233)." par : ".iconv("UTF-8", "ISO-8859-1", strtoupper($nom_acheteur)." ".ucfirst($prenom_acheteur)));
		$pdf->SetTextColor(0,0,0);
		$pdf->EAN13(189, 72, $codebillet, 12, 1);
		
		return $pdf->Output("", $output_type);
	}
}