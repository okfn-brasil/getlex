getlex
------
Recupera todas as URNs LEX do [www.lexml.gov.br](http://www.lexml.gov.br/) e permite análise estatística em base SQL local.

# Apresentação #
O retorno das buscas realizadas pelo  [Portal LexML](http://www.lexml.gov.br/) nos fornece uma "ficha catalográfica" da norma desejada. Por exemplo, o *Código das Águas* é apresentado em 

> http://www.lexml.gov.br/urn/urn:lex:br:federal:decreto:1934-07-10;24643

onde, junstamente o trecho que se vê na própria URL, `urn:lex:br:federal:decreto:1934-07-10;24643`, é a chamada [URN LEX](https://pt.wikipedia.org/wiki/Lex_(URN)) da norma. A URN LEX identifica sem risco de ambiguidades, e de forma transparente, qualquer norma brasileira, dos poderes Legislativo (leis, resoluções da Câmara, etc.), Jurídico (acórdons e jurisprudências) ou Executivo (decretos, portarias, etc.), em todas as suas esferas, Federal, Estadual e Municipal.

O Portal LexML é apenas um dos recursos disponibilizados pelo [Projeto LexML](http://projeto.lexml.gov.br/): os padrões LexML garantem a interoperabilidade de dados legislativos e jurídicos entre todos os tipos de sistemas informatizados, inclusive aqueles voltados para as iniciativas de  [Dados Abertos](http://dados.gov.br/dados-abertos/).

A URN LEX (ver [RFC draft](https://datatracker.ietf.org/doc/draft-spinosa-urn-lex/)) é central a todas as iniciativas de transparência e interoperabilidade. A listagem de todas as URNs (mais de 3 milhões) pode ser útil para quem deseja realizar estatísiticas ou implementar localmente algum recurso LexML.

## Dados oferecidos pelo projeto
Os milhões de URNs LEX podem ser conseguidos seguindo a instalação e procedimentos indicados. Os relatórios e sumarização dos dados, por outro lado, são disponibilizados diretamente na pasta `/data` como dados abertos,

**Dados-fonte**:

 * [urn_prefixos.csv](https://github.com/ppKrauss/getlex/blob/master/data/urn_prefixos.csv): dados de carga, contém os IDs padronizados dos prefixos das URNs.
 * [grupo1.csv](https://github.com/ppKrauss/getlex/blob/master/data/grupo1.csv): listagem das normas "top 35" do Brasil (PARTICIPE! [Edite a planilha e avise aqui no new issues](https://docs.google.com/spreadsheets/d/1_8pmaPkmnPc-EnKFCPbT_YkiaON1nXZFO2i3ITrqog8/edit?usp=sharing)).

**Relatórios** (obtidos do estado atual da base):
 * [autoridades.csv](https://github.com/ppKrauss/getlex/blob/master/data/autoridades.csv): listagem corrente das *autoridades* que contribuem para o acervo LexML; em particular normas estatutárias (dos poderes Legislativo e Executivo) são responsabilidade das assim-chamadas "autoridades emitentes de normas".
 * [jurisdicoes.csv](https://github.com/ppKrauss/getlex/blob/master/data/jurisdicoes.csv): listagem corrente das jurisdições (áreas) com *autoridades* que estão presentes no acervo LexML.
 * [tipoDocumento.csv](https://github.com/ppKrauss/getlex/blob/master/data/tipoDocumento.csv): listagem corrente dos *tipos de documento* (acordões, leis, decretos, livros, artigos, etc.) presentes no acervo LexML.

# Objetivos#
O presente projeto tem por finalidade:
 * obter uma cópia completa de todas as URNs LEX do [lexml.gov.br](http://lexml.gov.br)
 * oferecer um modelo de dados SQL util para diversos usos, incluindo espelhamento, análises estatísticas e interpretação de identificadores curtos (*projeto shortlex* ainda em construção
 * servir de apoio a atividades de "curadoria das URNs", segmentando o universo em categorias e grupos de relevância.
 * oferecer relatórios (contagens e estatísticas) em formato padronizado, em conformidade com as recomendações do [Data Packaged Core Datasets](https://github.com/datasets).

# Estrutura#
Ver `ini.sql`, testado em [PostgreSQL v9+](http://www.postgresql.org/). Resumo:

```sql
CREATE TABLE lexml.urn_prefixos(
  -- Prefixos das URNs LEX-BR, formando um conjunto de categorias.
  id serial NOT NULL PRIMARY KEY,
  prefixo text NOT NULL, -- "jurisdição:autoridade:tipoMedida"
  escopo char(3), -- prj=proposições, jus=justiça, leg=legislativo/exec, bib=bibliotecas
  ...
);
CREATE TABLE lexml.urn_detalhes(
  -- URNs explodidas em prefixo+detalhe (e data+numeracao)
  prefixo_id integer REFERENCES lexml.urn_prefixos(id),
  datapub date NOT NULL,           -- data (YYYY-MM-DD) de assinatura ou de publicação
  numeracao VARCHAR(100) NOT NULL, -- final da URN, em geral o número ou código da norma
  ...
  UNIQUE(prefixo_id,datapub,numeracao)  -- validação e indexação
);
CREATE VIEW lexml.urns AS
  -- URNs expressas como string unica, e demais dados herdados das demais tabelas.
  SELECT p.prefixo||':'||d.datapub||':'||d.numeracao as urn, ... 
  FROM lexml.urn_prefixos p  INNER JOIN lexml.urn_detalhes d ON p.id=d.prefixo_id;
```

## Instalação
Depois do `git clone` temos uma pasta `/getlex`, a partir da qual podemos rodar o script de carga:
```shel
  psql -h localhost -p 5432 -U postgres < ini.sql
  psql -h localhost -p 5432 -U postgres -c "
    COPY lexml.urn_prefixos(id,prefixo,kx_n_urns,grupo_id,escopo) 
    FROM '/yourLocalPath/getlex/data/urn_prefixos.csv' DELIMITER ',' CSV HEADER
  "
  php carga.php xml
  php carga.php
```

O primeiro comando `psql` cria as tabelas SQL do esquema lexml, apresentado na seção anterior. Em seguida deve-se efetuar a carga do arquivo *urn_prefixos.csv* para atualização dos prefixos. {FALTA revisar algoritmo de inclusão de novos prefixos}

O primeiro comando `php carga` traz os arquivos XML em meia hora ou um pouco mais se a rede estiver lenta; o segundo faz de fato a carga no banco de dados. Fazer a carga em duas etapas gasta temporariamente mais disco, mas em geral é mais seguro que opção `php carga.php direto`, tendo em vista que a demora maior é de CPU para processar os comandos `INSERT` no SQL, tratado em blocos para evitar inconsistências em caso de falha.

Se a base já existe, e a intenção é atualizar, deve-se conferir se a aplicação (ex. *shortlex*) criou seu próprio script de manutenção, e portanto deve-se tomar muito coidado antes de realizar no SQL o comando `DROP SCHEMA lexml CASCADE`, `DELETE` ou similares.

### Disponibilidade e carga na base de dados
Como são muitos dados, a totalidade das URN LEX não pode ser disponibilizada como simples *download*, pois comprometeria a banda do servidor `Lexml.gov.br`. O *download* completo das URNs é oferecido em blocos de 50 mil items, e a lista completa dos blocos é fornecida por um arquivo XML, disponível em [lexml.gov.br/sitemap_index.xml](http://www.lexml.gov.br/sitemap_index.xml) (ou outro local indicado pelo [dadosabertos.senado.leg.br](http://dadosabertos.senado.leg.br/). Ao rodar o script `carga.php` esses dados serão baixados (sugere-se realizar esse procedimento fora de horário comercial para não sobrecarregar os servidores).

## Preparação dos arquivos CSV
A geração de [arquivos CSV-dataset padrão](https://github.com/datasets), no contexto *getlex*, é realizada a partir de uma base SQL, local ou do servidor. A tabela que origina o `urn_prefixos.csv`, em particular, vem do servidor central LexML, seus IDs não mudam, as atualizações apenas acrescentam novos IDs. O padrão "OKFN core datasets" adotado também exige que todos os arquivos CSV que constam na pasta de dados `/data` sejam descritos em `datapackage.json`.

Os arquivos de contagem e relatórios oferecidos neste repositório (*getlex*) precisam ser atualizados cada vez que a base LexML é atualizada. Eles foram gerados com os comandos SQL do script `preparo.php`.

Exemplo: o arquivo `foo.csv` poderia ser gerado pelo seguinte comando de shell,
```shell
psql -h localhost -p 5432 -U postgres -c "
 COPY (SELECT a,b FROM foo) TO STDOUT WITH DELIMITER ',' CSV HEADER
"> data/foo.csv
```
e esse preparo difere dos demais apenas pelo trecho `SELECT a,b FROM foo`. Desse modo o script  `preparo.php` é apenas um script que armazena as especificações SQL de cada preparo, e executa diversas vezes o template de comando shell, uma para cada preparo.
<!--
ou ainda com `\copy (...) TO '/tmp/test.csv' WITH ...` (o PHP também oferece [pg_copy_to](http://php.net/manual/en/function.pg-copy-to.php)), mas a chamada `psql` no termial (*client*) das versões novas (v9+) vem munidas do STDIN/STDOUT.
-->

----

## Hashes da URN compacta

Compactar requer um algoritmo para a redução da URN LEX: sem o uso de um identificador opaco, lançando mão de funções de hashing (por exemplo SHA-1, Murmur2, ou CRC32) com baixa taxa de colisão nas amostragens. 

No levantamento dos diversos tipos de aplicação (ex. Códigos QR), notou-se que os requisitos são praticamente os mesmos. Há que se usar representação final do identificador em  base36 ao invés de outra mais compacta.

Como as estratégias de hashing estão sempre sujeitas a apresentar colisões (duas URNs com mesmo hash), algumas elementos adicionais podem ser concatenados ao hash para formar uma URN  (ver elementos na [sintáxe da URN LEX-BR](http://okfn-brasil.github.io/getlex/docs/LexMLbr-Parte2-URN-AnexoA.xhtml)) compacta unívoca:


* Metadados compactos:

  * ano-mês: podem ser condensados em 3 dígitos base36.

  * prefixo da `jurisdicao`: em função do ano a jurisdição pode fazer uso das [siglas padronizadas de UF](https://github.com/okfn-brasil/getlex/blob/master/data/ISO-3166-2-BR.csv). Um só dígito base36 é necessário a cada ano. Na sintaxe, o que sobra depois desse prefixo é `jurisdicao-sufixo`.

  * versão-escopo: dado suplementar para controlar modo de hashing, garantindo ausência de colisões nos hashes.

* Composição de duas hashes:

  * `jurisdicao-sufixo` , `autoridade` e `tipo-documento` formam a entrada para o *primeiro hash*. Estatisticas por escopo, ano e prefixo de jurisdição.

  * `descritor` (sem ano e mês) forma a entrada para o *segundo hash*. Estatisticas por escopo.

As estatísticas de colisão permitem a escolha e confirmação de validade dos hashes. A estratégia é garantir que as colisões sejam mínimas e, sobretudo, que a resolução das colisões (sem demanda por performance) em [Open Adressing](https://www.wikidata.org/wiki/Q7096315) seja necessária apenas nos períodos em que o identificador oficial e o local ficam fora de sincronia. A modelagem dos sistemas de identificação e estimativas de tempo, seguem o [_modelo de referência_ 2016](https://doi.org/10.5281/zenodo.159004).

-----

### APOIO

* [site deste projeto](http://okfn-brasil.github.io/getlex)
* ...


