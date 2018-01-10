<?php
	header('Access-Control-Allow-Origin: *');
	$url = "http://localhost:8983/solr/myexample/suggest?wt=json&indent=on&q=";
	if (!isset($_GET["suggest"])) {
		$para = "*:*";
		$url.= $para;
	} else {
		$para = $_GET["suggest"];
		$url.= rawurlencode($para);
	}
	$URLContent = file_get_contents($url);
	$congressJson = json_decode($URLContent,true);
	echo json_encode($congressJson["suggest"]["suggest"][$para]["suggestions"])
?>

