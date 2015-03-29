--
-- Script previo para carga. Projeto https://github.com/ppKrauss/getlex
-- Versão beta
--
CREATE SCHEMA lexml;
 
CREATE TABLE lexml.grupo(
  id serial NOT NULL PRIMARY KEY, -- 
  use_local BOOLEAN NOT NULL,
  use_aut BOOLEAN NOT NULL,
  use_tipo BOOLEAN NOT NULL,
  use_ano BOOLEAN NOT NULL,
  is_legislacao NOT NULL,     -- do poder legislativo
  is_jurisprudencia NOT NULL, -- nao-estatutaria (do poder legislativo)
  is_proposicao NOT NULL,        -- projetos de lei
  regras xml -- descrição das regras de segmentação e critérios de curadoria.
);
 
CREATE TABLE lexml.urn_split(
   -- tabela das URNs explodidas
   id serial NOT NULL PRIMARY KEY, -- (REDUNDANTE) pode ser removido, pois o que vale é o ID na base LexML.
   LOCAL VARCHAR(100) NOT NULL, -- br
   aut VARCHAR(100) NOT NULL, -- autoridade ou federal/estadual/municipal
   tipo VARCHAR(100) NOT NULL, -- lei, decreto, "acordao;re", etc.
   ano SMALLINT NOT NULL,  
   mes_dia CHAR(5) NOT NULL, -- MM-DD
   codigo VARCHAR(100) NOT NULL, -- não precisa testar unicidade
   grupo_id INTEGER REFERENCES lexml.grupo(id),  -- id do grupo
   idg1 INTEGER,  -- GERADO POR ALGORITMO do grupo_id, PERSISTIR
   idg2 INTEGER,  -- GERADO POR ALGORITMO do grupo_id, PERSISTIR
   UNIQUE(LOCAL, aut, tipo, ano, mes_dia, codigo), -- validação
   UNIQUE(id1,id2)  -- validação
);
 
CREATE VIEW lexml.urn_join AS 
  SELECT *, LOCAL||':'||aut||':'||tipo||':'||ano||'-'||mes_dia||':'||codigo AS urn 
  FROM lexml.urn_split;
 
-- regra de definição do grupo1
SELECT * 
FROM lexml.urn_join
WHERE urn IN ('br:federal:constituicao:1988-10-05;1988', 'br:federal:lei:2002-01-10;10406', 'br:federal:decreto:1934-07-10;24643', 'br:federal:lei:1990-09-11;8078', 'br:federal:lei:1973-01-11;5869', 'br:federal:lei:1965-09-15;4771', 'br:federal:lei:1997-09-23;9503', 'br:federal:decreto.lei:1940-12-07;2848', 'br:federal:lei:1990-07-13;8069', 'br:federal:lei:2003-10-01;10741', 'br:federal:lei:1985-07-24;7347', 'br:federal:lei:2006-08-07;11340', 'br:federal:lei:2012-10-17;12727', 'br:federal:lei:1998-02-12;9605', 'br:federal:lei:2014-04-23;12965');
 
--- regras de definição para os grupos 2 a N
 
--- regras de difinição para "demais legislação"
 
--- regras de definição para "jurisprudência"
 
-- regras de definição para Proposições Legislativas
 
-- regras para overload demais (qq que não caiba na sua faixa prevista)

