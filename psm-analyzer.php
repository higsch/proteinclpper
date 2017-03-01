<?php
/*****************
This script analyzes lists of peptides from mass spectrometry data sets to determine cleavage site specificities.

Input data:
===========
$psm_file_name: file name of PSM text file (tab delimited)
	Sequest files should be exported from Proteome Discoverer 1.4 with the
	File->Export->To Text (tab delimited)... commmand  
	Check Proteins and Peptides
	Proteins: -> All Proteins
	Peptides: -> All PSMs

$psm_file_type: 'sequest' or 'maxquant' 
	depending on file type

$fasta_file_name: file name of a fasta file that contains the sequences of all proteins identified in the dataset
	The format of the first line should be e.g.
		>sp|Q2FIM5|CLPP_STAA3 ATP-dependent Clp protease proteolytic subunit
	since the Uniprot accession code (e.g. 'Q2FIM5') is read from there
	
$minimum_number_of_cleavages: Defines the minimum number of cleavages per protein, 
	so that data from this protein is included in the averaged analysis.


To do list:
===========
stm in sequenz(alle s t m raussuchen, unique, dann alle psm durch und für jeden check, rausschreiben, schauen ob einheitlich, falls ja, sequ ersetzen)
import form
mehrere Datenfiles
p value Berechnung
gute Referenz generieren fuer weblogo
Number of cleavages fuer jede Position einzeln


Author info
===========
Technische Universitaet Muenchen
Matthias Stahl
Malte Gersch
matthias.stahl@tum.de
malte.gersch@mytum.de
1 Dec 2015
Version 1.0

*****************/



//START UP
//==================
error_log(E_ALL);
//input data
//fetch psm and fasta file from temporary session
$fasta_file_name = "empty";
$psm_file_name = "empty";
//$fasta_file_name = "fasta_substrate.txt";
//$psm_file_name = "20141202_MS_E1__lowB_psms.txt";
$psm_file_type = "sequest";
$minimum_number_of_cleavages = 10; // if # of cleavages < for a protein, it is then excluded from analysis
$weight = array(
        "on_off" => "on",
        "type" => array(17)
        );
$sort_out_control = "off";


//initial settings
$AA = array("A", "C", "D", "E", "F", "G", "H", "I", "K", "L", "M", "N", "P", "Q", "R", "S", "T", "V", "W", "Y", "m", "s", "t");
$analysis = array("P3" => -3, "P2" => -2, "P1" => -1, "P1p" => 0, "P2p" => 1, "P3p" => 2);


//FUNCTIONS
//==================

// error log function
function error_logging($report) {
	echo("<pre>");
	print_r($report);
	echo("</pre><br />");
}

// Dynamic setting of column identifiers
function Detect_column_headers($column_headers, $psm_file_type) {
    if ($psm_file_type == 'sequest') {
        // Initialize array
        $positions = array(
                "confidence" => -1,
                "sequence" => -1,
                "ambiguity" => -1,
                "num_pga" => -1,
                "pga" => -1,
                "modifications" => -1,
                "Xcorr" => -1,
                "probability" => -1,
                "spscore" => -1,
                "intensity" => -1,
                "mhplus" => -1,
                "spectrum_file" => -1,
                "comments" => 37,
                "alluppercase" => 38);
        
        // Assign keys
        foreach ($column_headers as $key => $value) {
            switch ($value) {
                case 'Confidence Level':
                    $positions['confidence'] = $key;
                    break;
                case 'Sequence':
                    $positions['sequence'] = $key;
                    break;
                case 'PSM Ambiguity':
                    $positions['ambiguity'] = $key;
                    break;
                case '# Protein Groups':
                    $positions['num_pga'] = $key;
                    break;
                case 'Protein Group Accessions':
                    $positions['pga'] = $key;
                    break;
                case 'Modifications':
                    $positions['modifications'] = $key;
                    break;
                case 'XCorr':
                    $positions['Xcorr'] = $key;
                    break;
                case 'Probability':
                    $positions['probability'] = $key;
                    break;
                case 'SpScore':
                    $positions['spscore'] = $key;
                    break;
                case 'Intensity':
                    $positions['intensity'] = $key;
                    break;
                case 'MH+ [Da]':
                    $positions['mhplus'] = $key;
                    break;
                case 'Spectrum File':
                    $positions['spectrum_file'] = $key;
                    break;
            }
        }
    }
    
    // If MaxQuant
    if ($psm_file_type == 'sequest') {
        // Initialize array
        $column_positions = array(
         	"confidence" => 0,
        	"sequence" => 3,
        	"ambiguity" => 5,
        	"pga" => 9,
        	"modifications" => 10,
        	"Xcorr" => 18,
        	"probability" => 19,
        	"spscore" => 20, 
       		"intensity" => 24,
       		"mhplus" => 27,
       		"spectrum_file" => 37);
    }
    
    return $positions;
}

