<?php
/**
 * Cria os arquivos finais CSV do "projeto gelex" a partir da especificação SQL.
 *
 * @see php preparo.php
 * @author https://github.com/ppKrauss/getlex
 *
 */

// // BEGIN:CONFIGS // // //
$descrFile='datapackage.json';
// // END:CONFIGS //

set_time_limit(500);
$descr = json_decode( file_get_contents($descrFile), true );
print "\n---- Lendo $descrFile ----------\n\n";
foreach ($descr['resources'] as $r) if (isset($r['sql'])) {
	print "\n\t $r[path]";
	system("
		psql -h localhost -p 5432 -U postgres -c \"
		 COPY ({$r['sql']}) TO STDOUT WITH DELIMITER ',' CSV HEADER
		\" > {$r['path']}
	");
}
print "\n---- Fim (confira os arquivos gerados) ----\n";

?>

