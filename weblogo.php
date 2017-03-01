<?php include('header.php'); ?>
<div id="navigation">
				<ul>
					<li><a href="set_data_path.php">Upload data</a></li>
					<li><a href="results_overview.php">Results overview</a></li>
					<li><a href="cleavage_site_statistics.php">Cleavage site statistics</a></li>
					<li class="active"><a href="weblogo.php">Weblogo</a></li>
					<li><a href="downloads.php">Downloads</a></li>
				</ul>
			</div>
			<div id="content">
				<h2>Weblogo</h2>
				<p>
					<form id="weblogo_options" action="#">
						<table id="weblogo_options_table">
							<tr><td><label for="unit_name">Y-axis unit:</label></td>
							<td><select name="unit_name" id="unit_name" size="1">
								<option<?php
										if ($_SESSION['weblogo']['unit_name'] == 'bits') {
											echo(' selected');
										}
									?>>bits</option>
								<option<?php
										if ($_SESSION['weblogo']['unit_name'] == 'probability') {
											echo(' selected');
										}
									?>>probability</option>
							</select></td></tr>
							<tr><td><label for="yaxis_scale">Y-axis scale:</label></td>
							<td><input name="yaxis_scale" id="yaxis_scale" type="text" value="<?php
									if ($_SESSION['weblogo']['yaxis_scale'] > 0) {
										echo($_SESSION['weblogo']['yaxis_scale']);
									} else {
										echo('2');
									}
								?>"></td></tr>
						</table>
					</form>
					<a id="generate_weblogo" href="javascript:;">Generate Weblogo from session data</a>
					<div id="weblogo_image">
						<?php
							if ($_SESSION['weblogo']['img'] <> "") {
								echo('<img id="weblogo_img" src="data:'. $_SESSION['weblogo']['img'] .'">');
							}
							//print_r($_SESSION['weblogo']);
						?>
					</div>
                </p>
                <div id="citation">
                    Original publication: Crooks, G.E., Hon, G., Chandonia, J.M., Brenner, S.E., "WebLogo: A sequence logo generator", <em>Genome Research</em>, <strong>2004</strong>, 14:1188-1190 
                </div>
			</div>
<?php include('footer.php'); ?>