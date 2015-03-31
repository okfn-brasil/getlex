-- -- -- -- -- -- -- -- --
-- Script prévio para carga. Projeto https://github.com/ppKrauss/getlex
-- Versão beta 
-- NOTA: grupo ainda fora de uso.
-- -- -- -- -- -- -- -- --

CREATE SCHEMA lexml;

CREATE TABLE lexml.grupo(
  --
  -- Agrupamentos de prefixos, para fins de curadoria e gestão
  --
  id serial NOT NULL PRIMARY KEY, -- 
  use_ano BOOLEAN NOT NULL,  -- sugere usar ano para split grupo
  escala smallint NOT NULL,  -- 1=federal, 2=estadual, 3=municipal, 11=eleitoral1, 12=eleitoral2, 21=hidrográfica1, ...
  is_legislacao BOOLEAN,     -- doc do poder legislativo
  is_jurisprudencia BOOLEAN, -- doc do poder judiciario
  is_proposicao BOOLEAN,     -- projeto de doc
  is_doutrina BOOLEAN,       -- doc (livro ou artigo) de doutrina
  maxlength_grupo integer,   -- máximo de items contados no grupo (se explodir, deixa de 
  regras xml -- descrição das regras de segmentação e critérios de curadoria.
);

CREATE TABLE lexml.urn_prefixos(
   --
   -- Prefixos das URNs LEX-BR, formando um conjunto de categorias.
   --
   id serial NOT NULL PRIMARY KEY,
   prefixo text NOT NULL, -- "jurisdição:autoridade:tipoMedida"
   grupo_id integer REFERENCES lexml.grupo (id),
   escopo char(3),
   kx_n_urns integer,     -- cache do número de URNs
   UNIQUE(prefixo)
);

CREATE TABLE lexml.urn_detalhes(
   --
   -- URNs explodidas em prefixo+detalhe (e data+numeracao)
   --
   prefixo_id integer REFERENCES lexml.urn_prefixos(id),
   datapub date NOT NULL,         -- data (YYYY-MM-DD) de assinatura ou de publicação
   numeracao VARCHAR(100) NOT NULL, -- final da URN, em geral o número ou código da norma
     -- tamanho máximo encontrado foi 55, mais usado 20, sendo ranges 3-5, 13-18, 18-22 tambem frequentes.
   grcnt_id INTEGER,  -- contador dentro do grupo, usado como id para compor a chave (grupo_id,grcnt_id).
   UNIQUE(prefixo_id,datapub,numeracao)  -- validação e indexação
   -- UNIQUE(thisref.urn_prefixos.grupo_id,grcnt_id)
);

CREATE VIEW lexml.urns AS
   --
   -- URNs expressas como string unica, e demais dados herdados das demais tabelas.
   --
  SELECT p.grupo_id, d.grcnt_id, p.prefixo||':'||d.datapub||':'||d.numeracao as urn,  
         p.prefixo, p.escopo, d.datapub, d.numeracao, p.id as prefix_id, p.kx_n_urns 
  FROM lexml.urn_prefixos p  INNER JOIN lexml.urn_detalhes d ON p.id=d.prefixo_id;
  



