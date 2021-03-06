<?php
/**
 * Algoritmos ilustrativos da conversão de URN canônica para URN curta.
 * @see https://github.com/okfn-brasil/getlex
 */

$debug = 0;

$jurPrefixes = [ // requer ano para delimitar intervalo de validade da UF
	'br'=>'br0',
	'br;sao.paulo'=>'brp'
];

$hashFrom = [
	'autoridade:br0'=>'sha1-t6',  // falta usar dado da versão-escopo... cria 36 possibilidades a cada autoridade, precisa defaults.
	'autoridade:brp'=>'crc32b',
];

$exemplos = [
	'urn:lex:br;sao.paulo;sao.paulo:municipal:lei:2016-07-15;16489',
	'urn:lex:br;sao.paulo;sao.paulo:municipal;secretaria.especial.participacao.parceria:convenio:2006-06-20;6',
	'urn:lex:br;sao.paulo;sao.paulo:municipal:lei:1992-07-25;11228',
	'urn:lex:br;sao.paulo;sao.paulo:imprensa.nacional:publicacao.oficial;diario.oficial.municipio;materia:2001-10-10;seq-dlivre-21',
	'urn:lex:br:federal:lei:2014-04-23;12965',
	'urn:lex:br:federal:lei:2014;123',
];


foreach ($exemplos as $e) {
	print "\n\n--- Decompondo $e\n";
	$p = explode(':',$e); // partes
	if ($p[0]=='urn') array_shift($p);
	if ($p[0]=='lex') array_shift($p);
	if ($debug) print "\n\t".join("\n\t",$p);
	$jurPts = explode(';', array_shift($p) );
	if (isset($jurPts[0])) 
		$jurPrefix = array_shift($jurPts); // br
	else
		die("\nERRO-1: URN sem jurisdição");
	if (isset($jurPts[0])) $jurPrefix .= ';'.array_shift($jurPts); // sp
	$jurRest = isset($jurPts[0])? array_shift($jurPts): ''; // sao.paulo
	if (isset($jurPrefixes[$jurPrefix])) 
		$jurPrefix=$jurPrefixes[$jurPrefix];
	else
		die("\nERRO-2: cache incompleto ou '$jurPrefix' inválido");

	if (isset($p[0])) $autoridade = array_shift($p);
	else
		die("\nERRO-3: autoridade ausente");

	if (isset($p[0])) $tipo = array_shift($p);
	else
		die("\nERRO-4: tipo ausente");

	if (isset($p[0])) $descritor = array_shift($p);
	else
		die("\nERRO-5: descritor ausente");
	if (preg_match('/^(\d+)(?:\-(\d+))?(?:\-(\d+))?;(.+)$/',$descritor,$m)) {
		$ano = (int) $m[1];
		$mes = $m[2]? $m[2]: '00';
		$dia = $m[3]? $m[3]: '00';
		$code = $m[4];
		if ($ano < 1600 || $ano>2017) 
			die("\nERRO-6: descritor com ano ($ano) inválido.");
		$ano -= 1600;
		$anoMes = base_convert("$ano$mes",10,36);
	} else
		die("\nERRO-7: descritor com sintaxe inválida.");

	// falta incluir a versão e decidir por versão+1 se $autoridade ou $tipo forem compostos.
	$h1 = $hashFrom["autoridade:$jurPrefix"];
	$trunc = false;
	if (preg_match('/^([a-z0-9]+)\-t(\d+)$/i',$h1,$m)) {
		$trunc=(int) $m[2];
		$h1 = $m[1];
	}
	$hash1 = hash($h1,$jurRest.$autoridade.$tipo);  // falta usar ano como seletor.
	if ($trunc>1) $hash1 = substr($hash1,0,$trunc);

	$versao=1;
	if (ctype_digit($code))
		$hash2 = base_convert("$dia$code",10,36);
	else {
		$versao=0;
		$hash2 = hash('crc32b',"$dia$code");
	}
	$escopo=0; // falta escopo conforme auoridade e tipo
	$VerSco = base_convert((string) $versao+6*$escopo, 10, 36);
	$urnCurta = strtoupper($jurPrefix.$VerSco.$anoMes.$hash1.$hash2);

	print "\n URN curta = $urnCurta = $jurPrefix.$VerSco.$anoMes.$hash1.$hash2";
}
?>



