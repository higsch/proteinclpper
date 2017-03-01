<?php
	session_start();
	// Weblogo connector
	// Matthias Stahl, TU Muenchen
	// Version 0.1
	// December 2014

	
	// This function mimics a weblogo request to the weblogo server
	// Do not change any character!!
	function get_post_data($weblogoconsensus, $image_type, $unit_name, $yaxis_scale) {
		
		// Create virtual form
		$postdata_array = array(
		        'sequences' => $weblogoconsensus,
		        'cmd_create' => '%C2%A0Create%C2%A0Logo%C2%A0%C2%A0%C2%A0%C2%A0',
		        'format' => $image_type,
		        'stack_width' => 'medium',
		        'stacks_per_line' => '40',
		        'alphabet' => 'alphabet_protein',
		        'unit_name' => $unit_name,
		        'first_index' => '-3',
		        'logo_start' => '-3',
		        'logo_end' => '2',
		        'composition' => 'comp_auto',
		        'percentCG' => '',
		        'scale_width' => 'true',
		        'show_errorbars' => '',
		        'logo_title' => '',
		        'logo_label' => '',
		        'show_xaxis' => 'true',
		        'xaxis_label' => 'P sites',
		        'show_yaxis' => 'true',
		        'yaxis_label' => 'auto',
		        'yaxis_scale' => $yaxis_scale,
		        'yaxis_tic_interval' => '1.0',
		        'show_fineprint' => '',
		        'color_scheme' => 'color_auto',
		        'symbols0' => '',
		        'color0' => '',
		        'symbols1' => '',
		        'color1' => '',
		        'symbols2' => '',
		        'color2' => '',
		        'symbols3' => '',
		        'color3' => '',
		        'symbols4' => '',
		        'color4' => ''
		);
		
		// Create URL encoded string
		$postdata = '';
		foreach ($postdata_array as $key => $value) {
			$postdata = $postdata . $key .'='. $value .'&';
		}
		$postdata = substr($postdata, 0, -1);
	
		return $postdata;
	}
	
	// This function connects to the server where weblogos or icelogos will be generated
	// It is able to submit POST requests and collects the server answer
	function curl_connection($url, $postdata, $returntransfer) {
		$ch = curl_init();
	
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, $returntransfer);
		
		$result = curl_exec($ch);
		curl_close($ch);
		
		return $result;
	}

	$url = 'http://weblogo.threeplusone.com/create.cgi';
	$arrSubmit = get_post_data($_SESSION['weblogo']['consensus'], $_GET['image_type'], $_GET['unit_name'], $_GET['yaxis_scale']);
	$result = curl_connection($url, $arrSubmit, true);
	$src = 'image/png;base64,'.base64_encode($result);
	echo $src;
	//echo '<img id="weblogo_img" src="', $src, '">';
	//echo '<object id="weblogo_svg" data="'. $result . '</object>';
?>