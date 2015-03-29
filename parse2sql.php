<?php
/**
 * Converte dados XML de uma carga de URNs do LexML, em dados SQL.
 * Opera em terminal, tipicamente com stdin em pipe. Exemplo:
 *  cat map1.xml | php parse2sql.php | psql -h localhost -p 5432 -U postgres
 *
 * @author https://github.com/ppKrauss/getlex
 */

$xmlFile='php://stdin';
if (isset($argv[1])) $xmlFile =$argv[1];

print "\nINSERT INTO lexml.urn_splitted (local, aut, tipo, ano, mes_dia, codigo) VALUES"; 
$dom = new DOMDocument;

$dom->load($xmlFile);
$locs = $dom->getElementsByTagName('loc');
$n=0;
foreach ($locs as $loc) {
    $url = $loc->nodeValue;
    $urn = preg_replace('#^http://www.lexml.gov.br/urn/urn:lex:#s', '', $url);
    list($local, $aut, $tipo, $etc) = explode(":",$urn);
    $ano=$mes_dia=$data=$codigo='';
    if ($etc) {
	list($data, $codigo) = explode(";",$etc);
	if (preg_match('/^(\d+)\-(.+$)/',$data,$m)) {
		$ano = $m[1];
		$mes_dia = $m[2];
	}
    }
    if (!$ano) $ano=0;
    $virg = $n? ',': '';
    print "$virg\n ('$local', '$aut', '$tipo', $ano, '$mes_dia', '$codigo')";
    $n++;
}
print ";\n";
?>

