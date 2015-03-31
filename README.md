getlex
------
Recupera todas as URNs LEX do [www.lexml.gov.br](http://www.lexml.gov.br/) e permite análise estatística em base SQL local.

# Apresentação #
O retorno das buscas realizadas pelo  [Portal LexML](http://www.lexml.gov.br/) nos fornece uma "ficha catalográfica" da norma desejada. Por exemplo, o *Código das Águas* é apresentado em 

> http://www.lexml.gov.br/urn/urn:lex:br:federal:decreto:1934-07-10;24643

onde, junstamente o trecho que se vê na própria URL, `urn:lex:br:federal:decreto:1934-07-10;24643`, é a chamada [URN LEX](https://pt.wikipedia.org/wiki/Lex_(URN)) da norma. A URN LEX identifica sem risco de ambiguidades, e de forma transparente, qualquer norma brasileira, dos poderes Legislativo (leis, resoluções da Câmara, etc.), Jurídico (acórdons e jurisprudências) ou Executivo (decretos, portarias, etc.), em todas as suas esferas, Federal, Estadual e Municipal.

O Portal LexML é apenas um dos recursos disponibilizados pelo [Projeto LexML](http://projeto.lexml.gov.br/): os padrões LexML garantem a interoperabilidade de dados legislativos e jurídicos entre todos os tipos de sistemas informatizados, inclusive aqueles voltados para as iniciativas de  [Dados Abertos](http://dados.gov.br/dados-abertos/).

A URN LEX é central a todas as iniciativas de transparência e interoperabilidade. A listagem de todas as URNs (mais de 3 milhões) pode ser útil para quem deseja realizar estatísiticas ou implementar localmente algum recurso LexML.

## Dados oferecidos pelo projeto
Os milhões de URNs LEX podem ser conseguidos seguindo a instalação e procedimentos indicados. Os relatórios e sumarização dos dados, por outro lado, são disponibilizados diretamente como dados abertos:

 * [autoridades.csv](https://github.com/ppKrauss/getlex/blob/master/data/autoridades.csv): listagem corrente das autoridades que postaram seus documentos no LexML.
 * [jurisdicoes.csv](https://github.com/ppKrauss/getlex/blob/master/data/jurisdicoes.csv): listagem corrente das jurisdições (áreas) com autoridades que postaram seus documentos no LexML.
 * ...
 * [urn_prefixos.csv](https://github.com/ppKrauss/getlex/blob/master/data/urn_prefixos.csv): dados de carga, contém os IDs padronizados dos prefixos das URNs. 

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
   --
   -- Prefixos das URNs LEX-BR, formando um conjunto de categorias.
   --
   id serial NOT NULL PRIMARY KEY,
   prefixo text NOT NULL, -- "jurisdição:autoridade:tipoMedida"
   escopo char(3),        -- prj=proposições, jus=justiça, leg=legislativo/executivo, bib=bibliotecas
   ...
);

CREATE TABLE lexml.urn_detalhes(
   --
   -- URNs explodidas em prefixo+detalhe (e data+numeracao)
   --
   prefixo_id integer REFERENCES lexml.urn_prefixos(id),
   datapub date NOT NULL,         -- data (YYYY-MM-DD) de assinatura ou de publicação
   numeracao VARCHAR(100) NOT NULL, -- final da URN, em geral o número ou código da norma
   ...
   UNIQUE(prefixo_id,datapub,numeracao)  -- validação e indexação
);

CREATE VIEW lexml.urns AS
   --
   -- URNs expressas como string unica, e demais dados herdados das demais tabelas.
   --
  SELECT p.grupo_id, d.grcnt_id, p.prefixo||':'||d.datapub||':'||d.numeracao as urn, ... 
  FROM lexml.urn_prefixos p  INNER JOIN lexml.urn_detalhes d ON p.id=d.prefixo_id;
```

## Instalação
Depois do `git clone` temos uma pasta `/getlex`, a partir da qual podemos rodar o script de carga:
```shel
  psql -h localhost -p 5432 -U postgres < ini.sql
  psql -h localhost -p 5432 -U postgres -c "
    COPY lexml.urn_prefixos FROM '/yourLocalPath/getlex/data/urn_prefixos.csv' DELIMITER ',' CSV HEADER
  "
  php carga.php xml
  php carga.php
```
O primeiro comando iniciado por `psql` cria as tabelas SQL do esquema lexml, apresentado na seção anterior. Em seguida (segundo  `psql`) deve-se efetuar a carga do 


O primeiro comando `php carga` traz os arquivos XML em meia hora ou um pouco mais se a rede estiver lenta; o segundo faz de fato a carga no banco de dados. Fazer a carga em duas etapas gasta temporariamente mais disco, mas em geral é mais seguro que opção `php carga.php direto`, tendo em vista que a demora maior é de CPU para processar os comandos `INSERT` no SQL, tratado em blocos para evitar inconsistências em caso de falha.

Se a base já existe, e a intenção é atualizar, deve-se conferir se a aplicação (ex. *shortlex*) criou seu próprio script de manutenção, e portanto deve-se tomar muito coidado antes de realizar no SQL o comando `DROP SCHEMA lexml CASCADE`, `DELETE` ou similares.

### Disponibilidade e carga na base de dados
Como são muitos dados, a totalidade das URN LEX não pode ser disponibilizada como simples *download*, pois comprometeria a banda do servidor `Lexml.gov.br`. O *download* completo das URNs é oferecido em blocos de 50 mil items, e a lista completa dos blocos **[deve ser solicitada pelo formulário de contato do LexML](http://projeto.lexml.gov.br/contact-info)**, sendo em  seguida enviada por e-mail, onde estará disível o arquivo completo `sitemap_index.xml`.

Recomenda-se baixar os dados (rodar `carga.php`) apenas em horário de menor demanda (madrugada de Brasília).

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


