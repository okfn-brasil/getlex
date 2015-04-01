<?php
/**
 * Converte dados XML de uma carga de URNs do LexML, em dados SQL.
 * Opera em terminal, tipicamente com stdin em pipe. Exemplo:
 *  cat map1.xml | php parse2sql.php | psql -h localhost -p 5432 -U postgres
 *  cat map1.xml | php parse2sql.php | grep ERRO
 * @see    carga.php
 * @author https://github.com/ppKrauss/getlex
 */
ini_set("memory_limit","256M"); // ok para o DOM? prefixos ocupam ~250kb

// // BEGIN:CONFIGS // // //
$prefixCSV='data/urn_prefixos.csv';
print "\nINSERT INTO lexml.urn_detalhes (prefixo_id, datapub, numeracao) VALUES";
// // END:CONFIGS //

$xmlFile='php://stdin';
if (isset($argv[1])) $xmlFile =$argv[1];


// carga prefixos
$prefixo2id=array();
for($n=0, $h = fopen($prefixCSV, 'r'); !feof($h); $n++) {
	$row = fgetcsv($h,1000);
	if ($n && isset($row[0]) && $row[0])
		$prefixo2id[$row[1]]=$row[0];
}
fclose($h);
print "\n -- $n linhas de CSV analisadas, num. prefixos = ".count(array_keys($prefixo2id));
print "\n";


// carga dados
$dom = new DOMDocument;
$dom->load($xmlFile);  // lento mas funciona (avaliar uso de ReadXML se virar gargalo)

$n=0;
$locs = $dom->getElementsByTagName('loc');
foreach ($locs as $loc) {
    $url = $loc->nodeValue;
    $urn = preg_replace('#^http://www.lexml.gov.br/urn/urn:lex:#s', '', $url);
    list($local, $aut, $tipo, $etc) = explode(":",$urn);
    $data=$codigo='';
    if ($etc) 
	list($data, $codigo) = explode(";",$etc);
    $virg = $n? ',': '';
    $prefixo = "$local:$aut:$tipo";
    if (isset($prefixo2id[$prefixo]))
	print "$virg\n ({$prefixo2id[$prefixo]}, to_date('$data','YYYY-MM-DD'), '$codigo')";
    else
	print "\n -- ERRO: prefixo desconhecido, '$prefixo'\n";
    $n++;
}
print ";\n";
?>