//function that will return array with protein length and amino acid occurence from protein sequence
function AA_frequency_from_fasta($fasta_file_name, $AA_in_function) 
{
    $fasta_file = $_SESSION['fasta_file']['content']; // file_get_contents($fasta_file_name);
    $fasta_proteins_temp = explode(">", $fasta_file); //separate entries
    unset($fasta_proteins_temp[0]);
    $fasta_proteins = array();
    foreach ($fasta_proteins_temp as $value) 
    {
        $lines = explode("\n", $value);
        $elements = explode("|", $lines[0]); //read out Uniprot entry and not all these weired file types
        if (count($elements) === 3) {
            $name_temp = explode(" ", $elements[2]);
            unset($lines[0]);
            $sequence = implode("", $lines);
            $sequence = str_replace(array("\r", "\n", "\t"), '', strip_tags($sequence)); // Delete control signs!
            $fasta_proteins[$elements[1]] = array("sequence" => $sequence);
            $fasta_proteins[$elements[1]]["length"] = strlen($sequence);
            $fasta_proteins[$elements[1]]["name"] = $name_temp[0];
            //fill output array with number of occurences
            foreach ($AA_in_function as $singleA) 
            {
                  $fasta_proteins[$elements[1]][$singleA] = substr_count($sequence, $singleA);
            }
            $fasta_proteins[$elements[1]]["seq_coverage"] = 0;
            $fasta_proteins[$elements[1]]["coverage"] = array_fill(0, strlen($sequence), 0);
            $fasta_proteins[$elements[1]]["coverage_w"] = array_fill(0, strlen($sequence), 0);
        }
    }
    return $fasta_proteins;
}

//sorting function that is used to sort protein accession keys
function sortByOrder($a, $b) 
{
	global $column_positions;
	$number = $column_positions["pga"];
    return strnatcmp($a[$number],$b[$number]);
}



//ANALYSIS
//==================

// Import fasta file(tab delimited)
$proteins = AA_frequency_from_fasta($fasta_file_name, $AA);
//error_logging($proteins);

// Check if there is control data and assign to variable and delete column headers
if (isset($_SESSION['control_psm_files'])) {
	$sort_out_control = "on";
	$control_data = $_SESSION['control_psm_files']['content'];
    if (!empty($control_data['column_headers'])) {
        unset($control_data['column_headers']);
    }
}

// Set PSM data and delete column headers
$data = $_SESSION['psm_files']['content'];
if (!empty($data['column_headers'])) {
    // Dynamic identification of column headers
    $column_positions = Detect_column_headers($data['column_headers'], $psm_file_type);
    // Delete column headers
    unset($data['column_headers']);
}

// Get rid of PSMs that have not been assigned to a protein in $data or are medium confident
$tobeunset = array();
foreach ($data as $key => $row) 
{
    // Only peptides assigned to one protein
    if ($row[$column_positions["num_pga"]] != 1) 
    {
    	$tobeunset[] = $key;
    // Only unambiguous assigned peptides
    } elseif ($row[$column_positions["ambiguity"]] != "Unambiguous")
	{
		$tobeunset[] = $key;
    // Only high confident peptides
    } elseif ($row[$column_positions["confidence"]] != "High")
	{
		$tobeunset[] = $key;
    }
}

foreach ($tobeunset as $value)
{
    unset($data[$value]);
}
unset($tobeunset);

// Get rid of PSMs that have not been assigned to a protein in $control_data or are medium confident
$tobeunset = array();
foreach ($control_data as $key => $row) 
{
    // Only peptides assigned to one protein
    if ($row[$column_positions["num_pga"]] != 1) 
    {
    	$tobeunset[] = $key;
    // Only unambiguous assigned peptides
    } elseif ($row[$column_positions["ambiguity"]] != "Unambiguous")
	{
		$tobeunset[] = $key;
    // Only high confident peptides
    } elseif ($row[$column_positions["confidence"]] != "High")
	{
		$tobeunset[] = $key;
    }
}

foreach ($tobeunset as $value) 
{
    unset($control_data[$value]);
}

// Sort $data by protein accession code
usort($data, 'sortByOrder');

// delte control sequences
if ($sort_out_control == "on")
{
    $tobeunset = array();
    usort($control_data, 'sortByOrder');
    foreach ($data as $key => $value) {
        foreach ($control_data as $control_psm) {
            if (strtoupper($value[$column_positions["sequence"]]) == strtoupper($control_psm[$column_positions["sequence"]])) {
                $tobeunset[] = $key;
                continue;
            }
        }
    }
    foreach ($tobeunset as $value) 
    {
        unset($data[$value]);
    }
    usort($data, 'sortByOrder');
}

// Read out number of proteins and determine range of according psms
$PGAs = array();
$sequences = array();

//$j = 0;
foreach($data as $value) 
{
    $PGAs[] = $value[$column_positions["pga"]];
    //$data[$j][$column_positions["alluppercase"]] = strtoupper($value[$column_positions["sequence"]]);
    $sequences[] = strtoupper($value[$column_positions["sequence"]]);
    //$j++;
}

$proteins_in_data_temp = array_unique($PGAs);
$proteins_in_data = array();

foreach ($proteins_in_data_temp as $key => $value) 
{
	    $proteins_in_data[] = array (
	        "pga" => $value,	//fill in pga
	        "start" => $key,	//fill in first psm
	        "end" => 0,
	        "name" => $proteins[$value]["name"]);
}

