<?php include('header.php'); ?>
<div id="navigation">
				<ul>
					<li><a href="set_data_path.php">Upload data</a></li>
					<li><a href="results_overview.php">Results overview</a></li>
					<li class="active"><a href="cleavage_site_statistics.php">Cleavage site statistics</a></li>
					<li><a href="weblogo.php">Weblogo</a></li>
					<li><a href="downloads.php">Downloads</a></li>
				</ul>
			</div>
			<div id="content" class="cleavage_site_statistics">
				<h2>Cleavage site statistics</h2>
				<div id="proteins_in_data">
					<table id="proteins_in_data_table">
						<ul id="score_type">
							<li><a class="score_type_link" id="score" href="javascript:;">normal</a></li>
							<li><a class="score_type_link" id="score_w" href="javascript:;">weighted</a></li>
							<li><a class="score_type_link" id="score_u" href="javascript:;">unique peptides</a></li>
						</ul>
						<ul id="pga_list">
						<?php
							$proteins = $_SESSION['results_json']['proteins_in_data'];
							if ($proteins != "") {
								foreach ($proteins as $key => $pgas) {
									if (isset($_SESSION['results_json']['results'][$pgas['pga']]['score'])) {
										echo('<li><a class="pga" id="'. $pgas['pga'] .'" href="javascript:;">'. $pgas['name'] .'</a></li>');
									}
								}
							echo('<li><a class="pga" id="averaged" href="javascript:;">averaged</a></li>');
							}
						?>
						</ul>
					</table>
					<form id="proteins_in_data_options" action="#">
						<table id="proteins_in_data_options_table">
							<tr><td>Options:</td>
							<td><input type="checkbox" name="log2" id="options_log2" value="log2"<?php
								if ($_SESSION['options']['statistics_log2_checked'] == 'true') {
									echo(' checked="checked"');
								}
							?>> Log2</td></tr>
						</table>
					</form>
				</div>
				<div id="charts">
					<div id="chart_P3"></div>
					<div id="chart_P2"></div>
					<div id="chart_P1"></div>
					<div id="chart_P1p"></div>
					<div id="chart_P2p"></div>
					<div id="chart_P3p"></div>
				</div>
			</div>
<?php include('footer.php'); ?>