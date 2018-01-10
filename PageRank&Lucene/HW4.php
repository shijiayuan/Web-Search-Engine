<?php
header('Content-Type:text/html; charset=utf-8');
$limit = 10;
$query= isset($_REQUEST['q'])?$_REQUEST['q']:false;
$results = false;

if($query){
        require_once('solr-php-client/Apache/Solr/Service.php');
        $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample/');
        if(get_magic_quotes_gpc() == 1){
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
		} ?>

	<table style="border: 1px solid black; text-align: left">

	
			<tr><th>Title</th><td><a href = <?php echo $url ; ?>>
			        <?php echo htmlspecialchars($Title,  ENT_NOQUOTES, 'utf-8') ; ?></a></td></tr>
			<tr><th>URL</th><td><a href = <?php echo $url ; ?>>
			        <?php echo htmlspecialchars($url,  ENT_NOQUOTES, 'utf-8') ; ?></a></td></tr>
			<tr><th>ID</th><td>
				    <?php echo htmlspecialchars($ID,  ENT_NOQUOTES, 'utf-8') ; ?></td></tr>
			<tr><th>Description</th><td>
			        <?php echo htmlspecialchars($Description,  ENT_NOQUOTES, 'utf-8') ; ?></td></tr>
	</table>
</li>
	<?php } ?>
    
<?php } ?>
</ol>
</body>
</html>

