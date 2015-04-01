<?php
/**
 * Faz carga de dados do LexML. $xmlFile indica fontes e parse2sql.php faz SQL de  fonte carregada.
 * Opera em dois modos, direto ou por carga XML temporária.
 *
 * @note   a coordenação do LexML fornece por email um arquivo sitemap_index.xml para listar os blocos.
 * @see    php carga.php direto
 * @see    php carga.php xml
 * @author https://github.com/ppKrauss/getlex
 * @dependences parse2sql.php
 *
 * INFO: 
 *   taxa de transferência com banda boa, ~8 segundos/bloco. O que mata são os 6 a 8 minutos/bloco de INSERT.
 */

// // BEGIN:CONFIGS // // //
$xmlFile='data/sitemap_index.xml';
$carregadosDir = 'carregados';
$gerarMais = 150;
$baiXML = false;
// // END:CONFIGS //

if (isset($argv[1]) && $argv[1]!='direto') 
	$baiXML = true;

set_time_limit($gerarMais*200);
$dom = new DOMDocument;
$dom->load($xmlFile);
$locs = $dom->getElementsByTagName('loc');
$n=$nmais = 1;
$now = date('Y-m-d h:i:s');
print "\n----------- $now -------------------";
foreach ($locs as $loc) {
	$url = $loc->nodeValue;  // ex. http://www.lexml.gov.br/map22.xml
	$file = preg_replace('#^http://www.lexml.gov.br/([^\.]+).+$#',$carregadosDir.'/$1',$url);
	$fileCtrl = "$file.txt";
	$fileXML = "$file.xml";
	print "\n -- $n -- ";
	if (file_exists($fileCtrl) || ($baiXML && file_exists($fileCtrl))) 
		print "JA FOI $file";
	elseif ($baiXML && file_exists($fileXML)) {
		print "usando $fileXML... ";
		$DO = "php parse2sql.php $fileXML | psql -h localhost -p 5432 -U postgres";
		system($DO); // no escuro... nao verifica retorno
		file_put_contents($fileCtrl,'');
		unlink($fileXML);
		$nmais++;
	} else {
		if ($baiXML) 
			$DO = "wget -qO- $url > $fileXML";
		else
			$DO = "wget -qO- $url |php parse2sql.php | psql -h localhost -p 5432 -U postgres";
		print " OK $nmais/$gerarMais fazendo $file\n\t$DO";
		system($DO); // no escuro... nao verifica retorno
		if (!$baiXML) 
			file_put_contents($fileCtrl,'');
		$nmais++;
	}
	if ($nmais>=$gerarMais){
		print ("\n OK FINALIZADA A META DE $gerarMais\n");
		break;
	}
	$n++;
}
$now = date('Y-m-d h:i:s');
print "\n----------- $now -------------------\n\n";

?>