//fill in last psm for each protein
$tobeunset = array();
foreach ($proteins_in_data as $key => $value) 
{
	// if more than one protein was assigned
	if ($proteins_in_data[$key]['name'] == "") { // avoids NULL assignments (MS)
        $proteins_in_data[$key]['name'] = "no name";
        $tobeunset[] = $key;
	}
	
    if (intval($key) == (count($proteins_in_data)-1) ) //for the last one
    {
         $proteins_in_data[$key]["end"] = count($data) - 1; 
    } 
    else  //for all others
    {
        $nextkey = $key + 1;
        $proteins_in_data[$key]["end"] = $proteins_in_data[$nextkey]["start"] - 1 ;
    }
}

// Unset proteins that are not in fasta
foreach ($tobeunset as $value) 
{
    unset($proteins_in_data[$value]);
}

//generate data array with only unique sequences
$unique_sequences = array_unique($sequences);
$data_unique = array();

foreach ($unique_sequences as $key => $value) 
{
	$data_unique[] = $data[$key];
}

//Initialize results array
$results = array();
foreach ($proteins_in_data as $value) 
{
    $results[$value["pga"]] = array(); //create entry for each protein
    
    foreach ($analysis as $key => $position) //for each analysis (P1, P2, ...)
    {
        foreach ($AA as $value2) // and for each amino acid
        {
        	// initialise counting variable
        	$results[$value["pga"]]["counts"][$key][$value2] = 0;
        	$results[$value["pga"]]["counts_u"][$key][$value2] = 0;  
        	
        	//fill in relative AA abundances from fasta
    		$results[$value["pga"]]["freq"][$value2] = $proteins[$value["pga"]][$value2] / $proteins[$value["pga"]]["length"];
    	}    
    }
}
$weblogoconsensus = array();

