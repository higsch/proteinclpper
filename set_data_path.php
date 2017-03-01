<?php
	// Assign files to session environment
	function assignFiles() {
		// PSM file selected
		if (isset($_FILES['psm_files'])) {
			$success = false;
			$is_error = false;
			foreach ($_FILES['psm_files']['error'] as $key => $error) {
				if ($error != 0) {
					$is_error = true;
				}
			}
			if (!$is_error) {
				// Delete former array
				unset($_SESSION['psm_files']);
				// Set PSM file names
				foreach ($_FILES['psm_files']['name'] as $key => $psm_file_name) {
					$_SESSION['psm_files']['name'][$key] = $psm_file_name;
				}
				// Set PSM file paths
				$dataoutput = array();
				foreach ($_FILES['psm_files']['tmp_name'] as $key => $psm_file_path) {
					$_SESSION['psm_files']['path'][$key] = $psm_file_path;
					$handle = fopen($psm_file_path, "r");
					if ($handle) {
						$row_counter = 0;
						while (($data2 = fgetcsv($handle, 0, "\t")) !== FALSE) 
						{
                            if ($row_counter > 0) {
                                $dataoutput[] = $data2;
                            } else {
                                $column_headers = $data2;
                            }
							$row_counter++;
						}
						fclose($handle);
					}
				}
                $dataoutput['column_headers'] = $column_headers;
				
			    // Check if PSM files are not empty
			    if (! empty($dataoutput[1])) {
					$_SESSION['psm_files']['content'] = $dataoutput;
					$success = true;
				}
			}
		}
		
		// Control PSM files selected
		if (isset($_FILES['control_psm_files'])) {
			$success = false;
			$is_error = false;
			foreach ($_FILES['control_psm_files']['error'] as $key => $error) {
				if ($error != 0) {
					$is_error = true;
				}
			}
			if (!$is_error) {
				// Delete former array
				unset($_SESSION['control_psm_files']);
				// Set Control PSM file names
				foreach ($_FILES['control_psm_files']['name'] as $key => $control_psm_file_name) {
					$_SESSION['control_psm_files']['name'][$key] = $control_psm_file_name;
				}
				// Set Control PSM file paths
				$dataoutput = array();
				foreach ($_FILES['control_psm_files']['tmp_name'] as $key => $control_psm_file_path) {
					$_SESSION['control_psm_files']['path'][$key] = $control_psm_file_path;
					$handle = fopen($control_psm_file_path, "r");
					if ($handle) {
						$row_counter = 0;
						while (($data2 = fgetcsv($handle, 0, "\t")) !== FALSE) 
						{
							$dataoutput[] = $data2;
							if ($row_counter > 0) {
                                $dataoutput[] = $data2;
                            } else {
                                $column_headers = $data2;
                            }
							$row_counter++;
						}
						fclose($handle);
					}
				}
                $dataoutput['column_headers'] = $column_headers;
				
			    // Check if Control PSM files are not empty
			    if (! empty($dataoutput[1])) {
					$_SESSION['control_psm_files']['content'] = $dataoutput;
					$success = true;
				}
			}
		}
		
		// FASTA file selected
		if (isset($_FILES['fasta_file'])) {
			if ($_FILES['fasta_file']['error'] == 0) {
				// Delete former array
				unset($_SESSION['fasta_file']);
				
				$_SESSION['fasta_file']['name'] = $_FILES['fasta_file']['name'];
				$_SESSION['fasta_file']['path'] = $_FILES['fasta_file']['tmp_name'];
				$_SESSION['fasta_file']['content'] = file_get_contents($_FILES['fasta_file']['tmp_name']);
				$success = true;
			}
		}
		
		return $success;
	}
	
	include('header.php');
	
	// The psm upload button was pushed
	if (isset($_POST['psm_upload']) or isset($_POST['fasta_upload']) or isset($_POST['control_psm_upload'])) {
		if (assignFiles()) {
			$_SESSION['status']['upload'] = '<span class="red">needs to be recalculated</span>';
		}
	}
	
	// The delete control PSM button was pressed
	if (isset($_POST['delete_control_psm_upload'])) {
		unset($_SESSION['control_psm_files']);
		$_SESSION['status']['upload'] = '<span class="red">needs to be recalculated</span>';
	}
	
	// Are the session variables set?
	if (isset($_SESSION['psm_files']) and isset($_SESSION['fasta_file'])) {
		// Check if there is content in the files
		if (($_SESSION['psm_files']['content'] != "") and ($_SESSION['fasta_file']['content'] != "")) {
			// Calculate
			if (isset($_POST['calculate'])) {
				// Delete old weblogo
				$_SESSION['weblogo']['consensus'] = '';
				$_SESSION['weblogo']['img'] = '';
				// Evoke calculation
				include('psm-analyzer.php');
				// Update status
				$_SESSION['status']['upload'] = '<span class="green">successful calculation</span>';
			}
		} else {
			// No file content, check if there is a previous calculation
			if ($_SESSION['status']['calculated']) {
				$_SESSION['status']['upload'] = '<span class="green">session variables calculated before</span>';
			} else {
				$_SESSION['status']['upload'] = '<span class="red">no file content</span>';
			}
		}
	} else {
		$_SESSION['status']['upload'] ='<span class="red">no session variables</span>';
	}
