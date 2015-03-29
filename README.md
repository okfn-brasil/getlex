getlex
------
Recupera todas as URNs LEX do [www.lexml.gov.br](http://www.lexml.gov.br/) e permite análise estatística em base SQL local.

# Apresentação #
O retorno das buscas realizadas pelo  [Portal LexML](http://www.lexml.gov.br/) nos fornece uma "ficha catalográfica" da norma desejada. Por exemplo, o *Código das Águas* é apresentado em 

> http://www.lexml.gov.br/urn/urn:lex:br:federal:decreto:1934-07-10;24643

onde, junstamente o trecho que se vê na própria URL, `urn:lex:br:federal:decreto:1934-07-10;24643`, é a chamada [URN LEX](https://pt.wikipedia.org/wiki/Lex_(URN)) da norma. A URN LEX identifica sem risco de ambiguidades, e de forma transparente, qualquer norma brasileira, dos poderes Legislativo (leis, resoluções da Câmara, etc.), Jurídico (acórdons e jurisprudências) ou Executivo (decretos, portarias, etc.), em todas as suas esferas, Federal, Estadual e Municipal.

O Portal LexML é apenas um dos recursos disponibilizados pelo [Projeto LexML](http://projeto.lexml.gov.br/): os padrões LexML garantem a interoperabilidade de dados legislativos e jurídicos entre todos os tipos de sistemas informatizados, inclusive aqueles voltados para as iniciativas de  [Dados Abertos](http://dados.gov.br/dados-abertos/).

A URN LEX é central a todas as iniciativas de transparência e interoperabilidade. A listagem de todas as URNs (mais de 3 milhões) pode ser útil para quem deseja realizar estatísiticas ou implementar localmente algum recurso LexML.

## Disponibilidade dos dados
Como são muitos dados, a totalidade das URN LEX não podem ser disponibilizadas como simples download, pois comprometeria a banda do servidor Lexml.gov.br. O download completo das URNs é oferecido em blocos de 50mil items, e a lista completa dos blocos **[deve ser solicitada pelo formulário de contato do LexML](http://projeto.lexml.gov.br/contact-info)**, sendo em  seguida enviada por e-mail, onde estará disível o arquivo completo `sitemap_index.xml`.

Recomenda-se baixar os dados (rodar `carga.php`) apenas em horário de menor demanda (madrugada de Brasília).

# Objetivos#
O presente projeto tem por finalidade:
 * obter uma cópia completa de todas as URNs LEX do [lexml.gov.br](http://lexml.gov.br)
 * oferecer um modelo de dados SQL util para diversos usos, incluindo espelhamento, análises estatísticas e interpretação de identificadores curtos (*projeto shortlex* ainda em construção
 * servir de apoio a atividades de "curadoria das URNs", segmentando o universo em categorias e grupos de relevância.
 * oferecer relatórios (contagens e estatísticas) em formato padronizado, em conformidade com as recomendações do [Data Packaged Core Datasets](https://github.com/datasets).

# Estrutura#
Ver `ini.sql`, testado em [PostgreSQL v9+](http://www.postgresql.org/). Resumo:

```sql
CREATE TABLE lexml.grupo(  -- agrupamentos de URNs
  id serial NOT NULL PRIMARY KEY, -- 
  use_local BOOLEAN NOT NULL,
  ...
  is_proposicao not null, -- é proposição
  is_legislacao not null, -- não é jurisprudencia nem proposição
  regras xml -- descrição das regras de segmentação e critérios de curadoria.
);
CREATE TABLE lexml.urn_split( -- URNs explodidas
   local VARCHAR(100) NOT NULL,   -- parte inicial da URN
   aut VARCHAR(100) NOT NULL,     -- autoridade 
   tipo VARCHAR(100) NOT NULL,    -- exs. lei, decreto, etc.
   ano SMALLINT NOT NULL,         -- ano da data de publicação
   mes_dia CHAR(5) NOT NULL,      -- MM-DD da data de publicação
   codigo VARCHAR(100) NOT NULL,  -- parte final da URN
   grupo_id INTEGER REFERENCES lexml.grupo(id),  -- id do grupo
   ...
   UNIQUE(LOCAL, aut, tipo, ano, mes_dia, codigo), -- validação
);
CREATE VIEW lexml.urn_join AS 
  SELECT *, local||':'||aut||':'||tipo||':'||ano||'-'||mes_dia||':'||codigo AS urn 
  FROM lexml.urn_split;
```

## Exemplos de consulta SQL

Contagem das URNs por *local/autoridade/tipo*:
```sql
WITH tall AS (
  SELECT local, aut, tipo, COUNT(*) AS n 
  FROM lexml.urn_split 
  GROUP BY LOCAL,aut,tipo
) SELECT local, aut, COUNT(*) AS n_tipos, SUM(n) AS n_urns 
  FROM tall 
  GROUP BY local,aut;
```

Contagem das URNs por tamanho em grupos *local/autoridade/tipo*:
```sql
  SELECT LENGTH(local||aut||tipo) AS len, COUNT(*) AS n
  FROM lexml.urn_split
  GROUP BY 1
  ORDER BY 2 DESC;
```

Contagem das URNs por tamanho em grupos *local/autoridade*:
```sql
  SELECT LENGTH(local||aut) AS len, COUNT(*) AS n
  FROM lexml.urn_split
  GROUP BY 1
  ORDER BY 2 DESC;
```