//For each protein that was identified ...
foreach ($proteins_in_data as $protein) 
{
    $cleavages = 0;
    $number_of_peptides = 0;
    $cleavages_u = 0;
    $number_of_peptides_u = 0;
	
	$counter = 0;
    
    //... go through the respective set of spectra ...
    for ($i = $protein["start"]; $i <= $protein["end"]; $i++) 
    {
        $sequence = strtoupper($data[$i][$column_positions["sequence"]]); // FIXME: Change when modifications are included in analysis
        $length = strlen($sequence);
        $pos = strpos($proteins[$protein["pga"]]["sequence"], $sequence);
        
		// Check if sequence is in fasta
		if (!is_numeric($pos)) {
            continue;
		}
        
        //check if sequence is among control peptides (-> discard)
        /*if ($sort_out_control == "on") 
		{
        	foreach ($control_data as $control_psm) 
        	{
        		if ($sequence == strtoupper($control_psm[$column_positions["sequence"]]))
        		{
        			continue 2;
				}
        	}
        }*/
        
        //map coverage on sequence
        for($k = $pos; $k < ($pos + $length); $k++)
        {
        	$proteins[$protein["pga"]]["coverage"][$k]++;
        }
        
        //... and increase the counts of the respective amino acids
        //both the ones that are in the peptide as well as the ones that cannot be seen
        //because they are flanking the peptide (these are fetched from the protein sequence)
        
        //N-terminal peptides
        if ($pos == 0)
        {
            $data[$i][$column_positions["comments"]] = "N-terminal";
            
            $results[$protein["pga"]]["counts"]["P1"][substr($sequence,-1,1)]++;
            $results[$protein["pga"]]["counts"]["P2"][substr($sequence,-2,1)]++;
            $results[$protein["pga"]]["counts"]["P3"][substr($sequence,-3,1)]++;
            
            $results[$protein["pga"]]["counts"]["P1p"][$proteins[$protein["pga"]]["sequence"][$pos+$length]]++;
            $results[$protein["pga"]]["counts"]["P2p"][$proteins[$protein["pga"]]["sequence"][$pos+$length+1]]++;
            $results[$protein["pga"]]["counts"]["P3p"][$proteins[$protein["pga"]]["sequence"][$pos+$length+2]]++; 
            
            if (isset($unique_sequences[$i])) 
            {
            	$results[$protein["pga"]]["counts_u"]["P1"][substr($sequence,-1,1)]++;
            	$results[$protein["pga"]]["counts_u"]["P2"][substr($sequence,-2,1)]++;
            	$results[$protein["pga"]]["counts_u"]["P3"][substr($sequence,-3,1)]++;
            
            	$results[$protein["pga"]]["counts_u"]["P1p"][$proteins[$protein["pga"]]["sequence"][$pos+$length]]++;
            	$results[$protein["pga"]]["counts_u"]["P2p"][$proteins[$protein["pga"]]["sequence"][$pos+$length+1]]++;
            	$results[$protein["pga"]]["counts_u"]["P3p"][$proteins[$protein["pga"]]["sequence"][$pos+$length+2]]++; 
            	
            	$cleavages_u++;
            	$number_of_peptides_u++;
            }
            
            $weblogoconsensus[] = array($protein["pga"] => substr($sequence,-3,1).substr($sequence,-2,1).substr($sequence,-1,1).$proteins[$protein["pga"]]["sequence"][$pos+$length].$proteins[$protein["pga"]]["sequence"][$pos+$length+1].$proteins[$protein["pga"]]["sequence"][$pos+$length+2]);
            
            $cleavages++;
            $number_of_peptides++;
            continue;
        } 
        //C-terminal peptides
        elseif ( ($pos+$length) == strlen($proteins[$protein["pga"]]["sequence"]) ) 
        {
            $data[$i][$column_positions["comments"]] = "C-terminal";
            
            $results[$protein["pga"]]["counts"]["P1p"][substr($sequence,0,1)]++;
            $results[$protein["pga"]]["counts"]["P2p"][substr($sequence,1,1)]++;
            $results[$protein["pga"]]["counts"]["P3p"][substr($sequence,2,1)]++;
            
            $results[$protein["pga"]]["counts"]["P1"][$proteins[$protein["pga"]]["sequence"][$pos-1]]++;
            $results[$protein["pga"]]["counts"]["P2"][$proteins[$protein["pga"]]["sequence"][$pos-2]]++;
            $results[$protein["pga"]]["counts"]["P3"][$proteins[$protein["pga"]]["sequence"][$pos-3]]++;
            
            if (isset($unique_sequences[$i])) 
            {
            	$results[$protein["pga"]]["counts_u"]["P1p"][substr($sequence,0,1)]++;
            	$results[$protein["pga"]]["counts_u"]["P2p"][substr($sequence,1,1)]++;
            	$results[$protein["pga"]]["counts_u"]["P3p"][substr($sequence,2,1)]++;
            
            	$results[$protein["pga"]]["counts_u"]["P1"][$proteins[$protein["pga"]]["sequence"][$pos-1]]++;
            	$results[$protein["pga"]]["counts_u"]["P2"][$proteins[$protein["pga"]]["sequence"][$pos-2]]++;
            	$results[$protein["pga"]]["counts_u"]["P3"][$proteins[$protein["pga"]]["sequence"][$pos-3]]++;
            	
            	$cleavages_u++;
            	$number_of_peptides_u++;
            }
            
            $weblogoconsensus[] = array($protein["pga"] => $proteins[$protein["pga"]]["sequence"][$pos-3].$proteins[$protein["pga"]]["sequence"][$pos-2].$proteins[$protein["pga"]]["sequence"][$pos-1].substr($sequence,0,1).substr($sequence,1,1).substr($sequence,2,1));
            
            $cleavages++;
            $number_of_peptides++;
            continue;
        } 
        //all other peptides
        else 
        {
            // mark as included
            $data[$i][$column_positions["comments"]] = "included_in_analysis";
            
            // increase cleavage count for aa
            $results[$protein["pga"]]["counts"]["P1p"][substr($sequence,0,1)]++;
            $results[$protein["pga"]]["counts"]["P2p"][substr($sequence,1,1)]++;
            $results[$protein["pga"]]["counts"]["P3p"][substr($sequence,2,1)]++;
            
            $results[$protein["pga"]]["counts"]["P1"][substr($sequence,-1,1)]++;
            $results[$protein["pga"]]["counts"]["P2"][substr($sequence,-2,1)]++;
            $results[$protein["pga"]]["counts"]["P3"][substr($sequence,-3,1)]++;
            
			$results[$protein["pga"]]["counts"]["P1"][$proteins[$protein["pga"]]["sequence"][$pos-1]]++;
			
            if (isset($proteins[$protein["pga"]]["sequence"][$pos-2])) 
            {
            	$results[$protein["pga"]]["counts"]["P2"][$proteins[$protein["pga"]]["sequence"][$pos-2]]++;
            	$P2 = $proteins[$protein["pga"]]["sequence"][$pos-2];
            }
            else
            {
            	$P2 = "X";
            }
            if (isset($proteins[$protein["pga"]]["sequence"][$pos-3])) 
            {
            	$results[$protein["pga"]]["counts"]["P3"][$proteins[$protein["pga"]]["sequence"][$pos-3]]++;
            	$P3 = $proteins[$protein["pga"]]["sequence"][$pos-3];
            }
            else
            {
            	$P3 = "X";
            }
           
            $results[$protein["pga"]]["counts"]["P1p"][$proteins[$protein["pga"]]["sequence"][$pos+$length]]++;
            if (isset($proteins[$protein["pga"]]["sequence"][$pos+$length+1])) 
            {
            	$results[$protein["pga"]]["counts"]["P2p"][$proteins[$protein["pga"]]["sequence"][$pos+$length+1]]++;
            	$P2p = $proteins[$protein["pga"]]["sequence"][$pos+$length+1];
            }
            else
            {
            	$P2p = "X";
            }
            if (isset($proteins[$protein["pga"]]["sequence"][$pos+$length+2])) 
            {
            	$results[$protein["pga"]]["counts"]["P3p"][$proteins[$protein["pga"]]["sequence"][$pos+$length+2]]++;
            	$P3p = $proteins[$protein["pga"]]["sequence"][$pos+$length+2];
            }
            else
            {
            	$P3p = "X";
            }
            
            if (isset($unique_sequences[$i])) 
            {
            	$results[$protein["pga"]]["counts_u"]["P1p"][substr($sequence,0,1)]++;
            	$results[$protein["pga"]]["counts_u"]["P2p"][substr($sequence,1,1)]++;
            	$results[$protein["pga"]]["counts_u"]["P3p"][substr($sequence,2,1)]++;
            
            	$results[$protein["pga"]]["counts_u"]["P1"][substr($sequence,-1,1)]++;
            	$results[$protein["pga"]]["counts_u"]["P2"][substr($sequence,-2,1)]++;
            	$results[$protein["pga"]]["counts_u"]["P3"][substr($sequence,-3,1)]++;
            
            	$results[$protein["pga"]]["counts_u"]["P1"][$proteins[$protein["pga"]]["sequence"][$pos-1]]++;
            	if (isset($proteins[$protein["pga"]]["sequence"][$pos-2])) 
            	{
            		$results[$protein["pga"]]["counts_u"]["P2"][$proteins[$protein["pga"]]["sequence"][$pos-2]]++;
            	}	
            	if (isset($proteins[$protein["pga"]]["sequence"][$pos-3])) 
            	{
            		$results[$protein["pga"]]["counts_u"]["P3"][$proteins[$protein["pga"]]["sequence"][$pos-3]]++;
            	}
           
            	$results[$protein["pga"]]["counts_u"]["P1p"][$proteins[$protein["pga"]]["sequence"][$pos+$length]]++;
            	if (isset($proteins[$protein["pga"]]["sequence"][$pos+$length+1])) 
            	{
            		$results[$protein["pga"]]["counts_u"]["P2p"][$proteins[$protein["pga"]]["sequence"][$pos+$length+1]]++;
            	}
            	if (isset($proteins[$protein["pga"]]["sequence"][$pos+$length+2])) 
            	{
            		$results[$protein["pga"]]["counts_u"]["P3p"][$proteins[$protein["pga"]]["sequence"][$pos+$length+2]]++;
            	}
            	$cleavages_u++;
            	$cleavages_u++;
            	$number_of_peptides_u++;
            }
            
            $weblogoconsensus[] = array($protein["pga"] => substr($sequence,-3,1).substr($sequence,-2,1).substr($sequence,-1,1).$proteins[$protein["pga"]]["sequence"][$pos+$length].$P2p.$P3p);
            $weblogoconsensus[] = array($protein["pga"] => $P3.$P2.$proteins[$protein["pga"]]["sequence"][$pos-1].substr($sequence,0,1).substr($sequence,1,1).substr($sequence,2,1));
            
            $cleavages++;
            $cleavages++;
            $number_of_peptides++;
        }
    }
    $results[$protein["pga"]]["counts"]["cleavages"] = $cleavages;
    $results[$protein["pga"]]["counts"]["number_of_peptides"] = $number_of_peptides;
    $results[$protein["pga"]]["counts_u"]["cleavages"] = $cleavages_u;
    $results[$protein["pga"]]["counts_u"]["number_of_peptides"] = $number_of_peptides_u;
	
    //calculate sequence coverage
    $measured_positions = 0;
    foreach ($proteins[$protein["pga"]]["coverage"] as $residue) 
    {
    	if ($residue > 0)
    	{
    	$measured_positions++;
    	}
    }
    $proteins[$protein["pga"]]["seq_coverage"] = $measured_positions / $proteins[$protein["pga"]]["length"];
}

