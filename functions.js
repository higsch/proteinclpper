// ***********************************************************************
// Global javascript functions for Protein|Clpper
// Matthias Stahl, TU Muenchen, AK Sieber
// Version 0.2
// January 2015
// ***********************************************************************
//
// ***********************************************************************
// version 0.1 December 2014
// - basic functions and click functionalities
// - cleavage site statistics
// ***********************************************************************
// version 0.2 January 2015
// - overview tables
// - sequence coverage
// - AA distribution
// - minor selection functionalities
// ***********************************************************************
// version 0.2 March 2015
// - log2 checkbox and respective data representation
// - "session mind"
// ***********************************************************************


// Let's start...
// Set constants
const TOTALAAS = 23; // Number of possible amino acids
const JSON_PATH = 'get_json.php'; // Link to psm-analyzer by Malte Gersch
const SET_SESSION_PHP = 'set_session_variables.php';
const GET_SESSION_PHP = 'get_session_variables.php';
const WAITING_TEXT = 'Waiting for response fom Weblogo server...';
const AXISY_TITLE = 'score';
const AXISY_TITLE_LOG2 = 'log2 score';

// This function creates an object/array for CanvasJS and draws six charts for each site
function drawColumnChart(json_result, pga, score_type) {
	// Request options
	var log2_data = false;
	var axisy_title = AXISY_TITLE;
	// Data representation in log2?
	if ($("input#options_log2").prop("checked") == true) {
		log2_data = true;
		axisy_title = AXISY_TITLE_LOG2;
	}
	// Check if averaged values or single protein values are requested
	if (pga == 'averaged') {
		// Ok, first the averaged ones
		// Set selector for results array and subkeys
		var selector = "averaged_results";
		var total_number_of_peptides = "total_number_of_peptides";
		var total_number_of_cleavages = "total_number_of_cleavages";
		if (score_type == "score_u") {
			selector += "_u";
			total_number_of_peptides += "_u";
			total_number_of_cleavages += "_u";
		} else if (score_type == "score_w") {
			selector += "_w";
		}
		
		// Each P site
		$.each(json_result[selector], function(site_key, site_value){
			if ((site_key != total_number_of_peptides) && (site_key != total_number_of_cleavages)) {
				var new_dataset = new Array(TOTALAAS);
				var i = 0;
				// Take each AA and reformat for CanvasJS
				$.each(site_value['mean'], function(position_key, position_value) {
					var temp_obj = new Object;
					temp_obj['label'] = position_key;
					var recalculated_position_value = position_value;
					if (log2_data) {
						if (position_value != 0) {
							recalculated_position_value = Math.log(position_value)/Math.log(2);
						}
					}
					temp_obj['y'] = recalculated_position_value;
					new_dataset[i] = temp_obj;
					i++;
				})
				// Set options for graph
				var options = {
					axisX: {
						title: site_key + " amino acid",
						interval: 1
					},
					axisY: {
						title: axisy_title
					},
					data: [
						{
							type: "column",
							dataPoints: new_dataset
						}
					]
				};
				// Render chart
				$("#chart_" + site_key).CanvasJSChart(options);
			}
	});
	// Now the not averaged results are drawn
	} else {
		console.log(json_result["results"][pga]);
		$.each(json_result["results"][pga][score_type], function(results_key, results_value){
			var new_dataset = new Array(TOTALAAS);
			var i = 0;
			// Take each AA and reformat for CanvasJS
			$.each(results_value, function(key, value) {
				var temp_obj = new Object;
				temp_obj['label'] = key;
				var recalculated_position_value = value;
				if (log2_data) {
					if (value != 0) {
						recalculated_position_value = Math.log(value)/Math.log(2);
					}
				}
				temp_obj['y'] = recalculated_position_value;
				new_dataset[i] = temp_obj;
				i++;
			});
			// Set options for graph
			var options = {
				axisX: {
					title: results_key + " amino acid",
					interval: 1
				},
				axisY: {
					title: axisy_title
				},
				data: [
					{
						type: "column",
						dataPoints: new_dataset
					}
				]
			};
			// Render chart
			$("#chart_" + results_key).CanvasJSChart(options);
		});
	}
}


