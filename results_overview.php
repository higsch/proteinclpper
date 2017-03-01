<?php include('header.php'); ?>
<div id="navigation">
				<ul>
					<li><a href="set_data_path.php">Upload data</a></li>
					<li class="active"><a href="results_overview.php">Results overview</a></li>
					<li><a href="cleavage_site_statistics.php">Cleavage site statistics</a></li>
					<li><a href="weblogo.php">Weblogo</a></li>
					<li><a href="downloads.php">Downloads</a></li>
				</ul>
			</div>
			<div id="content" class="results_overview">
				<h2>Results overview</h2>
				<?php
					if ($_SESSION['results_json'] == NULL) {
						echo("<p>No data!</p>");
					}
				?>
				<h3>General information</h3>
					<p>
						<table id="results_overview_very_general">
							<tbody>
								<tr><td>PSM file name:</td><td><?php echo($_SESSION['psm_files']['name'][0]); ?></td></tr>
								<tr><td>FASTA file name:</td><td><?php echo($_SESSION['fasta_file']['name']); ?></td></tr>
								<tr><td>Proteins in data:</td><td><?php echo(count($_SESSION['results_json']['proteins_in_data'])); ?></td></tr>
							</tbody>
						</table>
						<table id="results_overview_general_information">
							<thead>
								<tr><td>Name</td><td>PGA</td><td>Start</td><td>End</td><td>Length</td><td>Coverage</td></tr>
							</thead>
							<tbody>
								<?php
									foreach ($_SESSION['results_json']['proteins_in_data'] as $key => $value) {
										echo("<tr><td>". $value['name'] ."</td><td><a href='http://uniprot.org/uniprot/". $value['pga'] ."'>". $value['pga'] ."</a></td><td>". $value['start'] ."</td><td>". $value['end'] ."</td><td>". $_SESSION['results_json']['proteins_in_fasta'][$value['pga']]['length'] ."</td><td>". round($_SESSION['results_json']['proteins_in_fasta'][$value['pga']]['seq_coverage'],2)*100 ."%</td></tr>");
									}
								?>
							</tbody>
						</table>
					</p>
				<h3>Information to single proteins</h3>
					<ul id="score_type">
						<li><a class="score_type_link" id="score" href="javascript:;">normal</a></li>
						<li><a class="score_type_link" id="score_w" href="javascript:;">weighted</a></li>
						<li><a class="score_type_link" id="score_u" href="javascript:;">unique peptides</a></li>
					</ul>
					<?php
						foreach ($_SESSION['results_json']['results'] as $key => $value) {
							echo('<table id="'. $key .'" class="results_overview_information_to_single_proteins">');
							echo('<thead><tr><td>'. $_SESSION['results_json']['proteins_in_fasta'][$key]['name'] .'</td><td>Cleavages</td><td>Peptides</td><td class="weights">Weights</td></tr></thead>');
							echo('<tbody><tr><td></td><td class="cleavages"></td><td class="number_of_peptides"></td><td class="weights"></td></tr>');
							echo('</table>');
						}
						echo('<table id="averaged_results" class="results_overview_information_to_single_proteins">');
						echo('<thead><tr><td>Averaged</td><td>Cleavages</td><td>Peptides</td><td class="weights">Weights</td></tr></thead>');
						echo('<tbody><tr><td></td><td class="cleavages"></td><td class="number_of_peptides"></td><td class="weights"></td></tr>');
						echo('</table>');
					?>			
				<h3>Sequence coverage</h3>
					<ul id="score_type_seq_coverage">
						<li><a class="score_type_link" id="score" href="javascript:;">normal</a></li>
						<li><a class="score_type_link" id="score_w" href="javascript:;">weighted</a></li>
					</ul>
					<div id="charts">
						<?php
							foreach ($_SESSION['results_json']['results'] as $key => $value) {
								echo('<h4>'. $_SESSION['results_json']['proteins_in_fasta'][$key]['name']  .'</h4>');
								echo('<div class="seq_coverage_chart" id="seq_coverage_chart_'. $key .'"></div>');
							}
						?>
					</div>
				<h3>Amino acid distribution</h3>
					<div id="charts">
						<?php
							foreach ($_SESSION['results_json']['results'] as $key => $value) {
								echo('<h4>'. $_SESSION['results_json']['proteins_in_fasta'][$key]['name']  .'</h4>');
								echo('<div class="aa_distribution_chart" id="aa_distribution_chart_'. $key .'"></div>');
							}
						?>
					</div>
			</div>
<?php include('footer.php'); ?>