//final calculations
//calculations of score S
foreach( $results as $pga => $value ) 
{
	if ($value["counts"]["cleavages"] > 0) {
		foreach( $analysis as $position => $value2 ) 
		{
			foreach( $AA as $key3 => $letter )
			{
				if( $results[$pga]["freq"][$letter] == 0 )  // do not attempt to calculate score for non-existent amino acids
				{
					$results[$pga]["score"][$position][$letter] = "";
					$results[$pga]["score_u"][$position][$letter] = "";
				} 
				else 
				{
					//calculate score as frequence of occurence of amino acid at cleavage site divided by relative amino acid abundance in this protein
					$results[$pga]["score"][$position][$letter] = $results[$pga]["counts"][$position][$letter] / ($results[$pga]["counts"]["cleavages"] * $results[$pga]["freq"][$letter]);
					$results[$pga]["score_u"][$position][$letter] = $results[$pga]["counts_u"][$position][$letter] / ($results[$pga]["counts_u"]["cleavages"] * $results[$pga]["freq"][$letter]);
				}
			}
		
		}
	}
}

//calculation of mean scores over all proteins weighted with number of cleavages
$averaged_results = array();
foreach ($analysis as $position => $value)
{
	foreach ($AA as $singleA)
	{
		$numerator1 = 0;
		$numerator2 = 0;
		$denominator1 = 0;
		$denominator2 = 0;
		
		//calculation of weighted mean
		foreach ($proteins_in_data as $key => $protein)
		{
			if ($results[$protein["pga"]]["counts"]["cleavages"] >= $minimum_number_of_cleavages) 
			{
				$numerator1 = $numerator1 + ($results[$protein["pga"]]["counts"]["cleavages"] * $results[$protein["pga"]]["score"][$position][$singleA]);
				$denominator1 = $denominator1 + $results[$protein["pga"]]["counts"]["cleavages"];
			}	
		}
		if ($denominator1 != 0) {
			$mean = ($numerator1 / $denominator1);
			$averaged_results[$position]["mean"][$singleA] = $mean;
		}
		
		//calculation of standard deviation of the weighted mean
		foreach ($proteins_in_data as $key => $protein)
		{
			if ($results[$protein["pga"]]["counts"]["cleavages"] >= $minimum_number_of_cleavages) 
			{
				$numerator2 = $numerator2 + ($results[$protein["pga"]]["counts"]["cleavages"] * pow(($results[$protein["pga"]]["score"][$position][$singleA]-$mean),2));
				$denominator2 = $denominator2 + $results[$protein["pga"]]["counts"]["cleavages"];
			}
		}
		if ($denominator2 != 0) {
			$sdm = sqrt($numerator2 / $denominator2);
			$averaged_results[$position]["sdm"][$singleA] = $sdm;
		}
	}
}

