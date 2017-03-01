<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	include('header.php');
} else {
	session_start();
}

$minimum_number_of_cleavages = 50;

// Consensus list button was pressed
if (isset($_POST['consensus_list']) && ($_POST['consensus_list'] == "Consensus list")) {
	if (isset($_SESSION['weblogo']['consensus_array'])) {
		$consensus_file_name = str_replace(".txt", "__consensus_list.csv", $_SESSION['psm_files']['name'][0]);
		$consensus_csv = fopen($consensus_file_name, 'w');
		foreach ($_SESSION['weblogo']['consensus_array'] as $line) {
			fputcsv($consensus_csv, $line);
		}
		fclose($consensus_csv);
		if(!$consensus_file_name) { // file does not exist
			die('file not found');
		} else {
			header('Content-Disposition: attachment; filename='. $consensus_file_name);
			// read the file from disk
			if (readfile($consensus_file_name) > 0) {
				unlink($consensus_file_name);
			}
			exit();
		}
	}
}

// Results button was pressed
if (isset($_POST['all_results']) && ($_POST['all_results'] == "All results")) {
	if (isset($_SESSION['results_json'])) {
		// Create and open file
		$output_file_name = str_replace(".txt","__all_results.csv", $_SESSION['psm_files']['name'][0]);
		$output_csv = fopen($output_file_name, 'w');
		$line = array();
		
		// Create file names string
		$files = "";
		foreach ($_SESSION['psm_files']['name'] as $filename) {
			$files = $files ." ". $filename;
		}
		$line[0] = "Analysis of ". $files;
		fputcsv($output_csv, $line);

		$timestamp = time();
		$date = date("d.m.Y - H:i", $timestamp);
		$line[0] = $date;
		fputcsv($output_csv, $line);

		$line = array();
		fputcsv($output_csv, $line);
		fputcsv($output_csv, $line);

		//write data for each position (P3, P2, ....)
		$analysis = array("P3" => -3, "P2" => -2, "P1" => -1, "P1p" => 0, "P2p" => 1, "P3p" => 2);
		foreach ($analysis as $position => $value) 
		{
			$line = array();
			$line[] = $position;
			fputcsv($output_csv, $line);
			
			//with proteins next to each other
			//write protein header and mean scores
			$line = array();
			$line[] = "Protein";
			$line[] = "Mean Score";
            $line[] = "log2 Mean Score";
			$line[] = "Standard deviation";
			
			$line2 = array();
			$line2[] = "Number of peptides";
			$line2[] = $_SESSION['results_json']['averaged_results']['total_number_of_peptides'];
			$line2[] = "";
            $line2[] = "";
			
			$line3 = array();
			$line3[] = "Number of cleavages";
			$line3[] = $_SESSION['results_json']['averaged_results']['total_number_of_cleavages'];
			$line3[] = "";
            $line3[] = "";
			
			foreach ($_SESSION['results_json']['proteins_in_data'] as $key => $protein)
			{
				$line[] = $protein["pga"]." (".$protein["name"].")";
				$line2[] = $_SESSION['results_json']['results'][$protein["pga"]]["counts"]["number_of_peptides"];
				$line3[] = $_SESSION['results_json']['results'][$protein["pga"]]["counts"]["cleavages"];
			}
			
			$line[] = "Mean score from unique peptides";
			$line[] = "Standard deviation of unique mean";
			$line2[] = $_SESSION['results_json']['averaged_results_u']["total_number_of_peptides_u"];
			$line2[] = "";
			$line3[] = $_SESSION['results_json']['averaged_results_u']["total_number_of_cleavages_u"];
			$line3[] = "";
			
			foreach ($_SESSION['results_json']['proteins_in_data'] as $key => $protein)
			{
				$line[] = $protein["pga"]." (".$protein["name"].")";
				$line2[] = $_SESSION['results_json']['results'][$protein["pga"]]["counts_u"]["number_of_peptides"];
				$line3[] = $_SESSION['results_json']['results'][$protein["pga"]]["counts_u"]["cleavages"];
			}
			
			$w = true; // FIXME
			if ($w)
			{
				$line[] = "Mean weighted score";
				$line[] = "Standard deviation of mean weighted score";
				$line2[] = "";
                $line2[] = "";
				$line3[] = "";
                $line3[] = "";
				
				foreach ($_SESSION['results_json']['proteins_in_data'] as $key => $protein)
				{
					$line[] = $protein["pga"]." (".$protein["name"].")";
					$line2[] = $_SESSION['results_json']['results'][$protein["pga"]]["counts"]["number_of_peptides"];
					$line3[] = $_SESSION['results_json']['results'][$protein["pga"]]["counts"]["cleavages"];
				}
			}
				
			fputcsv($output_csv, $line);
			fputcsv($output_csv, $line2);
			fputcsv($output_csv, $line3);
			
			//list result for each amino acid
			$AA = array("A", "C", "D", "E", "F", "G", "H", "I", "K", "L", "M", "N", "P", "Q", "R", "S", "T", "V", "W", "Y", "m", "s", "t");
			foreach ($AA as $singleA)
			{
				$line = array();
				$line[] = $singleA;
				$line[] = $_SESSION['results_json']['averaged_results'][$position]["mean"][$singleA];
                $line[] = log($_SESSION['results_json']['averaged_results'][$position]["mean"][$singleA])/log(2);
				$line[] = $_SESSION['results_json']['averaged_results'][$position]["sdm"][$singleA];
				
				foreach ($_SESSION['results_json']['proteins_in_data'] as $key => $protein)
				{
					$line[] = $_SESSION['results_json']['results'][$protein["pga"]]["score"][$position][$singleA];
				}
				
				$line[] = $_SESSION['results_json']['averaged_results_u'][$position]["mean"][$singleA];
				$line[] = $_SESSION['results_json']['averaged_results_u'][$position]["sdm"][$singleA];
				foreach ($_SESSION['results_json']['proteins_in_data'] as $key => $protein)
				{
					$line[] = $_SESSION['results_json']['results'][$protein["pga"]]["score_u"][$position][$singleA];
				}
				
				if ($w)
				{
					$line[] = $_SESSION['results_json']['averaged_results_w'][$position]["mean"][$singleA];
					$line[] = $_SESSION['results_json']['averaged_results_w'][$position]["sdm"][$singleA];
					foreach ($_SESSION['results_json']['proteins_in_data'] as $key => $protein)
					{
						$line[] = $_SESSION['results_json']['results'][$protein["pga"]]["score_w"][$position][$singleA];
					}
				}
				
				fputcsv($output_csv, $line);
			}
			
			//separation
			$line = array();
			$line[] = "";
			fputcsv($output_csv, $line);
			fputcsv($output_csv, $line);
		}
		fclose($output_csv);
		// Download and delete it
		if(!$output_file_name) { // file does not exist
			die('file not found');
		} else {
			header('Content-Disposition: attachment; filename='. $output_file_name);
			// read the file from disk
			if (readfile($output_file_name) > 0) {
				unlink($output_file_name);
			}
			exit();
		}
	}
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	include('header.php');
}
?>
<div id="navigation">
				<ul>
					<li><a href="set_data_path.php">Upload data</a></li>
					<li><a href="results_overview.php">Results overview</a></li>
					<li><a href="cleavage_site_statistics.php">Cleavage site statistics</a></li>
					<li><a href="weblogo.php">Weblogo</a></li>
					<li class="active"><a href="downloads.php">Downloads</a></li>
				</ul>
			</div>
			<div id="content">
				<h2>Downloads</h2>
				<p>Click on the buttons to download analyzed data.</p>
				<form id="download_list" action="downloads.php" method="post" enctype="multipart/form-data">
					<ul id="downloads_list">
						<li><input class="download_button" type="submit" name="all_results" value="All results"><label for="all_results">All available calculations.</label></li>
						<li><input class="download_button" type="submit" name="consensus_list" value="Consensus list"><label for="consensus_list">List of AA sequences around cleavage sites.</label></li>
					</ul>
				</form>
                <br /><br />
                <p>Download the Protein|Clpper user manual <a href="http://fluorophor.de/ProteinClpper_User_guide_v1.1.pdf">here</a>.</p>
                <br />
                <p>
                Example files:
                    <ul>
                        <li><a href="http://fluorophor.de/example_data/chymotrypsin_digest.txt">chymotrypsin_digest.txt</a></li>
                        <li><a href="http://fluorophor.de/example_data/chymotrypsin_digest_control.txt">chymotrypsin_digest_control.txt</a></li>
                        <li><a href="http://fluorophor.de/example_data/fasta_db.fasta">fasta_db.fasta</a></li>
                    </ul>
                </p>
			</div>
<?php include('footer.php'); ?>