// This function draws the chart for sequence coverage
function drawSeqCoverageChart(json_result, score_type) {
	var coverage_type = score_type.replace('score', 'coverage');
	$.each(json_result['results'], function(results_key, results_value){
		var i = 0;
		var new_dataset = new Array(json_result['proteins_in_fasta'][results_key]['length']);
		// Take each position and reformat for CanvasJS
		$.each(json_result['proteins_in_fasta'][results_key][coverage_type], function(fasta_key, fasta_value){
			var temp_obj = new Object;
			temp_obj['x'] = fasta_key;
			temp_obj['y'] = fasta_value;
			new_dataset[i] = temp_obj;
			i++;
		});
		// Set options for graph
		var options = {
			//exportEnabled: true,
			axisX: {
				title: "position"
			},
			axisY: {
				title: "counts"
			},
			data: [
				{
					type: "area",
					color: "#007bbd",
					fillOpacity: .8,
					dataPoints: new_dataset
				}
			]
		};
		// Render chart
		$("#seq_coverage_chart_" + results_key).CanvasJSChart(options);
	});
}


// This function draws the chart for Aa distribution
function drawAaDistributionChart(json_result) {
	$.each(json_result['results'], function(results_key, results_value){
		var i = 0;
		var new_dataset = new Array(TOTALAAS);
		// Take each AA and reformat for CanvasJS
		$.each(json_result['results'][results_key]['freq'], function(freq_key, freq_value){
			var temp_obj = new Object;
			temp_obj['label'] = freq_key;
			temp_obj['y'] = freq_value;
			new_dataset[i] = temp_obj;
			i++;
		});
		// Set options for graph
		var options = {
			axisX: {
				title: "amino acid",
				interval: 1
			},
			axisY: {
				title: "frequency"
			},
			data: [
				{
					type: "column",
					dataPoints: new_dataset
				}
			]
		};
		// Render chart
		$("#aa_distribution_chart_" + results_key).CanvasJSChart(options);
	});

}


// This function fills tables for information to single proteins
function fillOverviewTables(json_result, score_type) {
	// Change score to counts (this is due to optimizing click events)
	var counts_type = score_type.replace("score", "counts");
	
	// Extract number of peptides and cleavages from results
	$.each(json_result['results'], function(key, value){
		$("table#" + key + " tr td.cleavages").empty().append(value[counts_type]['cleavages']);
		$("table#" + key + " tr td.number_of_peptides").empty().append(value[counts_type]['number_of_peptides']);
		if (counts_type == 'counts_w') {
			$("table#" + key + " tbody tr td.weights").empty().append(value[counts_type]['weights']).css({'display':'table-cell'});
			$("table#" + key + " thead tr td.weights").css({'display':'table-cell'});
		} else {
			$("table#" + key + " tbody tr td.weights").empty().css({'display':'none'});
			$("table#" + key + " thead tr td.weights").css({'display':'none'});
			$("table#averaged_results tr td.weights").css({'display':'none'});
		}
	});
	
	// Extract number of peptides and cleavages from averaged_results
	var selector = "averaged_results";
	var total_number_of_cleavages = "total_number_of_cleavages";
	var total_number_of_peptides = "total_number_of_peptides";
	// Change suffix for different types of averaged results
	if (score_type == "score_u") {
		selector += "_u";
		total_number_of_cleavages += "_u";
		total_number_of_peptides += "_u";
	} else if (score_type == "score_w") {
		selector += "_w";
	}
	// Finally go in JSON array and find values
	$("table#averaged_results tr td.cleavages").empty().append(json_result[selector][total_number_of_cleavages]);
	$("table#averaged_results tr td.number_of_peptides").empty().append(json_result[selector][total_number_of_peptides]);
}


// This function sets PHP session variables
function setSessionVariable(request_var, fieldname_var, value_var) {
	$.ajax({
		url: SET_SESSION_PHP,
		type: 'post',
		data: {request : request_var, fieldname : fieldname_var, value : value_var}
	});
}


// This function gets session variables from PHP
function getSessionVariables(request_var) {
	var result = null;
	$.ajax({
	    url: GET_SESSION_PHP + '?request=' + request_var,
	    type: 'get',
	    dataType: 'json',
	    async: false,
	    success: function(data) {
	        result = data;
	    }
	});
	return result;  
}


// This function converts string values to boolean format
function strToBool(value) {
	if (value == 'true') {
		return true;
	} else {
		return false;
	}
}