//calculation of mean scores over all proteins weighted with number of cleavages for unique psms
$averaged_results_u = array();
foreach ($analysis as $position => $value)
{
	foreach ($AA as $singleA)
	{
		$numerator1 = 0;
		$numerator2 = 0;
		$denominator1 = 0;
		$denominator2 = 0;
		
		//calculation of weighted mean
		foreach ($proteins_in_data as $key => $protein)
		{
			if ($results[$protein["pga"]]["counts_u"]["cleavages"] >= $minimum_number_of_cleavages) 
			{
				$numerator1 = $numerator1 + ($results[$protein["pga"]]["counts_u"]["cleavages"] * $results[$protein["pga"]]["score_u"][$position][$singleA]);
				$denominator1 = $denominator1 + $results[$protein["pga"]]["counts_u"]["cleavages"];
			}	
		}
		if ($denominator1 != 0) {
			$mean = ($numerator1 / $denominator1);
			$averaged_results_u[$position]["mean"][$singleA] = $mean;
		}
		
		//calculation of standard deviation of the weighted mean
		foreach ($proteins_in_data as $key => $protein)
		{
			if ($results[$protein["pga"]]["counts_u"]["cleavages"] >= $minimum_number_of_cleavages) 
			{
				$numerator2 = $numerator2 + ($results[$protein["pga"]]["counts_u"]["cleavages"] * pow(($results[$protein["pga"]]["score_u"][$position][$singleA]-$mean),2));
				$denominator2 = $denominator2 + $results[$protein["pga"]]["counts_u"]["cleavages"];
			}
		}
		if ($denominator2 != 0) {
			$sdm = sqrt($numerator2 / $denominator2);
			$averaged_results_u[$position]["sdm"][$singleA] = $sdm;
		}
	}
}

//calculate total numbers of peptides and cleavages that were considered
$total_number_of_cleavages = 0;
$total_number_of_peptides = 0;
$total_number_of_cleavages_u = 0;
$total_number_of_peptides_u = 0;
foreach ($proteins_in_data as $key => $protein) 
{
	if ($results[$protein["pga"]]["counts"]["cleavages"] >= $minimum_number_of_cleavages)
	{
		$total_number_of_cleavages += $results[$protein["pga"]]["counts"]["cleavages"];
		$total_number_of_peptides +=  $results[$protein["pga"]]["counts"]["number_of_peptides"];
	}
	
	if ($results[$protein["pga"]]["counts_u"]["cleavages"] >= $minimum_number_of_cleavages)
	{
		$total_number_of_cleavages_u += $results[$protein["pga"]]["counts_u"]["cleavages"];
		$total_number_of_peptides_u +=  $results[$protein["pga"]]["counts_u"]["number_of_peptides"];
	}
}
$averaged_results["total_number_of_peptides"] = $total_number_of_peptides;
$averaged_results["total_number_of_cleavages"] = $total_number_of_cleavages;
	
$averaged_results_u["total_number_of_peptides_u"] = $total_number_of_peptides_u;
$averaged_results_u["total_number_of_cleavages_u"] = $total_number_of_cleavages_u;
	
	

//Analysis for weighted calculations
$w = FALSE;
if ($weight["on_off"] == "on" AND is_numeric($weight["type"][0]))
{
$w = TRUE;
$column_of_weight = $weight["type"][0];
}

//Initialize results array
foreach ($proteins_in_data as $value) 
{
    foreach ($analysis as $key => $position) //for each analysis (P1, P2, ...)
    {
        foreach ($AA as $value2) // and for each amino acid
        {
        	// initialise counting variable
        	$results[$value["pga"]]["counts_w"][$key][$value2] = 0; 
    	}    
    }
}

