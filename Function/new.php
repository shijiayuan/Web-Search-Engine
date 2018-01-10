<?php
include 'SpellCorrector.php';
ini_set('memory_limit', '-1');
header('Content-Type:text/html; charset=utf-8');
$limit = 10;
$query= isset($_REQUEST['q'])?$_REQUEST['q']:false;
$query_terms = explode(" ", $query);
if (sizeof($query_terms) > 1){
	$corrected_query = "";
	$corrected_query_terms = array();
	for($i = 0; $i < sizeof($query_terms) - 1; $i++){
		$corrected_query_terms[] = SpellCorrector::correct($query_terms[$i]);
		$corrected_query .= $corrected_query_terms[$i] . " ";
	}
	$corrected_query_terms[] = SpellCorrector::correct($query_terms[sizeof($query_terms) - 1]);
	$corrected_query .= $corrected_query_terms[sizeof($query_terms) - 1];
}
else{
	$corrected_query = SpellCorrector::correct($query);
}

$results = false;

if($corrected_query){
        require_once('solr-php-client/Apache/Solr/Service.php');
        $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample/');
        if(get_magic_quotes_gpc() == 1){
                $corrected_query = stripslashes($corrected_query);
                $query = stripslashes($query);

        }
        try{
		if(!isset($_GET['algorithm']))$_GET['algorithm']="lucene";
		if($_GET['algorithm'] == "lucene"){

			 $results = $solr->search($query, 0, $limit);

		}else{

			$param = array('sort'=>'pageRankFile desc');
			$results = $solr->search($query, 0, $limit, $param);

		}

	 }
        catch(Exception $e){
                die("<html><head><title>SEARCH EXCEPTION</title></head><body><pre>{$e->__toString()}</pre></body></html>");
        }
}

//echo $query . "<br/>";
//echo $corrected_query . "<br/>";


$map;
$CSVfp = fopen("USA Today Map.csv", "r");
if ($CSVfp !== FALSE) {
	while(!feof($CSVfp)) {
		$data = fgetcsv($CSVfp, 1000, ",");
		$map[$data[0]] = $data[1];
	}
}
fclose($CSVfp);
?>

<html>
<head>
        <title> Jiayuan Shi </title>
<style>
	body{
		background: pink; 
	}	
</style>
       <link rel="stylesheet" href="http://code.jquery.com/ui/1.12.1/themes/sunny/jquery-ui.css">
	   <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.0/jquery.min.js"></script>
	   <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
       <script type="text/javascript">
		$(function(){
			$("#q").autocomplete({
				source: function( request, response ) {
					var words = request.term.toLowerCase().split(/\s+/);
					if (words.length <= 1) {
						$.ajax({
							type: "GET",
							url: "solr.php",
							data: { 
								suggest: encodeURI(request.term.toLowerCase()),
							}, 
							success: function( data ) {
								console.log(encodeURI(request.term.toLowerCase()));
								data = $.parseJSON(data);
						        response($.map(data, function( item ) { 
						            return { 
						                label: item.term,
						                value: item.term,
						            }; 
						   		}));
						    }
						}); 						
					} else {
						var prev_word = "";
						var cur_word = words[words.length - 1];
						for (var i = 0; i < words.length - 1; i++) {
							prev_word += words[i] + " ";
						}
						$.ajax({
							type: "GET",
							url: "solr.php",
							data: { 
								suggest: encodeURI(cur_word.toLowerCase()),
							}, 
							success: function( data ) {
								console.log(encodeURI(cur_word.toLowerCase()));
								data = $.parseJSON(data);
						        response($.map(data, function( item ) { 
						            return { 
						                label: prev_word + " " + item.term,
						                value: prev_word + " " + item.term,
						            }; 
						   		}));
						    }
						}); 												
					}
					
				},
				minLength: 1
			});
		});
	</script>


</head>
<body>
<div style="width:100%;text-align:center">
<h1> USA Today Search Compare </h1><br/>

<form accept-charset="utf-8" method="get">

    <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8');?>"/><br/><br/> 
	<input type="radio" name="algorithm" value="lucene" /> Lucene&nbsp;&nbsp;&nbsp;&nbsp;        
	<input type="radio" name="algorithm" value="pagerank" /> PageRank <br/><br/> 
	<input type="submit" />
</form>
</div>
<?php
if($results){
	    if (strtolower($query) !== $corrected_query){
	    	echo "<p>Showing results for: <a href=\"new.php?algorithm=lucene&q=" . htmlspecialchars($corrected_query, ENT_QUOTES, 'utf-8') . "\">" . $corrected_query . "</a></p>";
	    }
        $total = (int)$results->response->numFound; 
        $start = min(1,$total);
        $end = min($limit, $total); 
?>
<div> Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total;?>:</div> 
<ol>
<?php

foreach ($results->response->docs as $doc){
        
?>

<li>
	<?php foreach($doc as $field => $value){
			if($field!="id" && $field!="title" && $field!="description" && $field!="og_url")continue;  
			elseif($field=="title"){
				$Title = $value;
			}
			elseif($field=="id"){
				$ID = $value;
				$pos=strripos($value,"/");
				$value=substr($value,$pos+1);
				$url=$map[$value];
			}
			elseif($field=="description"){
				$Description = $value;
			}
		}
		    $filepath = preg_replace("/\/Users\/jiayuan\/Downloads\/solr-7.1.0\/USAToday\//","", $ID);
			$file_content = file_get_contents($filepath);
			$str = preg_replace("/<script[^>]*?>.*?<\/script>/si","", $file_content); 
		    $str = preg_replace("/<([a-z]+)[^>]*>/i","#", $str); 
		    $str = preg_replace("/<\/([a-z]+)[^>]*>/i","#", $str); 
		    $pattern = "/[^:#.?!]*\\b(" . $query . ")\\b[^#.?!|]*[.?!]/i";
			preg_match_all($pattern, $str, $matches);
			$snippet = "";
			if (isset($matches[0][0])) {
				$snippet = $matches[0][0];
			} else if (stristr($description, $query)) {
				$snippet = $description;
			}?>

	<table style="border: 1px solid black; text-align: left">
	
			<tr><th>Title</th><td><a href = <?php echo $url ; ?>>
			        <?php echo htmlspecialchars($Title,  ENT_NOQUOTES, 'utf-8') ; ?></a></td></tr>
			<tr><th>URL</th><td><a href = <?php echo $url ; ?>>
			        <?php echo htmlspecialchars($url,  ENT_NOQUOTES, 'utf-8') ; ?></a></td></tr>
			<tr><th>ID</th><td>
				    <?php echo htmlspecialchars($ID,  ENT_NOQUOTES, 'utf-8') ; ?></td></tr>
			<tr><th>Description</th><td>
			        <?php echo htmlspecialchars($Description,  ENT_NOQUOTES, 'utf-8') ; ?></td></tr>
			<tr><th>Snippet</th><td>
				    <?php echo htmlspecialchars($snippet, ENT_NOQUOTES, 'utf-8'); ?></td></tr>
	</table>
</li>
	<?php } ?>
    
<?php } ?>
</ol>
</body>
</html>