// Execute when html is loaded
// This is the main function called from each site
$(document).ready(function() {
	// Tests
	//console.log(getSessionVariables('all'));

	// Set standard selection
	$("a.score_type_link#score").parent().addClass('active');
	$("ul#pga_list").children().first().addClass('active');
	
	// Get analysis results from psm-analyzer and play with it
	$.ajax({
		// JSON_PATH connects JS with PHP
    	url: GET_SESSION_PHP + '?request=results_json',
		datatype: 'json',
		success: function(data) {
			// Only when POST request was successful and content is not NULL
			// Draw site statistics by default
			if (data != null) {
				// Cleavage site statistics opened
				if ($("#content").attr('class') == 'cleavage_site_statistics') {
					drawColumnChart(data, $("ul#pga_list li.active a").attr('id'), $("a.score_type_link").parent().filter(".active").children().attr('id'));
				}
				
				// Results overview opened
				// Draw overview graphs and fill tables by default
				if ($("#content").attr('class') == 'results_overview') {
					fillOverviewTables(data, $("a.score_type_link").parent().filter(".active").children().attr('id'));
					drawSeqCoverageChart(data, $("ul#score_type_seq_coverage li.active a").attr('id'));
					drawAaDistributionChart(data);
				}
				
				// Oh, a click: analyse where request comes from and give answer
				$("a.score_type_link").click(function(event) {
					// The site statistics
					if ($("#content").attr('class') == 'cleavage_site_statistics') {
						drawColumnChart(data, $("a.pga").parent().filter(".active").children().attr('id'), $(this).attr('id'));
					}
					// The overview tables
					if (($("#content").attr('class') == 'results_overview') && ($(this).parent().parent().attr('id') == 'score_type')) {
						fillOverviewTables(data, $(this).attr('id'));
					}
					// The sequence coverage
					if (($("#content").attr('class') == 'results_overview') && ($(this).parent().parent().attr('id') == 'score_type_seq_coverage')) {
						drawSeqCoverageChart(data, $(this).attr('id'));
					}
					// Change style of selectors
					$(this).parent().parent().children().removeClass('active');
					$(this).parent().addClass('active');
				});
				
				// Another click, on the PGAs: Draw again site statistics
				$("a.pga").click(function(event) {
					if ($("#content").attr('class') == 'cleavage_site_statistics') {
						drawColumnChart(data, $(this).attr('id'), $("a.score_type_link").parent().filter(".active").children().attr('id'));
					}
					// Change selector style
					$(this).parent().parent().children().removeClass('active');
					$(this).parent().addClass('active');
				});
				
				// Click on options in cleavage site statistics
			    $("input#options_log2").click(function(event) {
			    	setSessionVariable('options', 'statistics_log2_checked', $("input#options_log2").prop('checked'));
				    drawColumnChart(data, $("a.pga").parent().filter(".active").children().attr('id'), $("a.score_type_link").parent().filter(".active").children().attr('id'));
			    });
			}
		}
    });
    
    // Generate weblogo
    $("a#generate_weblogo").click(function(event) {
    	// Set default values for weblogo variables
    	var image_type = 'png_print';
    	var unit_name = 'bits';
    	var yaxis_scale = '2';
    	
    	// Overwrite default values with custom selections
    	unit_name = $("select#unit_name option:selected").text();
    	if ($.isNumeric($("input#yaxis_scale").val())) {
	    	yaxis_scale = $("input#yaxis_scale").val();
    	}
    	    	
	    $.ajax({
		    url: 'generate_weblogo.php?image_type=' + image_type + '&unit_name=' + unit_name + '&yaxis_scale=' + yaxis_scale,
		    type: 'get',
		    datatype: 'text',
		    beforeSend: function(thisXHR) {
		    	// A pleasure for the user: there's progress going on
		    	$("#weblogo_image").empty().css({'font-style':'italic','display':'none'}).append(WAITING_TEXT).fadeIn('fast');
            },
		    success: function(data) {
		    	// Generate object with weblogo session variables
		    	setSessionVariable('weblogo', 'unit_name', unit_name);
		    	setSessionVariable('weblogo', 'yaxis_scale', yaxis_scale);
		    	setSessionVariable('weblogo', 'img', data);
				// Output on screen
		    	var img_tag = '<img id="weblogo_img" src="data:' + data + '">';
		    	$("#weblogo_image").empty().css({'font-style':'normal','display':'none'}).append(img_tag).fadeIn('fast');
		    }
	    });
    });
});