foreach ($proteins_in_data as $protein) 
{
    $cleavages = 0;
    $number_of_peptides = 0;
    $weights = 0;
	    
    //... go through the respective set of spectra ...
    for ($i = $protein["start"]; $i <= $protein["end"]; $i++) 
    {
        
        $sequence = strtoupper($data[$i][$column_positions["sequence"]]); // FIXME
        $length = strlen($sequence);
        $pos = strpos($proteins[$protein["pga"]]["sequence"], $sequence);
        
		// Check if sequence is in fasta
		if (!is_numeric($pos)) {
			continue;
		}
		
        //check if sequence contains unusual letters (-> discard peptide)
        if ( (strpos($sequence, "f") !== FALSE) OR (strpos($sequence, "g") !== FALSE) )
        {
        	continue;
        }
        
        //check if sequence is among control peptides (-> discard)
        if ($sort_out_control == "on") 
		{
        	foreach ($control_data as $control_psm) 
        	{
        		if ($sequence == strtoupper($control_psm[$column_positions["sequence"]]))
        		{
        			continue 2;
				}
        	}
        }
        
        //map coverage on sequence
        for($k = $pos; $k < ($pos + $length); $k++)
        {
        	$proteins[$protein["pga"]]["coverage_w"][$k] += $data[$i][$column_of_weight];
        }
        
        //... and increase the counts the the respective amino acids
        //both the ones that are in the peptide as well as the ones that cannot be seen
        //because they are flanking the peptide (these are fetched from the protein sequence)
        
        //N-terminal peptides
        if ($pos == 0) 
        {
            $data[$i][$column_positions["comments"]] = "N-terminal";
            
            $results[$protein["pga"]]["counts_w"]["P1"][substr($sequence,-1,1)] += $data[$i][$column_of_weight];
            $results[$protein["pga"]]["counts_w"]["P2"][substr($sequence,-2,1)] += $data[$i][$column_of_weight];
            $results[$protein["pga"]]["counts_w"]["P3"][substr($sequence,-3,1)] += $data[$i][$column_of_weight];
            
            $results[$protein["pga"]]["counts_w"]["P1p"][$proteins[$protein["pga"]]["sequence"][$pos+$length]] += $data[$i][$column_of_weight];
            $results[$protein["pga"]]["counts_w"]["P2p"][$proteins[$protein["pga"]]["sequence"][$pos+$length+1]] += $data[$i][$column_of_weight];
            $results[$protein["pga"]]["counts_w"]["P3p"][$proteins[$protein["pga"]]["sequence"][$pos+$length+2]] += $data[$i][$column_of_weight]; 
            
            $cleavages++;
            $number_of_peptides++;
            $weights += $data[$i][$column_of_weight];
            continue;
        } 
        //C-terminal peptides
        elseif ( ($pos+$length) == strlen($proteins[$protein["pga"]]["sequence"]) ) 
        {
            $data[$i][$column_positions["comments"]] = "C-terminal";
            
            $results[$protein["pga"]]["counts_w"]["P1p"][substr($sequence,0,1)] += $data[$i][$column_of_weight];
            $results[$protein["pga"]]["counts_w"]["P2p"][substr($sequence,1,1)] += $data[$i][$column_of_weight];
            $results[$protein["pga"]]["counts_w"]["P3p"][substr($sequence,2,1)] += $data[$i][$column_of_weight];
            
            $results[$protein["pga"]]["counts_w"]["P1"][$proteins[$protein["pga"]]["sequence"][$pos-1]] += $data[$i][$column_of_weight];
            $results[$protein["pga"]]["counts_w"]["P2"][$proteins[$protein["pga"]]["sequence"][$pos-2]] += $data[$i][$column_of_weight];
            $results[$protein["pga"]]["counts_w"]["P3"][$proteins[$protein["pga"]]["sequence"][$pos-3]] += $data[$i][$column_of_weight];
            
            $cleavages++;
            $number_of_peptides++;
            $weights += $data[$i][$column_of_weight];
            continue;
        } 
        //all other peptides
        else 
        {
            $data[$i][$column_positions["comments"]] = "included_in_analysis";
            
            $results[$protein["pga"]]["counts_w"]["P1p"][substr($sequence,0,1)] += $data[$i][$column_of_weight];
            $results[$protein["pga"]]["counts_w"]["P2p"][substr($sequence,1,1)] += $data[$i][$column_of_weight];
            $results[$protein["pga"]]["counts_w"]["P3p"][substr($sequence,2,1)] += $data[$i][$column_of_weight];
            
            $results[$protein["pga"]]["counts_w"]["P1"][substr($sequence,-1,1)] += $data[$i][$column_of_weight];
            $results[$protein["pga"]]["counts_w"]["P2"][substr($sequence,-2,1)] += $data[$i][$column_of_weight];
            $results[$protein["pga"]]["counts_w"]["P3"][substr($sequence,-3,1)] += $data[$i][$column_of_weight];
            
            $results[$protein["pga"]]["counts_w"]["P1"][$proteins[$protein["pga"]]["sequence"][$pos-1]] += $data[$i][$column_of_weight];
            if (isset($proteins[$protein["pga"]]["sequence"][$pos-2])) 
            {
            	$results[$protein["pga"]]["counts_w"]["P2"][$proteins[$protein["pga"]]["sequence"][$pos-2]] += $data[$i][$column_of_weight];
            }
            if (isset($proteins[$protein["pga"]]["sequence"][$pos-3])) 
            {
            	$results[$protein["pga"]]["counts_w"]["P3"][$proteins[$protein["pga"]]["sequence"][$pos-3]] += $data[$i][$column_of_weight];
            }
           
            $results[$protein["pga"]]["counts_w"]["P1p"][$proteins[$protein["pga"]]["sequence"][$pos+$length]] += $data[$i][$column_of_weight];
            if (isset($proteins[$protein["pga"]]["sequence"][$pos+$length+1])) 
            {
            	$results[$protein["pga"]]["counts_w"]["P2p"][$proteins[$protein["pga"]]["sequence"][$pos+$length+1]] += $data[$i][$column_of_weight];
            }
            if (isset($proteins[$protein["pga"]]["sequence"][$pos+$length+2])) 
            {
            	$results[$protein["pga"]]["counts_w"]["P3p"][$proteins[$protein["pga"]]["sequence"][$pos+$length+2]] += $data[$i][$column_of_weight];
            }
            
            $cleavages++;
            $cleavages++;
            $number_of_peptides++;
            $weights += $data[$i][$column_of_weight];
            $weights += $data[$i][$column_of_weight];
        }
    }
    $results[$protein["pga"]]["counts_w"]["cleavages"] = $cleavages;
    $results[$protein["pga"]]["counts_w"]["number_of_peptides"] = $number_of_peptides;
    $results[$protein["pga"]]["counts_w"]["weights"] = $weights;
}


