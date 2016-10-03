<?php
/**
 * Converte dados XML de uma carga de URNs do LexML, em dados SQL.
 * Opera em terminal, tipicamente com stdin em pipe. Exemplo:
 *  cat map1.xml | php parse2sql.php | psql -h localhost -p 5432 -U postgres
 *  cat map1.xml | php parse2sql.php | grep ERRO
 *  php parse2sql.php map1.xml draft > lixo.sql
 *  php parse2sql.php map1.xml escopo-leg > lixo.sql
 * @see    carga.php
 * @author https://github.com/ppKrauss/getlex
 */
ini_set("memory_limit","256M"); // ok para o DOM? prefixos ocupam ~250kb

// // BEGIN:CONFIGS // // //
$prefixCSV='data/urn_prefixos.csv';
// // END:CONFIGS //

$draftmode = false;
$escopo = '';
$parte2escopo = ['tip'=>[], 'aut'=>[]];

$xmlFile='php://stdin';
// refazer usando getopt('f:e:d')
if (isset($argv[1])) { // nome de arquivo para carga ou diretiva de processamento

	$opt = $argv[1];
	$flag = 0;
	if (isset($argv[2])){
		$xmlFile =$argv[1];
		$opt = $argv[2];
		$flag = 1;
	}
	if ($opt=='draft') 
		$draftmode = true;
	elseif (substr($opt,0,7)=='escopo-') // jud,leg,orig,proj,dout
		$escopo = substr($opt,7); // vai comparar com $parte2escopo
	elseif (!$flag)
		$xmlFile =$opt;
}

print "\n-- -- processando com draftmode=$draftmode, escopo=$escopo, file=$xmlFile --";


if ($draftmode)
	print "\nINSERT INTO lexml.draft (urn) VALUES";
else {
	print "\nINSERT INTO lexml.urn_detalhes (prefixo_id, datapub, numeracao) VALUES";

	// carga prefixos
	$prefixo2id=array();
	for($n=0, $h = fopen($prefixCSV, 'r'); !feof($h); $n++) {
		$row = fgetcsv($h,1000);
		if ($n && isset($row[0]) && $row[0])
			$prefixo2id[$row[1]]=$row[0];
	}
	fclose($h);
	print "\n -- $n linhas de CSV analisadas, num. prefixos = ".count(array_keys($prefixo2id)) ."\n";

	// carga autoridades
	$f = 'data/autoridades.csv';
	$t = array_map('str_getcsv', file($f));
	$thead = array_shift($t);
	foreach($t as $r) {
		$a = array_combine($thead,$r);   //cor,secao,retranca
		$parte2escopo['aut'][$a['autoridade']] = $a['escopo'];
	}

	// carga tipos
	$f = 'data/tipoDocumento.csv';
	$t = array_map('str_getcsv', file($f));
	$thead = array_shift($t);
	foreach($t as $r) {
		$a = array_combine($thead,$r);   //cor,secao,retranca
		$parte2escopo['tip'][$a['tipodocumento']] = $a['escopo'];
	}
}


// carga dados
$dom = new DOMDocument;
$dom->load($xmlFile);  // lento mas funciona (avaliar uso de ReadXML se virar gargalo)

$pending='';

$n=0;
$locs = $dom->getElementsByTagName('loc');
foreach ($locs as $loc) {
    $url = $loc->nodeValue;
    $urn = preg_replace('#^http://www.lexml.gov.br/urn/urn:lex:#s', '', $url);
    $virg = $n? ',': '';
    if ($draftmode)
    	print "$virg\n ('$urn')";
    else {
	list($local, $aut, $tipo, $etc) = explode(":",$urn);
	$data=$codigo='';
	if ($etc) 
		list($data, $codigo) = explode(";",$etc);
	$prefixo = "$local:$aut:$tipo";
	if (!$escopo || checkEscopo($escopo,$aut,$tipo)) {
		if (isset($prefixo2id[$prefixo]))
			print "$virg\n ({$prefixo2id[$prefixo]}, to_date('$data','YYYY-MM-DD'), '$codigo')";
		else {
			print "\n -- ERRO: prefixo desconhecido, '$prefixo'\n";
			$pending .= "$virg\n ('$urn','$data')";
		}
	}
    }
    $n++;
}
print ";  -- $n records \n";

$np = count($pending);
if ($np) 
	print "\n\n-------------\nINSERT INTO lexml.pending(urn,refdate) VALUES $pending; -- $np\n";

/////

function checkEscopo($e,$aut,$tipo) {
	global $parte2escopo;
	return (isset($parte2escopo['aut'][$aut]) && $parte2escopo['aut'][$aut]==$e) 
		|| 
		(isset($parte2escopo['tip'][$aut]) && $parte2escopo['tip'][$tipo]==$e)
	;
}

?>