?>
<div id="navigation">
				<ul>
					<li class="active"><a href="set_data_path.php">Upload data</a></li>
					<li><a href="results_overview.php">Results overview</a></li>
					<li><a href="cleavage_site_statistics.php">Cleavage site statistics</a></li>
					<li><a href="weblogo.php">Weblogo</a></li>
					<li><a href="downloads.php">Downloads</a></li>
				</ul>
			</div>
			<div id="content">
				<h2>Upload data</h2>
                <p>Please pay attention that large file connections cannot be handled and will result in an error.</p>
				<h3>Getting started</h3>
				<ol id="set_data_path_manual">
					<li>Select PSM and FASTA files. Add optionally several control PSM files.</li>
					<li>Calculate results.</li>
				</ol>
				<form id="file_upload" action="set_data_path.php" method="post" enctype="multipart/form-data">
					<table class="set_data_path_upload">
						<tr><td><label for="psm_file">PSM file(s):</label></td>
						<td><input class="normal_button" id="psm_file" type="file" name="psm_files[]" multiple="multiple"></td>
						<td><input class="normal_button" type="submit" name="psm_upload" value="Upload PSM file(s)"></td></tr>
					</table>
					<table class="set_data_path_upload">
						<tr><td><label for="control_psm_files">Control PSM file(s):</label></td>
						<td><input class="normal_button" id="control_psm_files" type="file" name="control_psm_files[]" multiple="multiple"></td>
						<td><input class="normal_button" type="submit" name="control_psm_upload" value="Upload Control PSM file(s)"></td>
						<td><input class="normal_button" type="submit" name="delete_control_psm_upload" value="Delete Control PSM file(s)"></td></tr>
					</table>
					<table class="set_data_path_upload">
						<tr><td><label for="fasta_file">FASTA file:</label></td>
						<td><input class="normal_button" type="file" name="fasta_file"></td>
						<td><input class="normal_button" type="submit" name="fasta_upload" value="Upload FASTA file"></td></tr>
					</table>
					<input class="highlighted_button" type="submit" name="calculate" value="Calculate">
				</form>
				<h3>System status</h3>
				<p>
					<table id="set_data_path_set_files">
						<tbody>
							<tr><td>PSM file name(s):</td><td><?php
								foreach ($_SESSION['psm_files']['name'] as $filename) {
									echo($filename ." ");
								}
							?></td></tr>
							<tr><td>Control PSM file name(s):</td><td><?php
								foreach ($_SESSION['control_psm_files']['name'] as $filename) {
									echo($filename ." ");
								}
							?></td></tr>
							<tr><td>FASTA file name:</td><td><?php echo($_SESSION['fasta_file']['name']); ?></td></tr>
							<tr><td>Status:</td><td><?php echo($_SESSION['status']['upload']); ?></td></tr>
						</tbody>
					</table>
				</p>
				<?php
					/*
					echo("<pre>");
					print_r($_SESSION['psm_files']['content']);
					echo("</pre>");
					*/
				?>
			</div>
<?php include('footer.php'); ?>