//final calculations
//calculations of score S
foreach( $results as $pga => $value ) 
{
	if ($value["counts_w"]["cleavages"] > 0) {
		foreach( $analysis as $position => $value2 ) 
		{
			foreach( $AA as $key3 => $letter ) 
			{
				if( $results[$pga]["freq"][$letter] == 0 )  // do not attempt to calculate score for non-existent amino acids
				{
					$results[$pga]["score_w"][$position][$letter] = "";
				} 
				else 
				{
					//calculate score as frequence of occurence of amino acid at cleavage site divided by relative amino acid abundance in this protein
					$results[$pga]["score_w"][$position][$letter] = $results[$pga]["counts_w"][$position][$letter] / ($results[$pga]["counts_w"]["weights"] * $results[$pga]["freq"][$letter]);
				}
			}
		
		}
	}
}

//calculation of mean scores over all proteins weighted with number of cleavges
$averaged_results_w = array();
foreach ($analysis as $position => $value)
{
	foreach ($AA as $singleA)
	{
		$numerator1 = 0;
		$numerator2 = 0;
		$denominator1 = 0;
		$denominator2 = 0;
		
		//calculation of weighted mean
		foreach ($proteins_in_data as $key => $protein)
		{
			if ($results[$protein["pga"]]["counts"]["cleavages"] >= $minimum_number_of_cleavages) 
			{
				$numerator1 = $numerator1 + ($results[$protein["pga"]]["counts_w"]["cleavages"] * $results[$protein["pga"]]["score_w"][$position][$singleA]);
				$denominator1 = $denominator1 + $results[$protein["pga"]]["counts_w"]["cleavages"];
			}	
		}
		if ($denominator1 != 0) {
			$mean = ($numerator1 / $denominator1);
			$averaged_results_w[$position]["mean"][$singleA] = $mean;
		}
		
		//calculation of standard deviation of the weighted mean
		foreach ($proteins_in_data as $key => $protein)
		{
			if ($results[$protein["pga"]]["counts"]["cleavages"] >= $minimum_number_of_cleavages) 
			{
				$numerator2 = $numerator2 + ($results[$protein["pga"]]["counts_w"]["cleavages"] * pow(($results[$protein["pga"]]["score_w"][$position][$singleA]-$mean),2));
				$denominator2 = $denominator2 + $results[$protein["pga"]]["counts_w"]["cleavages"];
			}
		}
		if ($denominator2 != 0) {
			$sdm = sqrt($numerator2 / $denominator2);
			$averaged_results_w[$position]["sdm"][$singleA] = $sdm;
		}
	}
}

//calculate total numbers of peptides and cleavages that were considered
$total_number_of_cleavages = 0;
$total_number_of_peptides = 0;
foreach ($proteins_in_data as $key => $protein) 
{
	if ($results[$protein["pga"]]["counts"]["cleavages"] >= $minimum_number_of_cleavages)
	{
		$total_number_of_cleavages = $total_number_of_cleavages + $results[$protein["pga"]]["counts_w"]["cleavages"];
		$total_number_of_peptides = $total_number_of_peptides + $results[$protein["pga"]]["counts_w"]["number_of_peptides"];
	}
}
$averaged_results_w["total_number_of_peptides"] = $total_number_of_peptides;
$averaged_results_w["total_number_of_cleavages"] = $total_number_of_cleavages;

// Output
// ======

$line2 = "";
$consensus_array = array();

foreach ($weblogoconsensus as $line) 
{
	$pga_here = array_keys($line);
	if ($results[$pga_here[0]]["counts"]["cleavages"] >= $minimum_number_of_cleavages)
	{ 
		$line2 = $line2 . array_values($line)[0] . "%0D%0A"; // generate URL code
		$consensus_array[] = array_values($line); // generate consensus array
	}
}

$line2 = substr($line2, 0, -6);
$_SESSION['weblogo']['consensus'] = $line2; // store in temporary session
$_SESSION['weblogo']['consensus_array'] = $consensus_array;


//Output for Protein|Clpper in JSON format
if (!empty($results)) {
	$json_array = ["proteins_in_data" => $proteins_in_data, "results" => $results, "averaged_results" => $averaged_results, "averaged_results_u" => $averaged_results_u, "averaged_results_w" => $averaged_results_w, "proteins_in_fasta" => $proteins];
	$_SESSION['results_json'] = $json_array;
	$_SESSION['status']['calculated'] = true;
} else {
	$_SESSION['status']['calculated'] = false;
}
?>