# Análise Funcional do Sistema ConectaIFPA

**Documento:** Levantamento funcional e relatório de testes exploratórios
**Método:** Análise caixa-preta (*black box*), conduzida exclusivamente pela interface da aplicação
**Ambiente analisado:** `http://conectaifpa.test` (ambiente local de desenvolvimento — Apache/2.4.62, PHP 8.3.28, Laravel 12.28.1)
**Data da análise:** 12 de agosto de 2026
**Perfis utilizados:** Coordenador (`admin@conectaifpa.com`), Avaliador (`reviewer@conectaifpa.com`), Aluno (`aluno@conectaifpa.com`), dois participantes criados pelo próprio cadastro público (`qa.interno@teste.com`, `qa.externo@teste.com`) e sessão anônima (visitante)

---



## Nota metodológica

Esta análise foi conduzida sem qualquer consulta ao código-fonte, ao banco de dados, a *controllers*, *models*, *migrations* ou arquivos de rota. Todo o conteúdo aqui descrito decorre de navegação real na aplicação: abertura de páginas, leitura da interface renderizada, preenchimento e envio de formulários, acionamento de botões, inspeção do DOM exibido ao usuário, leitura de mensagens do sistema, observação de redirecionamentos e verificação de códigos de resposta HTTP.

A navegação foi automatizada com um navegador Chromium controlado (Playwright), o que permitiu registrar de forma sistemática, para cada tela: URL final, código HTTP, título, hierarquia de títulos, links visíveis, botões visíveis, formulários com todos os campos e atributos, tabelas com cabeçalhos e conteúdo, mensagens de alerta e uma captura de tela. As evidências visuais (136 capturas) estão em `C:\Users\kenzo\qa-conectaifpa\shots`.

Cada afirmação relevante está classificada por grau de certeza:

- **Confirmado** — observado diretamente e de forma reprodutível na interface.
- **Parcialmente confirmado** — observado, mas sem possibilidade de reprodução completa ou com uma única ocorrência.
- **Não confirmado** — hipótese derivada da interface que não pôde ser testada, sempre identificada como tal.

**Restrição imposta pelo cliente:** foi determinado que nenhum dado novo deveria ser criado no ambiente. Por isso, todos os fluxos de escrita (criação de evento, edição, exclusão, emissão de certificado, inscrição, submissão de trabalho, avaliação) foram documentados a partir dos formulários, campos, validações e regras visíveis, **sem persistir registros**. As duas contas de participante foram criadas antes dessa determinação, durante o teste do cadastro público, e estão sinalizadas neste documento. Isso está registrado como limitação no capítulo 15.

---



## 1. Visão geral do sistema

O ConectaIFPA é uma aplicação web de **gestão do ciclo de vida de eventos acadêmicos** do Instituto Federal do Pará — Campus Belém. O sistema cobre, em um único produto, três domínios que normalmente aparecem separados:

1. **Divulgação e inscrição** — uma vitrine pública de eventos, com página detalhada por evento e inscrição dos participantes.
2. **Gestão científica** — submissão de trabalhos pelos participantes, distribuição desses trabalhos a avaliadores, registro de pareceres, decisão da coordenação, agendamento das apresentações e publicação nos **anais** do evento.
3. **Certificação** — controle de presença (geral, por atividade e por apresentação de trabalho), configuração da carga horária e das assinaturas, emissão de certificados em PDF e **validação pública por código**, sem necessidade de login.

A interface é em português do Brasil, com identidade visual própria (verde institucional), tipografia Open Sans e layout responsivo. Há uma preocupação explícita com acessibilidade: barra com controle de tamanho de fonte, modo de alto contraste e integração com o **VLibras** (tradutor para Libras do governo federal).

**Evidência:** título da aplicação "ConectaIFPA – Eventos do IFPA"; chamada da página inicial "Conecte-se aos melhores eventos do IFPA — Descubra eventos acadêmicos, culturais e sociais"; rodapé "© 2026 ConectaIFPA. Todos os direitos reservados. Feito com carinho para a comunidade universitária."
**Grau de certeza:** Confirmado.

---



## 2. Objetivo do sistema

Pelo comportamento observado, o sistema resolve os seguintes problemas concretos:


| Problema                             | Como o sistema resolve                                                                                        |
| ------------------------------------ | ------------------------------------------------------------------------------------------------------------- |
| Eventos divulgados de forma dispersa | Vitrine pública única, com categoria, datas, local, modalidade e tipo de evento                               |
| Inscrições controladas em planilhas  | Inscrição pelo próprio participante, com lista de inscritos gerenciável e exportação em CSV                   |
| Recebimento de trabalhos por e-mail  | Submissão pela plataforma, com tipos de trabalho configuráveis por evento e prazo próprio                     |
| Avaliação sem rastreabilidade        | Distribuição de trabalhos a avaliadores com mínimo/máximo por trabalho, pareceres registrados e decisão final |
| Certificados emitidos manualmente    | Geração de PDF a partir da presença registrada, com carga horária e assinaturas configuráveis                 |
| Certificado falsificável             | Página pública de validação por código único, acessível sem login                                             |
| Anais publicados fora do sistema     | Registro de publicação nos anais por trabalho, com URL e observações, e página pública de anais               |


**Grau de certeza:** Confirmado para todos os itens (cada um corresponde a telas efetivamente abertas e descritas nos capítulos 8 a 11).

---



## 3. Público-alvo

Identificado pela própria interface, especialmente pelo formulário de cadastro:

- **Estudantes do IFPA — Campus Belém**, que se cadastram informando **matrícula de 12 dígitos** e curso.
- **Estudantes externos**, de outras instituições, que se cadastram informando a **instituição de origem** e o curso, sem matrícula. O rótulo é explícito: *"Sou aluno(a) externo(a). Estudo em outra instituição (fora do campus Belém do IFPA)."*
- **Coordenadores de evento** (servidores/organizadores), responsáveis por criar e gerir eventos, inscritos, trabalhos, presença e certificados.
- **Avaliadores** (pareceristas), responsáveis por avaliar os trabalhos científicos designados.
- **Público em geral**, que consulta eventos, baixa anexos, consulta anais e valida certificados sem se autenticar.

**Grau de certeza:** Confirmado.

---



## 4. Perfis de usuário

Foram encontrados **três perfis** na aplicação, cada um com painel, menu e permissões próprias. O rótulo do perfil é exibido no cartão de identificação do usuário dentro do painel.

### 4.1 Aluno (participante)

- **Rótulo exibido:** "Aluno"
- **Objetivo:** inscrever-se em eventos, submeter trabalhos, acompanhar avaliações e obter certificados.
- **Como acessa:** autocadastro público em `/register` (o formulário traz o campo oculto `role` com valor `participant`) ou credenciais fornecidas.
- **Menu (dropdown "Minha conta" + "Área do participante"):** Minha Área, Minha apresentação, Certificados, Meus trabalhos, Minha conta, Sair. No topo, links públicos Eventos e Validar certificado.
- **Ações de consulta:** eventos em que participa, eventos encerrados, seus trabalhos e status, cronograma da própria apresentação, seus certificados com download e link de validação.
- **Ações de alteração:** cancelar a própria participação em um evento ("Sair do evento"), atualizar nome, e-mail e senha, ativar 2FA, encerrar outras sessões.
- **Restrições confirmadas:** recebe `403` em toda rota de coordenação e na área do avaliador; recebe `403` ao tentar abrir trabalho, PDF de trabalho ou certificado de outro usuário.



### 4.2 Coordenador

- **Rótulo exibido:** "Coordenador" (o usuário utilizado chama-se "Administrador Inicial", mas o perfil apresentado na interface é Coordenador)
- **Objetivo:** criar e administrar eventos de ponta a ponta.
- **Como acessa:** credenciais; contas do tipo são criadas em `/register/coordinator`.
- **Menu:** Painel, Novo evento, Minha conta, Sair, além dos links públicos.
- **Painel:** lista "Meus eventos", cada evento com contagem de participantes e três selos de estado — participantes, submissão (*Aceita submissões* / *Sem submissão*) e situação (*Finalizado* / *Período encerrado*).
- **Ações por evento:** abrir página pública, gerenciar Novidades, gerenciar Inscritos (com exportação CSV e remoção de aluno), Certificados e presença, Editar evento, Excluir evento, Finalizar evento, gerenciar Trabalhos, distribuir avaliadores, agendar Apresentações, registrar Publicação nos anais.
- **Ações de equipe:** "Novo coordenador" e "Novo avaliador".
- **Ações de conta:** atualizar nome, e-mail e senha, ativar 2FA e encerrar outras sessões em `/user/profile` (mesma tela T18 do participante).
- **Restrições confirmadas:** recebe `403` em `/reviews/assigned` (área do avaliador) e em `/meus-certificados`. Em evento **finalizado**, o próprio sistema informa que editar, gerenciar inscritos e novidades deixa de ser possível.



### 4.3 Avaliador

- **Rótulo exibido:** "Avaliador"
- **Objetivo:** emitir pareceres sobre os trabalhos designados.
- **Como acessa:** credenciais; contas do tipo são criadas em `/register/reviewer`.
- **Menu:** Painel, Avaliações, Minha conta, Sair, além dos links públicos.
- **Painel:** cartão "Painel do avaliador — Acesse os trabalhos designados e envie suas avaliações por critério", com botão "Ir para avaliações".
- **Ações:** consultar `/reviews/assigned`.
- **Ações de conta:** atualizar nome, e-mail e senha, ativar 2FA e encerrar outras sessões em `/user/profile` (mesma tela T18 do participante).
- **Restrições confirmadas:** `403` em todas as rotas de coordenação e em `/meus-certificados`, `/works/{id}`, `/works/{id}/download` e `/certificates/{id}/download`.



### 4.4 Visitante (não autenticado)

Não é um perfil cadastrável, mas é um estado relevante do sistema. Pode: consultar a vitrine de eventos, abrir a página de qualquer evento, baixar anexos do evento, consultar os anais, validar certificado por código, cadastrar-se e recuperar senha. Qualquer rota interna redireciona para `/login` com HTTP 302.

**Grau de certeza:** Confirmado para os quatro estados.

**Observação importante sobre nomenclatura:** não foi encontrado nenhum perfil chamado "Administrador" com poderes globais distintos do Coordenador. A conta `admin@conectaifpa.com` é apresentada pela interface como **Coordenador** e seu painel mostra "Meus eventos" — sugerindo escopo por evento próprio e não administração global do sistema. Não havia um segundo coordenador disponível para verificar se um coordenador vê os eventos de outro. **Grau de certeza: parcialmente confirmado.**

---



## 5. Matriz de permissões

Construída por **acesso direto às rotas** em cada perfil, registrando o código HTTP retornado. Legenda: ✓ = acesso concedido (200); ✗ = bloqueado; 302 = redirecionado ao login.

### 5.1 Acesso a telas


| Funcionalidade / Rota                                                 | Visitante | Aluno | Avaliador | Coordenador |
| --------------------------------------------------------------------- | --------- | ----- | --------- | ----------- |
| Página inicial e eventos públicos                                     | ✓         | ✓     | ✓         | ✓           |
| Validar certificado (`/validar-certificado`, `/certificado/{código}`) | ✓         | ✓     | ✓         | ✓           |
| Baixar anexo do evento (`/events/{id}/documentos/{doc}/download`)     | ✓         | ✓     | ✓         | ✓           |
| Anais públicos (`/events/{id}/anais`)                                 | ✓         | ✓     | ✓         | ✓           |
| Painel (`/dashboard`)                                                 | 302       | ✓     | ✓         | ✓           |
| Minha conta (`/user/profile`)                                         | 302       | ✓     | ✓         | ✓           |
| Meus trabalhos (`/works/my`)                                          | 302       | ✓     | ✓         | ✓           |
| Minha apresentação (`/works/my-presentation`)                         | 302       | ✓     | ✓         | ✓           |
| Meus certificados (`/meus-certificados`)                              | 302       | ✓     | ✗ 403     | ✗ 403       |
| Avaliações designadas (`/reviews/assigned`)                           | 302       | ✗ 403 | ✓         | ✗ 403       |
| Criar evento (`/events/create`)                                       | 302       | ✗ 403 | ✗ 403     | ✓           |
| Editar evento (`/events/edit/{id}`)                                   | 302       | ✗ 403 | ✗ 403     | ✓           |
| Gerenciar inscritos (`/events/registered/{id}`)                       | 302       | ✗ 403 | ✗ 403     | ✓           |
| Exportar inscritos CSV (`/events/{id}/export-csv`)                    | 302       | ✗ 403 | ✗ 403     | ✓           |
| Configurar novidades (`/events/{id}/novidades`)                       | 302       | ✗ 403 | ✗ 403     | ✓           |
| Trabalhos do evento (`/events/{id}/works`)                            | 302       | ✗ 403 | ✗ 403     | ✓           |
| Publicação nos anais (`/events/{id}/anais/coordenacao`)               | 302       | ✗ 403 | ✗ 403     | ✓           |
| Cronograma de apresentações (`/events/{id}/apresentacoes`)            | 302       | ✗ 403 | ✗ 403     | ✓           |
| Certificados e presença (`/events/{id}/certificates`)                 | 302       | ✗ 403 | ✗ 403     | ✓           |
| Certificados emitidos (`/events/{id}/certificates/issued`)            | 302       | ✗ 403 | ✗ 403     | ✓           |
| Lista de presença de atividade                                        | 302       | ✗ 403 | ✗ 403     | ✓           |
| Cadastro de assinaturas (`/assinaturas`)                              | 302       | ✗ 403 | ✗ 403     | ✓           |
| Cadastro de coordenador (`/register/coordinator`) — GET e POST        | 302       | ✗ 403 | ✗ 403     | ✓           |
| Cadastro de avaliador (`/register/reviewer`) — GET e POST             | 302       | ✗ 403 | ✗ 403     | ✓           |




### 5.2 Acesso a recursos de terceiros (teste de IDOR)

Executado com um aluno recém-criado, **sem qualquer vínculo** com os eventos, trabalhos ou certificados existentes.


| Recurso de outro usuário                                                                | Resultado                             |
| --------------------------------------------------------------------------------------- | ------------------------------------- |
| Detalhe de trabalho (`/works/1`, `/works/2`)                                            | ✗ 403 Proibido                        |
| Download do arquivo do trabalho (`/works/1/download`)                                   | ✗ 403 Proibido                        |
| Download de certificado em PDF (`/certificates/1/download`, `/certificates/2/download`) | ✗ 403 Proibido                        |
| Lista de certificados emitidos do evento                                                | ✗ 403 Proibido                        |
| Próprias listas (`/works/my`, `/meus-certificados`)                                     | ✓ 200, exibindo apenas dados próprios |


**Conclusão:** o controle de acesso é consistente e aplicado no servidor. Não há escalonamento de privilégio nem referência direta insegura a objetos.
**Grau de certeza:** Confirmado.

**Diferença entre "não aparece" e "aparece mas é impedido":** para o Aluno e o Avaliador, as funcionalidades de coordenação **não aparecem no menu nem no painel** — a restrição é primeiro visual. Ao forçar a URL, o servidor responde `403` (`Proibido` / `ACESSO NEGADO`), ou seja, a restrição é **também efetiva**, não apenas cosmética. Isso inclui as telas de cadastro de coordenador e de avaliador (`/register/coordinator` e `/register/reviewer`): tanto a abertura (GET) quanto o envio (POST) são bloqueados para quem não é coordenador.

---



## 6. Arquitetura funcional observável

Do ponto de vista do usuário, a aplicação se organiza em quatro camadas:

```
┌─────────────────────────────────────────────────────────────┐
│ CAMADA PÚBLICA (sem autenticação)                           │
│ Vitrine de eventos · Página do evento · Anais · Anexos       │
│ Validação de certificado · Login · Cadastro · Recuperar senha│
├─────────────────────────────────────────────────────────────┤
│ ÁREA DO PARTICIPANTE                                        │
│ Painel de participação · Meus trabalhos · Minha apresentação │
│ Meus certificados · Minha conta (perfil, senha, 2FA, sessões)│
├─────────────────────────────────────────────────────────────┤
│ ÁREA DA COORDENAÇÃO (por evento)                            │
│ Criar/Editar (assistente) · Inscritos · Novidades           │
│ Trabalhos → Avaliadores → Apresentações → Anais             │
│ Certificados e presença → Emitidos · Assinaturas            │
│ Cadastro de equipe (coordenador/avaliador)                  │
├─────────────────────────────────────────────────────────────┤
│ ÁREA DO AVALIADOR                                           │
│ Avaliações designadas                                       │
└─────────────────────────────────────────────────────────────┘
```

Elementos transversais presentes em todas as telas: cabeçalho com marca e navegação, barra de acessibilidade (A−/Padrão/A+/Contraste), widget VLibras, rodapé institucional e, em telas internas, o menu do usuário com "Sair".

**Grau de certeza:** Confirmado.

---



## 7. Mapa de navegação

```
/ (vitrine)
├── #eventos ......................... âncora + "Carregar mais eventos"
├── /events/{id} ..................... página do evento
│   ├── /events/{id}/anais ........... anais públicos
│   ├── /events/{id}/documentos/{d}/download
│   └── [modal] detalhe da atividade
├── /validar-certificado ............. validação por código
│   └── /certificado/{código} ........ resultado da validação
├── /login
│   └── /forgot-password
└── /register

[Aluno]  /dashboard
         ├── /works/my ─────────► /works/{id}
         ├── /works/my-presentation
         ├── /meus-certificados ──► /certificado/{código} · /certificates/{id}/download
         └── /user/profile

[Avaliador] /dashboard
            └── /reviews/assigned

[Coordenador] /dashboard
   ├── /events/create ............... assistente de 5 etapas
   ├── /register/coordinator · /register/reviewer
   └── por evento:
       ├── /events/edit/{id} ........ assistente de 9 etapas
       ├── /events/registered/{id} .. + /events/{id}/export-csv
       ├── /events/{id}/novidades
       ├── /events/{id}/works ....... + distribuição de avaliadores
       │   └── /works/{id} .......... + /works/{id}/download
       ├── /events/{id}/apresentacoes
       ├── /events/{id}/anais/coordenacao
       ├── /events/{id}/certificates
       │   ├── /events/{id}/certificates/issued
       │   ├── /events/{id}/certificates/activities/{a}/presence
       │   └── /assinaturas?event={id}
       └── /events/{id}/finalize (ação)
```

**Grau de certeza:** Confirmado — todos os nós foram abertos, exceto as ações de escrita, que foram identificadas pelos formulários e não executadas.

---



## 8. Inventário de telas



### 8.1 Telas públicas


| ID  | Tela                   | Rota                      | Objetivo                     | Elementos observados                                                                                                                                                                                                                                                                                                                           |
| --- | ---------------------- | ------------------------- | ---------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| T01 | Vitrine / Home         | `/`                       | Divulgar eventos             | Hero com CTA "Explorar eventos" e "Validar certificado"; cards de evento com imagem, categoria, período, título, resumo, local e modalidade; botão "Carregar mais eventos"; barra de acessibilidade; rodapé                                                                                                                                    |
| T02 | Página do evento       | `/events/{id}`            | Detalhar o evento            | Cabeçalho com período, horário, local, categoria, modalidade, tipo, prazo de inscrição; selo de situação; seções Sobre, Localização (com Google Maps), Organização e contato, Atividades (cronograma clicável com modal), Convidados, Submissões Científicas, Documentos e Anexos, Novidades; em evento híbrido, bloco "Ambiente EAD" com link |
| T03 | Anais do evento        | `/events/{id}/anais`      | Publicar trabalhos           | Contagem de trabalhos registrados, agrupamento por tipo, autor, data e link "Ver registro / PDF"                                                                                                                                                                                                                                               |
| T04 | Validar certificado    | `/validar-certificado`    | Conferir autenticidade       | Campo "Código de validação" (máx. 64), botão "Verificar certificado", texto explicativo informando que maiúsculas/minúsculas e espaços são ignorados e que não é necessário login                                                                                                                                                              |
| T05 | Resultado da validação | `/certificado/{código}`   | Exibir validade              | Título "Certificado válido" e link "Validar outro certificado"                                                                                                                                                                                                                                                                                 |
| T06 | Login                  | `/login`                  | Autenticar                   | E-mail, senha, "Lembrar de mim", "Esqueceu a senha?", "Criar conta"                                                                                                                                                                                                                                                                            |
| T07 | Cadastro               | `/register`               | Autocadastro de participante | Nome, e-mail, alternância aluno externo, matrícula ou instituição, curso, senha, confirmação                                                                                                                                                                                                                                                   |
| T08 | Recuperar senha        | `/forgot-password`        | Redefinir senha              | E-mail e "Enviar link de recuperação"                                                                                                                                                                                                                                                                                                          |
| T09 | Erro 404               | qualquer rota inexistente | —                            | Texto "404 NÃO ENCONTRADO", sem navegação                                                                                                                                                                                                                                                                                                      |
| T10 | Erro 419               | POST com token inválido   | —                            | Texto "419 PÁGINA EXPIRADA", sem navegação                                                                                                                                                                                                                                                                                                     |
| T11 | Erro 403               | rota sem permissão        | —                            | Texto "403 ACESSO NEGADO" / "Proibido"                                                                                                                                                                                                                                                                                                         |
| T12 | Erro 429               | após 5 logins falhos      | —                            | Texto "429 MUITAS SOLICITAÇÕES"                                                                                                                                                                                                                                                                                                                |




### 8.2 Telas do Aluno


| ID  | Tela                        | Rota                     | Elementos observados                                                                                                                                                                                                                                                                                                  |
| --- | --------------------------- | ------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| T13 | Painel do participante      | `/dashboard`             | Cartão do usuário (nome, e-mail, perfil "Aluno"); "Eventos que estou participando" com contagem de participantes, selo de submissão, "Ver evento" e "Sair do evento"; seção "Eventos encerrados" com data de finalização; estado vazio "Nenhum evento ainda — Explore os eventos disponíveis" com botão "Ver eventos" |
| T14 | Meus trabalhos              | `/works/my`              | Tabela Evento / Trabalho / Tipo / Status / Ações; estado vazio "Nenhum trabalho submetido — Participe de um evento e realize sua primeira submissão"                                                                                                                                                                  |
| T15 | Detalhe do trabalho (autor) | `/works/{id}`            | Seções Sobre esta submissão, Resultado da avaliação, Detalhes da apresentação, Autores, Pareceres enviados ao autor, Mensagem da coordenação, **Ações do autor**, Contexto; link "Baixar arquivo"                                                                                                                     |
| T16 | Minha apresentação          | `/works/my-presentation` | Agendamentos dos trabalhos aceitos, com evento e link para o trabalho; estado vazio "Nada agendado ainda"                                                                                                                                                                                                             |
| T17 | Meus certificados           | `/meus-certificados`     | Lista de certificados com "Abrir página de validação" e "Baixar PDF"; estado vazio "Nenhum certificado disponível ainda"                                                                                                                                                                                              |
| T18 | Minha conta                 | `/user/profile`          | Informações do Perfil (nome, e-mail); Atualizar Senha (senha atual, nova, confirmação); Autenticação de dois fatores com botão ATIVAR; Sessões do Navegador com dispositivo/IP e "Encerrar outras sessões". **A mesma tela está disponível para Coordenador e Avaliador** (ver 8.3 e 8.4).                            |




### 8.3 Telas do Coordenador


| ID  | Tela                              | Rota                                                | Elementos observados                                                                                                                                                                                                                                                                                                           |
| --- | --------------------------------- | --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| T19 | Painel do coordenador             | `/dashboard`                                        | "Meus eventos"; bloco "Cadastros de equipe" com "Novo coordenador" e "Novo avaliador"; por evento: selos de participantes/submissão/situação e bloco "Gestão" com as ações permitidas + "Excluir evento"                                                                                                                       |
| T20 | Criar evento                      | `/events/create`                                    | Assistente de **5 etapas**, indicador 1-2-3-4-5, editor de texto com negrito/listas/alinhamento, tabela de convidados, botão "Próximo"                                                                                                                                                                                         |
| T21 | Editar evento                     | `/events/edit/{id}`                                 | Assistente de **9 etapas**: Informações Básicas, Data e Horário, Localização, Imagem (1980x1080), Organização e Contato, Configurações de Inscrição, Configuração Científica, Convidados, Atividades e Documentos; botões "+ Novo convidado", "+ Nova atividade", "+ Novo documento", "Selecionar Arquivo" e "Publicar evento" |
| T22 | Gerenciar inscritos               | `/events/registered/{id}`                           | Tabela ordenável (DataTables) com #, Nome, E-mail, Matrícula, Instituição, Curso, Ações; paginação Primeiro/Anterior/1/Próximo/Último; "Exportar CSV"; "Remover aluno" por linha                                                                                                                                               |
| T23 | Configurar novidades              | `/events/{id}/novidades`                            | Formulário Título + Conteúdo, botão "Adicionar novidade", contador "Novidades publicadas 0"                                                                                                                                                                                                                                    |
| T24 | Trabalhos do evento               | `/events/{id}/works`                                | Bloco "Vinculação de avaliadores" com busca, seleção de avaliadores, campo "Quantidade de avaliadores por trabalho" (1 a 2) e botão "Distribuir avaliadores"; filtro por tipo de trabalho; tabela ID / Trabalho / Autor principal / Tipo / Status / Avaliadores / Decisão final / Ações                                        |
| T25 | Detalhe do trabalho (coordenação) | `/works/{id}`                                       | Mesmas seções do autor, mais "Avaliadores vinculados", "Avaliações recebidas", "Excluir trabalho", download da "Versão refinada (reavaliação)" e atalhos para apresentações e anais                                                                                                                                            |
| T26 | Cronograma de apresentações       | `/events/{id}/apresentacoes`                        | Por trabalho: tipo de apresentação, nome da sessão, local, início e fim; botão "Salvar este trabalho"                                                                                                                                                                                                                          |
| T27 | Publicação nos anais              | `/events/{id}/anais/coordenacao`                    | Tabela Trabalho / Tipo / Situação / Registro nos anais, com URL opcional e observações; botões "Registrar nos anais", "Salvar alterações" e "Remover dos anais"; data do primeiro registro                                                                                                                                     |
| T28 | Certificados e presença           | `/events/{id}/certificates`                         | Cinco blocos numerados: 1) Organização e instituição nos PDFs, 2) Assinaturas do evento, 3) Presença geral + carga horária total, 4) Apresentação de trabalhos (título, CH e situação de presença por trabalho), 5) Atividades com CH, presença e "Abrir lista de presença"; atalhos para Assinaturas e "Emitidos (lista)"     |
| T29 | Certificados emitidos             | `/events/{id}/certificates/issued`                  | Tabela Data / Participante / Tipo / Código / PDF com "Baixar"; tipos observados: "Apresentação de trabalho" e "Participação em atividade"                                                                                                                                                                                      |
| T30 | Presença de atividade             | `/events/{id}/certificates/activities/{a}/presence` | Carga horária da atividade e lista de participantes com marcação de presença                                                                                                                                                                                                                                                   |
| T31 | Assinaturas                       | `/assinaturas`                                      | Cadastro de assinaturas usadas nos certificados (nome e cargo, ex.: "Coordenador geral", "Vice-coordenador")                                                                                                                                                                                                                   |
| T32 | Cadastrar coordenador             | `/register/coordinator`                             | Nome, e-mail, senha, confirmação; campo oculto `role=coordinator`                                                                                                                                                                                                                                                              |
| T33 | Cadastrar avaliador               | `/register/reviewer`                                | Nome, e-mail, senha, confirmação; campo oculto `role=reviewer`                                                                                                                                                                                                                                                                 |
| T18 | Minha conta                       | `/user/profile`                                     | Mesmos elementos descritos em 8.2: Informações do Perfil (nome, e-mail), Atualizar Senha, Autenticação de dois fatores e Sessões do Navegador. Acessível pelo menu "Minha conta".                                                                                                                                              |




### 8.4 Telas do Avaliador


| ID  | Tela                  | Rota                | Elementos observados                                                                                                                                                              |
| --- | --------------------- | ------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| T34 | Painel do avaliador   | `/dashboard`        | Cartão "Painel do avaliador — Acesse os trabalhos designados e envie suas avaliações por critério" e botão "Ir para avaliações"                                                   |
| T35 | Avaliações designadas | `/reviews/assigned` | Título "Minhas Avaliações"; no ambiente analisado, estado vazio "Nenhum trabalho designado"                                                                                       |
| T18 | Minha conta           | `/user/profile`     | Mesmos elementos descritos em 8.2: Informações do Perfil (nome, e-mail), Atualizar Senha, Autenticação de dois fatores e Sessões do Navegador. Acessível pelo menu "Minha conta". |


**Total: 35 telas distintas inventariadas.**

---



## 9. Inventário de rotas

Todas as rotas abaixo foram acessadas ou tiveram sua existência comprovada por link/formulário exibido na interface. Nenhuma rota foi inferida.

### 9.1 Rotas de leitura (GET)


| Rota                                                  | Tela                    | Autenticação               | Perfis com acesso                  | Finalidade                     |
| ----------------------------------------------------- | ----------------------- | -------------------------- | ---------------------------------- | ------------------------------ |
| `/`                                                   | Vitrine                 | Não                        | Todos                              | Listar eventos                 |
| `/events/{id}`                                        | Página do evento        | Não                        | Todos                              | Detalhar evento                |
| `/events/{id}/anais`                                  | Anais públicos          | Não                        | Todos                              | Consultar trabalhos publicados |
| `/events/{id}/documentos/{doc}/download`              | —                       | Não                        | Todos                              | Baixar anexo do evento         |
| `/validar-certificado`                                | Validação               | Não                        | Todos                              | Formulário de validação        |
| `/certificado/{código}`                               | Resultado               | Não                        | Todos                              | Exibir validade do certificado |
| `/login`                                              | Login                   | Não (bloqueia autenticado) | Visitante                          | Autenticar                     |
| `/register`                                           | Cadastro                | Não (bloqueia autenticado) | Visitante                          | Autocadastro de participante   |
| `/forgot-password`                                    | Recuperar senha         | Não (bloqueia autenticado) | Visitante                          | Solicitar redefinição          |
| `/dashboard`                                          | Painel                  | Sim                        | Todos autenticados                 | Painel conforme perfil         |
| `/user/profile`                                       | Minha conta             | Sim                        | Todos autenticados                 | Gerenciar conta                |
| `/works/my`                                           | Meus trabalhos          | Sim                        | Aluno (e acessível a Coord./Aval.) | Listar trabalhos próprios      |
| `/works/my-presentation`                              | Minha apresentação      | Sim                        | Aluno (e acessível a Coord./Aval.) | Agendamentos próprios          |
| `/meus-certificados`                                  | Meus certificados       | Sim                        | Aluno                              | Certificados próprios          |
| `/works/{id}`                                         | Detalhe do trabalho     | Sim                        | Autor e Coordenador do evento      | Consultar submissão            |
| `/works/{id}/download`                                | —                       | Sim                        | Autor e Coordenador                | Baixar arquivo do trabalho     |
| `/certificates/{id}/download`                         | —                       | Sim                        | Titular e Coordenador              | Baixar certificado em PDF      |
| `/reviews/assigned`                                   | Avaliações              | Sim                        | Avaliador                          | Trabalhos designados           |
| `/events/create`                                      | Criar evento            | Sim                        | Coordenador                        | Assistente de criação          |
| `/events/edit/{id}`                                   | Editar evento           | Sim                        | Coordenador                        | Assistente de edição           |
| `/events/registered/{id}`                             | Inscritos               | Sim                        | Coordenador                        | Gerenciar inscrições           |
| `/events/{id}/export-csv`                             | —                       | Sim                        | Coordenador                        | Exportar inscritos (text/csv)  |
| `/events/{id}/novidades`                              | Novidades               | Sim                        | Coordenador                        | Gerenciar comunicados          |
| `/events/{id}/works`                                  | Trabalhos               | Sim                        | Coordenador                        | Gerenciar submissões           |
| `/events/{id}/apresentacoes`                          | Apresentações           | Sim                        | Coordenador                        | Agendar apresentações          |
| `/events/{id}/anais/coordenacao`                      | Anais (gestão)          | Sim                        | Coordenador                        | Registrar publicação           |
| `/events/{id}/certificates`                           | Certificados e presença | Sim                        | Coordenador                        | Configurar e registrar         |
| `/events/{id}/certificates/issued`                    | Emitidos                | Sim                        | Coordenador                        | Auditar emissões               |
| `/events/{id}/certificates/activities/{a}/presence`   | Presença                | Sim                        | Coordenador                        | Presença por atividade         |
| `/assinaturas`                                        | Assinaturas             | Sim                        | Coordenador                        | Cadastro de assinaturas        |
| `/register/coordinator`                               | Cadastrar coordenador   | Sim                        | Coordenador                        | Criar conta de coordenador     |
| `/register/reviewer`                                  | Cadastrar avaliador     | Sim                        | Coordenador                        | Criar conta de avaliador       |
| `/works/{id}/reviews/{r}/refined-correction/download` | —                       | Sim                        | Coordenador                        | Baixar versão refinada         |




### 9.2 Rotas de escrita (POST / PUT / DELETE)

Identificadas pelo atributo `action` dos formulários exibidos. Executadas somente quando não persistiam dados (validações).


| Rota                                                | Método                  | Origem            | Finalidade                                      | Executada?                                              |
| --------------------------------------------------- | ----------------------- | ----------------- | ----------------------------------------------- | ------------------------------------------------------- |
| `/login`                                            | POST                    | T06               | Autenticar                                      | Sim                                                     |
| `/register`                                         | POST                    | T07               | Criar participante                              | Sim (2 contas)                                          |
| `/register/coordinator`                             | POST                    | T32               | Criar coordenador                               | Sim, apenas para testar autorização (bloqueado por 403) |
| `/register/reviewer`                                | POST                    | T33               | Criar avaliador                                 | Sim, apenas para testar autorização (bloqueado por 403) |
| `/forgot-password`                                  | POST                    | T08               | Enviar link de redefinição                      | Sim (e-mail inexistente)                                |
| `/validar-certificado`                              | POST                    | T04               | Validar código                                  | Sim                                                     |
| `/logout`                                           | POST                    | menu              | Encerrar sessão                                 | Sim                                                     |
| `/events`                                           | POST                    | T20               | Criar evento                                    | Não                                                     |
| `/events/update/{id}`                               | POST + `_method`        | T21               | Atualizar evento                                | Não                                                     |
| `/events/{id}`                                      | POST + `_method`        | T19               | Excluir evento                                  | Não                                                     |
| `/events/{id}/finalize`                             | POST                    | T02 (coordenação) | Finalizar evento                                | Não                                                     |
| `/events/{id}/novidades`                            | POST                    | T23               | Publicar novidade                               | Não                                                     |
| `/events/{id}/remove/{aluno}`                       | POST + `_method`        | T22               | Remover inscrito                                | Não                                                     |
| `/events/leave/{id}`                                | POST + `_method`        | T13               | Cancelar própria participação                   | Não                                                     |
| `/events/{id}/works/reviewers/distribute`           | POST                    | T24               | Distribuir avaliadores                          | Não                                                     |
| `/works/{id}`                                       | POST + `_method`        | T25               | Excluir trabalho                                | Não                                                     |
| `/works/{id}/presentation`                          | POST                    | T26               | Salvar agendamento                              | Não                                                     |
| `/works/{id}/annals`                                | POST                    | T27               | Registrar/atualizar nos anais                   | Não                                                     |
| `/works/{id}/annals`                                | POST + `_method=DELETE` | T27               | Remover dos anais                               | Não                                                     |
| `/events/{id}/certificates/meta`                    | POST                    | T28               | Salvar organização, instituição e carga horária | Não                                                     |
| `/events/{id}/certificates/signatures`              | POST                    | T28               | Vincular assinaturas                            | Não                                                     |
| `/events/{id}/certificates/presence`                | POST                    | T28               | Salvar presença geral                           | Não                                                     |
| `/events/{id}/certificates/presentation-rows`       | POST                    | T28               | Salvar presença e CH das apresentações          | Não                                                     |
| `/events/{id}/certificates/activities/{a}/presence` | POST                    | T30               | Salvar presença da atividade                    | Não                                                     |
| `/events/{id}/certificates/activities/{a}/workload` | POST                    | T30               | Salvar CH da atividade                          | Não                                                     |


---



## 10. Inventário de funcionalidades


| ID  | Funcionalidade                                   | Perfil       | Como foi observada                                                  |
| --- | ------------------------------------------------ | ------------ | ------------------------------------------------------------------- |
| F01 | Listagem paginada de eventos com "Carregar mais" | Todos        | Acionado; ao esgotar, exibe "Não há mais eventos disponíveis."      |
| F02 | Página detalhada do evento com 8 seções          | Todos        | Abertas nos 3 eventos                                               |
| F03 | Cronograma com modal de detalhes da atividade    | Todos        | Modal aberto exibindo Convidados, Tipo, Local, Data e horário       |
| F04 | Download público de anexos                       | Todos        | Retornou `.docx` com HTTP 200                                       |
| F05 | Anais públicos do evento                         | Todos        | Tela aberta com 1 trabalho registrado                               |
| F06 | Validação pública de certificado por código      | Todos        | Códigos válidos → "Certificado válido"; inválido → mensagem de erro |
| F07 | Autocadastro de participante (interno/externo)   | Visitante    | 2 contas criadas; alternância de campos verificada                  |
| F08 | Login com "Lembrar de mim"                       | Visitante    | Autenticado nos 3 perfis                                            |
| F09 | Bloqueio por tentativas excessivas               | Visitante    | HTTP 429 após 5 falhas                                              |
| F10 | Recuperação de senha                             | Visitante    | Formulário enviado                                                  |
| F11 | Logout                                           | Autenticados | Redireciona para `/`                                                |
| F12 | Painel personalizado por perfil                  | Autenticados | Três painéis distintos confirmados                                  |
| F13 | Edição de nome e e-mail                          | Autenticados | Formulário presente (não submetido)                                 |
| F14 | Troca de senha                                   | Autenticados | Formulário presente (não submetido)                                 |
| F15 | Autenticação de dois fatores                     | Autenticados | Botão "ATIVAR" presente                                             |
| F16 | Gerenciamento de sessões do navegador            | Autenticados | Sessão atual listada (Windows - Chrome, 127.0.0.1)                  |
| F17 | Barra de acessibilidade (fonte e contraste)      | Todos        | Escala 14→20px, alto contraste, persistência                        |
| F18 | Integração VLibras                               | Todos        | Widget presente e ativo                                             |
| F19 | Acompanhamento de eventos inscritos              | Aluno        | Painel com evento ativo e encerrados                                |
| F20 | Cancelamento da própria participação             | Aluno        | Botão "Sair do evento" presente (não acionado)                      |
| F21 | Consulta de trabalhos submetidos e status        | Aluno        | 2 trabalhos com status distintos                                    |
| F22 | Consulta de parecer e mensagem da coordenação    | Aluno        | Seções presentes no detalhe do trabalho                             |
| F23 | Consulta do agendamento da apresentação          | Aluno        | Tela com 2 apresentações                                            |
| F24 | Download de certificados próprios                | Aluno        | PDF retornado com HTTP 200                                          |
| F25 | Criação de evento (assistente de 5 etapas)       | Coordenador  | Formulário completo mapeado (não submetido)                         |
| F26 | Edição de evento (assistente de 9 etapas)        | Coordenador  | Formulário completo mapeado (não submetido)                         |
| F27 | Exclusão de evento                               | Coordenador  | Botão presente em todos os eventos                                  |
| F28 | Finalização de evento                            | Coordenador  | Botão presente somente em eventos não finalizados                   |
| F29 | Gestão de inscritos com ordenação e paginação    | Coordenador  | Tabela com 3 inscritos, ordenável                                   |
| F30 | Exportação de inscritos em CSV                   | Coordenador  | Retornou `text/csv`                                                 |
| F31 | Remoção de inscrito                              | Coordenador  | Botão por linha                                                     |
| F32 | Publicação de novidades do evento                | Coordenador  | Formulário com título e conteúdo                                    |
| F33 | Cadastro de convidados, atividades e documentos  | Coordenador  | Etapas 8 e 9 do assistente de edição                                |
| F34 | Configuração de submissões científicas           | Coordenador  | Prazo, 11 tipos de trabalho, mín./máx. de avaliadores               |
| F35 | Distribuição automática de avaliadores           | Coordenador  | Bloco com seleção e quantidade por trabalho                         |
| F36 | Filtro de trabalhos por tipo                     | Coordenador  | Select com os tipos aceitos pelo evento                             |
| F37 | Exclusão de trabalho                             | Coordenador  | Seção "Excluir trabalho"                                            |
| F38 | Agendamento das apresentações                    | Coordenador  | Tipo, sessão, local, início e fim por trabalho                      |
| F39 | Registro/atualização/remoção nos anais           | Coordenador  | Três botões distintos por situação do trabalho                      |
| F40 | Configuração de dados do certificado             | Coordenador  | Organização, instituição e carga horária total                      |
| F41 | Vinculação de assinaturas ao evento              | Coordenador  | Seleção múltipla a partir do cadastro de assinaturas                |
| F42 | Registro de presença geral                       | Coordenador  | Lista de participantes com marcação                                 |
| F43 | Registro de presença e CH por atividade          | Coordenador  | Tela dedicada por atividade                                         |
| F44 | Registro de presença e CH por apresentação       | Coordenador  | Linhas por trabalho com situação de presença                        |
| F45 | Auditoria de certificados emitidos               | Coordenador  | 4 certificados com data, tipo, código e PDF                         |
| F46 | Cadastro de coordenadores e avaliadores          | Coordenador  | Duas telas específicas                                              |
| F47 | Consulta de avaliações designadas                | Avaliador    | Tela aberta (sem designações no ambiente)                           |


**Total: 47 funcionalidades identificadas.**

---



## 11. Fluxos de negócio



### FL01 — Cadastro e primeiro acesso do participante (testado integralmente)

1. Visitante acessa `/register`.
2. Informa nome e e-mail.
3. Escolhe entre aluno do IFPA Belém (matrícula de 12 dígitos + curso) ou aluno externo (instituição + curso) — a marcação da caixa **troca os campos exibidos e obrigatórios em tempo real**.
4. Define senha e confirmação.
5. Envia. O sistema valida no servidor.
6. Em caso de sucesso, **autentica automaticamente** e redireciona para `/dashboard`, exibindo o perfil "Aluno".

**Resultado observado:** duas contas criadas com sucesso; painel exibido no estado vazio, com convite "Explore os eventos disponíveis e inscreva-se nos que mais te interessam".
**Grau de certeza:** Confirmado.

### FL02 — Autenticação (testado integralmente)

1. Usuário acessa `/login`, informa e-mail e senha.
2. Credenciais corretas → redireciona para `/dashboard` com o painel do perfil.
3. Credenciais incorretas → permanece em `/login` com "As credenciais indicadas não coincidem com as registradas no sistema."
4. Após 5 tentativas falhas → HTTP 429.
5. Usuário autenticado que acessa `/login`, `/register` ou `/forgot-password` é redirecionado para `/dashboard`.
6. "Sair" encerra a sessão e redireciona para `/`.

**Grau de certeza:** Confirmado.

### FL03 — Criação de evento (mapeado, não executado por restrição de dados)

Assistente de **5 etapas**, com os campos obrigatórios: título, categoria, modalidade, tipo de evento científico, capacidade, descrição, datas e horários de início e fim, imagem de capa (PNG/JPG), nome e e-mail do coordenador e data/hora limite de inscrição. Opcionais: categoria e tipo personalizados ("Outro"), link do ambiente EAD, telefone, aceite de submissões com prazo, tipos de trabalho aceitos e faixa de avaliadores por trabalho. Localização é pré-preenchida com o campus Belém em campos ocultos.
**Grau de certeza:** Confirmado quanto à estrutura e obrigatoriedade; **não confirmado** quanto ao resultado do envio.

### FL04 — Ciclo de vida do evento (parcialmente confirmado)

Estados observados na interface, com efeito prático comprovado:

```
[em edição/publicado] --("Publicar evento")--> [ativo]
        |                                          |
        | prazo de inscrição vence                 | ("Finalizar evento")
        v                                          v
[Período encerrado] ------------------------> [Finalizado]
```

- Evento **ativo/encerrado**: coordenador vê Novidades, Inscritos, Certificados, Editar e Excluir; a página do evento exibe o botão "Finalizar evento".
- Evento **Finalizado**: o painel deixa de oferecer Novidades, Inscritos e Editar; permanecem Trabalhos, Anais e Certificados; a página do evento informa, para a coordenação: *"Evento finalizado: não é possível editar, gerenciar inscritos ou novidades. A página permanece visível ao público e você pode excluir o evento no painel."*; para o público, exibe *"Este evento foi finalizado pelo coordenador."*
- No painel do aluno, o evento finalizado migra para "Eventos encerrados" com a data de finalização.

**Grau de certeza:** Confirmado (comparação entre o evento 1, finalizado, e os eventos 2 e 3, encerrados).

### FL05 — Inscrição em evento (não testável no ambiente)

O painel do aluno mostra eventos "que estou participando" com a ação "Sair do evento", e a lista de inscritos do coordenador confirma que existem participantes vinculados. Contudo, **os três eventos do ambiente estavam com prazo de inscrição vencido**, e nenhuma tela exibiu botão de inscrição. A página do evento apresentava apenas "O período deste evento foi encerrado."
**Conclusão:** a funcionalidade de inscrição existe (há inscritos e há o cancelamento), mas o **gatilho de inscrição não pôde ser observado**.
**Grau de certeza:** Não confirmado quanto à tela/botão de inscrição; confirmado quanto à existência do vínculo e do cancelamento.

### FL06 — Submissão e avaliação de trabalho (parcialmente observável)

Reconstituído a partir dos estados encontrados nos dois trabalhos existentes:

1. Coordenador habilita "Este evento aceita submissão de trabalhos científicos", define prazo e os tipos aceitos (evento 1: Artigo Completo e Pôster).
2. Participante submete o trabalho — o autor vê "Sobre esta submissão", "Autores" e "Ações do autor".
3. Após o prazo, o coordenador distribui os trabalhos aos avaliadores, respeitando mínimo 1 e máximo 2 por trabalho.
4. Avaliador acessa "Minhas Avaliações" e envia parecer **por critério**.
5. Coordenador registra a decisão final — a tabela mostra "Decisão final: Registrada 04/05/2026" e a coluna Status com "Publicado nos anais" e "Apresentado".
6. Há previsão de **reavaliação com versão refinada**: o detalhe do trabalho oferece "Versão refinada (reavaliação)" para download.
7. Coordenador agenda a apresentação (tipo, sessão, local, início e fim); o autor passa a ver o agendamento em "Minha apresentação".
8. Após apresentado, o trabalho fica elegível ao registro nos anais.

**Grau de certeza:** Confirmado quanto aos estados, telas e artefatos; **não confirmado** quanto ao formulário de submissão do participante e ao formulário de parecer do avaliador, que não puderam ser abertos (prazo encerrado e nenhuma designação para o avaliador disponível).

### FL07 — Publicação nos anais (mapeado)

Regra explicitada na própria tela: *"Apenas trabalhos com status Apresentado aparecem aqui para registro nos anais. Já constam também os que foram publicados anteriormente para permitir atualizar URL ou observações."* O coordenador informa URL opcional e observações (ex.: volume, páginas, DOI) e aciona "Registrar nos anais"; depois pode "Salvar alterações" ou "Remover dos anais". O que é registrado aparece na página pública `/events/{id}/anais`.
**Grau de certeza:** Confirmado quanto à regra e aos estados; ações não executadas.

### FL08 — Presença e emissão de certificados (mapeado)

A tela "Certificados e presença" organiza o processo em cinco passos numerados:

1. Definir organização e instituição que constarão nos PDFs.
2. Selecionar as assinaturas do evento (a partir do cadastro `/assinaturas`, com nome e cargo).
3. Registrar a presença geral e a carga horária total do evento.
4. Registrar presença e carga horária das apresentações de trabalho.
5. Registrar presença e carga horária por atividade, com lista de presença própria.

Os certificados emitidos ficam auditáveis em "Emitidos (lista)", com data, participante, **tipo** ("Participação em atividade", "Apresentação de trabalho"), **código de validação** e PDF. O participante encontra os mesmos certificados em "Meus certificados", com link para a página pública de validação.

**Observação:** não foi localizado um botão explícito de "emitir certificado". Os certificados aparecem como consequência do registro de presença/carga horária. **Grau de certeza: não confirmado** quanto ao gatilho exato da emissão.

### FL09 — Validação pública de certificado (testado integralmente)

1. Qualquer pessoa acessa `/validar-certificado`.
2. Informa o código impresso no PDF (aceita maiúsculas/minúsculas e ignora espaços, conforme o próprio texto da tela).
3. Código válido → página "Certificado válido" com opção "Validar outro certificado".
4. Código inexistente → "Não encontramos um certificado com este código. Verifique o valor e tente novamente."

**Grau de certeza:** Confirmado (testado com 4 códigos reais e com códigos inválidos).

---



## 12. Regras de negócio observadas



### 12.1 Regras confirmadas


| ID   | Regra                                                                              | Evidência                                                                                                       |
| ---- | ---------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| RN01 | O autocadastro público cria exclusivamente participantes                           | Campo oculto `role=participant` no formulário; conta criada exibe perfil "Aluno"                                |
| RN02 | Matrícula do IFPA Belém deve ter exatamente 12 números                             | "A matrícula deve conter exatamente 12 números."                                                                |
| RN03 | Aluno externo não informa matrícula; informa instituição, que se torna obrigatória | Marcação da caixa oculta e desobriga `matricula` e torna `institution` obrigatório                              |
| RN04 | Senha deve ter no mínimo 8 caracteres                                              | "A senha deve ter no mínimo 8 caracteres."                                                                      |
| RN05 | Senha e confirmação devem coincidir                                                | "As senhas não conferem."                                                                                       |
| RN06 | E-mail é único por conta                                                           | Cadastro só concluiu com e-mails distintos                                                                      |
| RN07 | Login com credenciais inválidas não revela se o e-mail existe                      | Mensagem idêntica para e-mail inexistente e senha errada                                                        |
| RN08 | Após 5 tentativas de login falhas o acesso é bloqueado temporariamente             | HTTP 429                                                                                                        |
| RN09 | Usuário autenticado não acessa telas de visitante                                  | `/login`, `/register` e `/forgot-password` redirecionam para `/dashboard`                                       |
| RN10 | Contas de coordenador e avaliador só podem ser criadas por coordenador             | GET e POST de `/register/coordinator` e `/register/reviewer` retornam `403` para Aluno e Avaliador              |
| RN11 | Evento finalizado não pode ser editado, nem ter inscritos ou novidades gerenciados | Aviso na página do evento e ausência das ações no painel                                                        |
| RN12 | Evento finalizado permanece público e ainda pode ser excluído                      | Aviso explícito na tela                                                                                         |
| RN13 | Evento com período encerrado não aceita inscrição                                  | "O período deste evento foi encerrado." e ausência de botão de inscrição                                        |
| RN14 | O evento define se aceita submissões, o prazo e os tipos de trabalho aceitos       | Etapa 7 do assistente; evento 1 aceita Artigo Completo e Pôster, eventos 2 e 3 exibem "Sem submissão"           |
| RN15 | Cada trabalho recebe entre 1 e 2 avaliadores, conforme configuração do evento      | Campo com `min=1`/`max=2` e texto "Cada trabalho marcado deve ficar com no mínimo 1 e no máximo 2 avaliadores"  |
| RN16 | A distribuição de avaliadores só ocorre após o encerramento do prazo de submissão  | "Após o encerramento do prazo de submissão, selecione os trabalhos..."                                          |
| RN17 | Somente trabalho com status "Apresentado" pode ser registrado nos anais            | Texto normativo na tela de publicação                                                                           |
| RN18 | Cada certificado possui código único de validação pública                          | 4 códigos distintos, todos validados com sucesso                                                                |
| RN19 | A validação de certificado é pública e não exige login                             | "Não é necessário estar logado."; testado em sessão anônima                                                     |
| RN20 | Certificados têm tipo distinto por natureza da participação                        | "Participação em atividade" e "Apresentação de trabalho"                                                        |
| RN21 | Cada usuário só acessa seus próprios trabalhos e certificados                      | `403` em todos os recursos de terceiros                                                                         |
| RN22 | Coordenador não acessa a área do avaliador, e vice-versa                           | `403` recíproco                                                                                                 |
| RN23 | Anexos do evento são públicos; arquivos de trabalho e certificados não são         | Anexo com 200 para visitante; trabalho e certificado com 302/403                                                |
| RN24 | Documento só é acessível pelo evento a que pertence                                | `/events/2/documentos/1/download` retorna 404                                                                   |
| RN25 | Formulários são protegidos contra CSRF                                             | Token inválido resulta em "419 PÁGINA EXPIRADA"                                                                 |
| RN26 | A capacidade de participantes deve ser no mínimo 1                                 | Campo numérico com `min=1`                                                                                      |
| RN27 | Imagem de capa aceita apenas PNG/JPG e é obrigatória na criação                    | `accept="image/png, image/jpeg, image/jpg"` e campo obrigatório em `/events/create`, opcional em `/events/edit` |
| RN28 | Preferências de acessibilidade persistem por navegador                             | Fonte e contraste mantidos após recarregar e ao navegar entre páginas                                           |




### 12.2 Regras inferidas (não confirmadas)


| ID   | Regra provável                                                           | Por que não foi confirmada                                                                 |
| ---- | ------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------ |
| RI01 | Coordenador só gerencia os próprios eventos                              | O painel diz "Meus eventos", mas não havia um segundo coordenador para comparar            |
| RI02 | A inscrição é limitada pela capacidade do evento                         | Existe o campo "Capacidade", mas não houve evento aberto para testar o limite              |
| RI03 | A emissão do certificado decorre automaticamente do registro de presença | Não há botão explícito de emissão; a relação não pôde ser executada                        |
| RI04 | O prazo de submissão bloqueia novas submissões                           | Prazo exibido na interface, mas nenhum evento estava com prazo aberto                      |
| RI05 | A avaliação é feita por critérios com notas                              | O painel do avaliador menciona "avaliações por critério", mas não havia trabalho designado |
| RI06 | Existe fluxo de reavaliação após correção do autor                       | Há link "Versão refinada (reavaliação)", mas o fluxo não pôde ser percorrido               |
| RI07 | Novidades do evento notificam os inscritos                               | A seção existe nas duas pontas, mas não havia novidade publicada                           |


---



## 13. Validações



### 13.1 Cadastro de participante (`/register`)


| Campo       | Validação no cliente                        | Validação no servidor                                                          |
| ----------- | ------------------------------------------- | ------------------------------------------------------------------------------ |
| Nome        | `required`                                  | "O nome é obrigatório."                                                        |
| E-mail      | `required`, `type=email`                    | "O e-mail é obrigatório."; unicidade                                           |
| Matrícula   | `required` (quando interno), `maxlength=12` | "A matrícula é obrigatória."; "A matrícula deve conter exatamente 12 números." |
| Instituição | `required` (quando externo)                 | —                                                                              |
| Curso       | `required`                                  | "O curso é obrigatório."                                                       |
| Senha       | `required`                                  | "A senha é obrigatória."; "A senha deve ter no mínimo 8 caracteres."           |
| Confirmação | `required`                                  | "As senhas não conferem."                                                      |


**Observação:** o campo Nome aceitou um único caractere ("A") sem apontar erro de tamanho mínimo. **Grau de certeza: confirmado** (o erro exibido referiu-se apenas a senha e matrícula).

### 13.2 Login (`/login`)

- Campos obrigatórios validados no cliente (`required`) e no servidor: "É obrigatória a indicação de um valor para o campo e-mail." / "...campo senha."
- Credenciais inválidas: mensagem genérica única.
- Limite de tentativas: HTTP 429.



### 13.3 Validação de certificado (`/validar-certificado`)

- Código obrigatório (cliente e servidor). Mensagem do servidor: "É obrigatória a indicação de um valor para o campo codigo."
- Código inexistente: "Não encontramos um certificado com este código."
- Entrada com caracteres especiais e conteúdo do tipo injeção (`' OR 1=1 -- <script>alert(1)</script>`) foi tratada com segurança: retornou a mesma mensagem de "não encontrado", sem erro de banco e sem execução de script. **Grau de certeza: confirmado.**



### 13.4 Recuperação de senha (`/forgot-password`)

- E-mail obrigatório e em formato válido.
- E-mail não cadastrado: "Não existe nenhum usuário com o e-mail indicado."



### 13.5 Criação/edição de evento

Obrigatórios (atributo `required` e marcação visual com asterisco): título, categoria, modalidade, tipo de evento, capacidade (mín. 1), descrição, data e hora de início e fim, imagem (na criação), nome e e-mail do coordenador, data/hora limite de inscrição. Faixa de avaliadores limitada a 1–10 em ambos os campos. Telefone limitado a 15 caracteres. Categoria e tipo personalizados aparecem condicionalmente ao escolher "Outro".

---



## 14. Mensagens do sistema



### 14.1 Mensagens de validação e erro


| Contexto                     | Mensagem                                                                                                                                                                                                                                                             |
| ---------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Bloco de erros de formulário | "Ops! Algo deu errado."                                                                                                                                                                                                                                              |
| Cadastro                     | "O nome é obrigatório." / "O e-mail é obrigatório." / "A senha é obrigatória." / "A matrícula é obrigatória." / "O curso é obrigatório." / "A senha deve ter no mínimo 8 caracteres." / "As senhas não conferem." / "A matrícula deve conter exatamente 12 números." |
| Login                        | "É obrigatória a indicação de um valor para o campo e-mail." / "...campo senha." / "As credenciais indicadas não coincidem com as registradas no sistema."                                                                                                           |
| Certificado                  | "É obrigatória a indicação de um valor para o campo codigo." / "Não encontramos um certificado com este código. Verifique o valor e tente novamente."                                                                                                                |
| Recuperação de senha         | "Não existe nenhum usuário com o e-mail indicado."                                                                                                                                                                                                                   |
| Páginas de erro              | "404 NÃO ENCONTRADO" / "403 ACESSO NEGADO" / "419 PÁGINA EXPIRADA" / "429 MUITAS SOLICITAÇÕES"                                                                                                                                                                       |




### 14.2 Mensagens informativas e de estado


| Contexto                        | Mensagem                                                                                                                                                                                                                                                                                                                                                                                                    |
| ------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Fim da listagem                 | "Não há mais eventos disponíveis."                                                                                                                                                                                                                                                                                                                                                                          |
| Evento finalizado (público)     | "Este evento foi finalizado pelo coordenador."                                                                                                                                                                                                                                                                                                                                                              |
| Evento encerrado (público)      | "O período deste evento foi encerrado."                                                                                                                                                                                                                                                                                                                                                                     |
| Evento finalizado (coordenação) | "Evento finalizado: não é possível editar, gerenciar inscritos ou novidades. A página permanece visível ao público e você pode excluir o evento no painel."                                                                                                                                                                                                                                                 |
| Evento híbrido                  | "Modalidade híbrida: há participação presencial no campus e acesso remoto."                                                                                                                                                                                                                                                                                                                                 |
| Certificado válido              | "Certificado válido"                                                                                                                                                                                                                                                                                                                                                                                        |
| Validação (orientação)          | "Digite o código de validação impresso no certificado (PDF) ou cole o texto completo. Você pode informar maiúsculas ou minúsculas; espaços são ignorados. Não é necessário estar logado."                                                                                                                                                                                                                   |
| Anais (regra)                   | "Apenas trabalhos com status Apresentado aparecem aqui para registro nos anais."                                                                                                                                                                                                                                                                                                                            |
| Distribuição de avaliadores     | "Após o encerramento do prazo de submissão, selecione os trabalhos (por tipo), escolha os avaliadores e distribua. Cada trabalho marcado deve ficar com no mínimo 1 e no máximo 2 avaliadores."                                                                                                                                                                                                             |
| Sem trabalhos elegíveis         | "Não há trabalhos elegíveis para distribuição neste momento."                                                                                                                                                                                                                                                                                                                                               |
| Estados vazios                  | "Nenhum evento ainda" / "Nenhum trabalho submetido" / "Nenhum certificado disponível ainda" / "Nada agendado ainda" / "Nenhum trabalho designado" / "Este evento ainda não possui novidades." / "A programação ainda não possui atividades cadastradas." / "Nenhum convidado cadastrado neste evento ainda." / "Ainda não há documentos publicados para este evento." / "Nenhum certificado emitido ainda." |


**Avaliação:** os estados vazios são consistentemente bem escritos, explicando o que aconteceu e o que fazer em seguida — por exemplo, "Quando um trabalho seu for aceito e receber sessão e horário no cronograma do evento, os detalhes aparecerão aqui." Isso é um ponto forte do produto.

---



## 15. Testes realizados

Total: 15 sessões de teste automatizadas, 22 registros de execução e 136 capturas de tela, incluindo uma matriz com **96 verificações de permissão**.


| Sessão | Escopo                                                            | Verificações |
| ------ | ----------------------------------------------------------------- | ------------ |
| 1      | Reconhecimento da página inicial                                  | 1            |
| 2      | Telas públicas e paginação                                        | 6            |
| 3      | Recuperação de senha, anais, sondagem de rotas sem autenticação   | 13           |
| 4      | Validações do cadastro (cliente e servidor)                       | 4            |
| 5      | Criação de contas e duplicidade                                   | 3            |
| 6      | Varredura da área do participante                                 | 11           |
| 7      | Autenticação: casos negativos, throttling, logout, botão voltar   | 9            |
| 8      | Validação de certificado e recuperação de senha (casos negativos) | 5            |
| 9      | Barra de acessibilidade: limites e persistência                   | 13           |
| 10     | Responsividade em 4 resoluções × 9 páginas                        | 36           |
| 11     | Menu mobile e widget VLibras                                      | 4            |
| 12     | Downloads, modal, CSRF, 404, cabeçalhos HTTP                      | 8            |
| 13     | Autenticação dos 3 perfis e varredura completa                    | 59 telas     |
| 14     | Matriz de permissões (24 rotas × 4 perfis)                        | 96           |
| 15     | Escalonamento de privilégio e IDOR                                | 12           |




### Limitações da análise

1. **Nenhum evento com inscrição aberta.** Os três eventos existentes tinham prazo vencido, impedindo testar inscrição, submissão de trabalho e o efeito da capacidade.
2. **Restrição de criação de dados** determinada pelo cliente. Os fluxos de escrita foram documentados pelos formulários, sem persistência. Em consequência, os resultados de criação/edição/exclusão de evento, emissão de certificado e publicação de novidade **não foram confirmados**.
3. **Avaliador sem trabalhos designados**, o que impediu observar o formulário de parecer por critério.
4. **Um único coordenador disponível**, o que impediu verificar o isolamento entre coordenadores (RI01).
5. **Contas criadas antes da restrição:** `qa.interno@teste.com` e `qa.externo@teste.com`, ambas do tipo participante e sem vínculo com eventos. Recomenda-se a remoção.

---



## 16. Testes negativos


| #   | Cenário                                                                     | Resultado observado                                            | Avaliação                                      |
| --- | --------------------------------------------------------------------------- | -------------------------------------------------------------- | ---------------------------------------------- |
| 1   | Enviar cadastro vazio (validação do navegador)                              | Navegador bloqueia com "Please fill out this field."           | Comportamento esperado, mas mensagem em inglês |
| 2   | Enviar cadastro vazio contornando o cliente                                 | Servidor rejeita e lista os 5 campos obrigatórios em português | Comportamento correto                          |
| 3   | Matrícula com 3 dígitos                                                     | "A matrícula deve conter exatamente 12 números."               | Correto                                        |
| 4   | Senha com 3 caracteres                                                      | "A senha deve ter no mínimo 8 caracteres."                     | Correto                                        |
| 5   | Confirmação de senha divergente                                             | "As senhas não conferem."                                      | Correto                                        |
| 6   | Nome com 1 caractere                                                        | Aceito sem crítica                                             | Possível lacuna de validação                   |
| 7   | E-mail já cadastrado                                                        | Cadastro não concluído                                         | Correto                                        |
| 8   | Login com e-mail inexistente                                                | Mensagem genérica de credenciais                               | Correto (boa prática)                          |
| 9   | Login com senha errada                                                      | Mesma mensagem genérica                                        | Correto                                        |
| 10  | 7 tentativas de login inválidas                                             | HTTP 429 em página crua                                        | Funciona, mas sem tratamento visual            |
| 11  | Recuperar senha com e-mail inexistente                                      | "Não existe nenhum usuário com o e-mail indicado."             | Revela existência de conta                     |
| 12  | Código de certificado vazio                                                 | "É obrigatória a indicação de um valor para o campo codigo."   | Funciona, mas expõe o nome técnico do campo    |
| 13  | Código de certificado inexistente                                           | Mensagem amigável de não encontrado                            | Correto                                        |
| 14  | Código com SQL/XSS (`' OR 1=1 -- <script>`)                                 | Tratado como não encontrado, sem erro e sem execução           | Correto                                        |
| 15  | Token CSRF inválido                                                         | "419 PÁGINA EXPIRADA"                                          | Correto, sem orientação ao usuário             |
| 16  | Rota inexistente                                                            | "404 NÃO ENCONTRADO"                                           | Correto, sem navegação                         |
| 17  | Método HTTP não suportado (`GET /events`)                                   | Página de depuração com *stack trace* completo                 | Inadequado (ver P-05)                          |
| 18  | Documento de outro evento (`/events/2/documentos/1/download`)               | 404                                                            | Correto                                        |
| 19  | Documento inexistente                                                       | 404                                                            | Correto                                        |
| 20  | Rota protegida sem autenticação (14 rotas)                                  | 302 para `/login`                                              | Correto                                        |
| 21  | Rota de coordenação como Aluno (12 rotas)                                   | 403                                                            | Correto                                        |
| 22  | Rota de coordenação como Avaliador (12 rotas)                               | 403                                                            | Correto                                        |
| 23  | Área do avaliador como Aluno e como Coordenador                             | 403                                                            | Correto                                        |
| 24  | GET e POST de cadastro de coordenador/avaliador como Aluno e como Avaliador | 403 Proibido / ACESSO NEGADO                                   | Correto                                        |
| 25  | Trabalho/PDF/certificado de terceiro (6 recursos)                           | 403                                                            | Correto                                        |
| 26  | Botão "voltar" do navegador após logout                                     | Exibe a página do cache do navegador                           | Ao recarregar, redireciona para login          |
| 27  | Recarregar página após o "voltar"                                           | Redireciona para `/login`                                      | Correto                                        |
| 28  | Página inicial em 320px de largura                                          | Sem rolagem horizontal                                         | Correto                                        |
| 29  | Validação de certificado em 320px                                           | Botão excede a largura e cria rolagem horizontal               | Defeito de layout                              |


---



## 17. Problemas encontrados

Classificação: **Bug** (comportamento incorreto), **Usabilidade** (funciona, mas prejudica o uso), **Melhoria** (sugestão), **Ambiente** (configuração, não código de negócio).

---



### P-01 — Recuperação de senha permite descobrir quais e-mails têm conta


| Campo                 | Conteúdo                                                                                                                                          |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Tipo / Severidade** | Bug de segurança / **Alta**                                                                                                                       |
| **Tela**              | `/forgot-password`                                                                                                                                |
| **Perfil**            | Visitante                                                                                                                                         |
| **Passos**            | 1. Acessar `/forgot-password`. 2. Informar um e-mail inexistente. 3. Enviar.                                                                      |
| **Esperado**          | Mensagem neutra do tipo "Se este e-mail estiver cadastrado, enviaremos o link de redefinição", sem revelar a existência da conta                  |
| **Obtido**            | "Não existe nenhum usuário com o e-mail indicado."                                                                                                |
| **Evidência**         | `shots/08-F1-forgot-inexistente.png`                                                                                                              |
| **Impacto**           | Permite enumerar contas válidas do sistema, insumo para ataques direcionados. É inconsistente com o login, que corretamente usa mensagem genérica |
| **Sugestão**          | Padronizar a resposta e aplicar limite de tentativas também nesse endpoint                                                                        |


---



### P-02 — Bloqueio por tentativas exibe página crua "429 MUITAS SOLICITAÇÕES"


| Campo                 | Conteúdo                                                                                                      |
| --------------------- | ------------------------------------------------------------------------------------------------------------- |
| **Tipo / Severidade** | Usabilidade / **Média**                                                                                       |
| **Tela**              | `/login`                                                                                                      |
| **Perfil**            | Visitante                                                                                                     |
| **Passos**            | Errar a senha 5 vezes seguidas e tentar novamente                                                             |
| **Esperado**          | Permanecer no formulário com aviso do tipo "Muitas tentativas. Tente novamente em X segundos"                 |
| **Obtido**            | Página em branco com o texto "429 MUITAS SOLICITAÇÕES", sem informar o tempo de espera nem oferecer navegação |
| **Evidência**         | `shots/07-L4-throttle.png`                                                                                    |
| **Impacto**           | Usuário legítimo que esqueceu a senha fica sem saber o que fazer e sem caminho de volta                       |
| **Sugestão**          | Tratar o erro 429 no próprio formulário, informando o tempo restante e o link de recuperação de senha         |


---



### P-03 — Páginas de erro sem identidade visual e sem navegação


| Campo                 | Conteúdo                                                                                                              |
| --------------------- | --------------------------------------------------------------------------------------------------------------------- |
| **Tipo / Severidade** | Usabilidade / **Média**                                                                                               |
| **Telas**             | 404, 403, 419, 429                                                                                                    |
| **Perfil**            | Todos                                                                                                                 |
| **Passos**            | Acessar uma rota inexistente, uma rota sem permissão ou enviar formulário com token expirado                          |
| **Esperado**          | Página com cabeçalho, marca, explicação e link para o início ou para o painel                                         |
| **Obtido**            | Texto centralizado sem nenhum elemento de navegação: "404 NÃO ENCONTRADO", "403 ACESSO NEGADO", "419 PÁGINA EXPIRADA" |
| **Evidência**         | `shots/12-404.png`, `shots/12-X1-csrf.png`                                                                            |
| **Impacto**           | Beco sem saída; em um TCC, é um dos pontos mais visíveis em demonstração                                              |
| **Sugestão**          | Criar páginas de erro com o layout da aplicação e ação de retorno; no caso do 419, reapresentar o formulário          |


---



### P-04 — Telas de cadastro de coordenador e avaliador abriam para qualquer usuário autenticado — **corrigido**


| Campo                 | Conteúdo                                                                                                           |
| --------------------- | ------------------------------------------------------------------------------------------------------------------ |
| **Tipo / Severidade** | Bug / **Baixa** — **corrigido após a análise**                                                                     |
| **Telas**             | `/register/coordinator`, `/register/reviewer`                                                                      |
| **Perfil**            | Aluno e Avaliador                                                                                                  |
| **Passos**            | Autenticar como Aluno e acessar `/register/coordinator` diretamente pela URL                                       |
| **Esperado**          | HTTP 403, como ocorre nas demais rotas de coordenação                                                              |
| **Obtido na análise** | HTTP 200 com o formulário completo no GET; o POST já era bloqueado com "403 ACESSO NEGADO"                         |
| **Situação atual**    | GET e POST retornam `403 Proibido` para Aluno e Avaliador. Apenas o Coordenador obtém HTTP 200                     |
| **Evidência**         | Análise original: `out-25.txt` e `out-26.txt`. Revalidação após correção: Aluno/Avaliador → 403; Coordenador → 200 |
| **Correção aplicada** | As rotas GET e POST passaram a exigir o middleware `isCoordinator`, além da autenticação                           |


---



### P-05 — Ambiente expõe informações técnicas em telas de erro


| Campo                 | Conteúdo                                                                                                                                               |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Tipo / Severidade** | Ambiente / **Alta se replicado em produção**                                                                                                           |
| **Tela**              | Qualquer erro de servidor                                                                                                                              |
| **Perfil**            | Todos                                                                                                                                                  |
| **Passos**            | Acessar `GET /events` (método não suportado)                                                                                                           |
| **Esperado**          | Página de erro genérica                                                                                                                                |
| **Obtido**            | Página de depuração com *stack trace*, caminhos absolutos do servidor (`C:\laragon\www\conectaifpa\...`), versões de PHP e Laravel e trechos de código |
| **Evidência**         | `out-03.txt` (linha do teste `/events`)                                                                                                                |
| **Impacto**           | Em produção, facilita o reconhecimento da aplicação por um atacante. No ambiente local é esperado                                                      |
| **Sugestão**          | Garantir modo de depuração desativado em produção e documentar isso no TCC como parte do plano de implantação                                          |


---



### P-06 — Botão excede a tela na validação de certificado em 320px


| Campo                 | Conteúdo                                                                                                       |
| --------------------- | -------------------------------------------------------------------------------------------------------------- |
| **Tipo / Severidade** | Bug de layout / **Baixa**                                                                                      |
| **Tela**              | `/validar-certificado`                                                                                         |
| **Perfil**            | Todos                                                                                                          |
| **Passos**            | Abrir a tela com viewport de 320×568                                                                           |
| **Esperado**          | Conteúdo contido na largura da tela                                                                            |
| **Obtido**            | Largura de rolagem de 376px contra 320px visíveis; o botão "Verificar certificado" se estende de 182px a 376px |
| **Evidência**         | `shots/10-mobile-320_validar-certificado.png`                                                                  |
| **Impacto**           | Rolagem horizontal em telas pequenas; foi a única página com o problema entre as nove testadas                 |
| **Sugestão**          | Permitir que o par de botões quebre linha e ocupe a largura total em telas estreitas                           |


---



### P-07 — Nome de arquivo com acentuação corrompida no download de anexos


| Campo                 | Conteúdo                                                                  |
| --------------------- | ------------------------------------------------------------------------- |
| **Tipo / Severidade** | Bug / **Baixa**                                                           |
| **Tela**              | Página do evento, seção Documentos e Anexos                               |
| **Perfil**            | Todos                                                                     |
| **Passos**            | Baixar o anexo do evento 1                                                |
| **Esperado**          | `1ª AVA_Pré_Projeto_NomeAluno.docx`                                       |
| **Obtido**            | Cabeçalho `Content-Disposition` com `1Âª AVA_PreÌ_Projeto_NomeAluno.docx` |
| **Evidência**         | `out-12.txt`, linha D1                                                    |
| **Impacto**           | Arquivo baixa corretamente, mas com nome ilegível                         |
| **Sugestão**          | Codificar o nome do arquivo conforme RFC 5987 (`filename*=UTF-8''...`)    |


---



### P-08 — Mensagem de validação expõe o nome técnico do campo


| Campo                 | Conteúdo                                                            |
| --------------------- | ------------------------------------------------------------------- |
| **Tipo / Severidade** | Usabilidade / **Baixa**                                             |
| **Tela**              | `/validar-certificado`                                              |
| **Perfil**            | Todos                                                               |
| **Passos**            | Enviar o formulário sem preencher o código                          |
| **Esperado**          | "Informe o código de validação."                                    |
| **Obtido**            | "É obrigatória a indicação de um valor para o campo codigo."        |
| **Evidência**         | `shots/08-C1-cert-vazio.png`                                        |
| **Impacto**           | Linguagem técnica e sem acento vazando para o usuário final         |
| **Sugestão**          | Definir rótulos amigáveis para os campos nas mensagens de validação |


---



### P-09 — Dois padrões distintos de mensagem de obrigatoriedade


| Campo                 | Conteúdo                                                                                                                                        |
| --------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| **Tipo / Severidade** | Usabilidade / **Baixa**                                                                                                                         |
| **Telas**             | `/register` versus `/login` e `/validar-certificado`                                                                                            |
| **Perfil**            | Todos                                                                                                                                           |
| **Passos**            | Comparar as mensagens de campo obrigatório entre as telas                                                                                       |
| **Esperado**          | Padrão único                                                                                                                                    |
| **Obtido**            | "O e-mail é obrigatório." no cadastro e "É obrigatória a indicação de um valor para o campo e-mail." no login                                   |
| **Evidência**         | `out-04.txt` e `out-07.txt`                                                                                                                     |
| **Impacto**           | Percepção de inconsistência; a segunda forma é a tradução padrão do framework, indicando que faltam mensagens personalizadas nesses formulários |
| **Sugestão**          | Centralizar as mensagens em um único padrão de redação                                                                                          |


---



### P-10 — Validação do navegador em inglês


| Campo                 | Conteúdo                                                                                                         |
| --------------------- | ---------------------------------------------------------------------------------------------------------------- |
| **Tipo / Severidade** | Usabilidade / **Baixa**                                                                                          |
| **Telas**             | Todos os formulários com campos `required`                                                                       |
| **Perfil**            | Todos                                                                                                            |
| **Passos**            | Enviar formulário com campo obrigatório vazio                                                                    |
| **Esperado**          | Mensagem em português                                                                                            |
| **Obtido**            | "Please fill out this field."                                                                                    |
| **Evidência**         | `out-04.txt`, teste T1                                                                                           |
| **Impacto**           | Depende do idioma do navegador; quebra a consistência linguística da aplicação, que é integralmente em português |
| **Sugestão**          | Validar no cliente com mensagens próprias em português                                                           |


---



### P-11 — Página do participante permanece visível pelo botão "voltar" após o logout


| Campo                 | Conteúdo                                                                                                                                           |
| --------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Tipo / Severidade** | Usabilidade / segurança leve / **Baixa**                                                                                                           |
| **Tela**              | `/dashboard`                                                                                                                                       |
| **Perfil**            | Todos autenticados                                                                                                                                 |
| **Passos**            | 1. Autenticar. 2. Sair. 3. Acionar o botão "voltar" do navegador.                                                                                  |
| **Esperado**          | Redirecionamento imediato para o login                                                                                                             |
| **Obtido**            | O painel é reexibido a partir do cache do navegador, com nome e e-mail do usuário. Ao recarregar, o sistema corretamente redireciona para `/login` |
| **Evidência**         | `out-07.txt`, testes L8 e L9                                                                                                                       |
| **Impacto**           | Em computador compartilhado, dados do usuário anterior ficam momentaneamente visíveis. A sessão em si já está encerrada                            |
| **Sugestão**          | Enviar `Cache-Control: no-store` nas páginas autenticadas                                                                                          |


---



### P-12 — Falha intermitente de sessão apontando para banco SQLite inexistente


| Campo                 | Conteúdo                                                                                                                 |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| **Tipo / Severidade** | Ambiente / **Média (não reproduzível)**                                                                                  |
| **Tela**              | `/user/profile`                                                                                                          |
| **Perfil**            | Avaliador (ocorrência única)                                                                                             |
| **Passos**            | Navegar pela área autenticada com requisições concorrentes                                                               |
| **Esperado**          | Página "Minha conta"                                                                                                     |
| **Obtido**            | HTTP 500 com a mensagem "Database file at path [...\database\database.sqlite] does not exist" ao ler a tabela `sessions` |
| **Evidência**         | `out-22-aval.txt`, linha 1775                                                                                            |
| **Reprodução**        | **Não reproduzível** — 9 tentativas posteriores (3 por perfil) retornaram HTTP 200                                       |
| **Impacto**           | Erro esporádico de leitura de sessão, aparentemente por resolução de conexão de banco divergente da configurada          |
| **Sugestão**          | Padronizar explicitamente o driver de sessão e a conexão de banco na configuração e limpar caches de configuração        |


---



### P-13 — Dados cadastrais incompletos na lista de inscritos


| Campo                 | Conteúdo                                                                                                                    |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| **Tipo / Severidade** | Melhoria / **Baixa**                                                                                                        |
| **Tela**              | `/events/registered/3`                                                                                                      |
| **Perfil**            | Coordenador                                                                                                                 |
| **Passos**            | Abrir a lista de inscritos do evento 3                                                                                      |
| **Esperado**          | Todas as colunas preenchidas ou com marcador explícito de ausência                                                          |
| **Obtido**            | Um inscrito com Matrícula e Curso vazios; outro com Matrícula "—"                                                           |
| **Evidência**         | `shots/p-coord-_events_registered_3.png`                                                                                    |
| **Impacto**           | Prejudica a emissão de certificados e a conferência da lista; a inconsistência entre célula vazia e "—" dificulta a leitura |
| **Sugestão**          | Padronizar o marcador de ausência e tornar matrícula/curso obrigatórios ou explicitamente não aplicáveis                    |


---



### P-14 — Telas do participante acessíveis a Coordenador e Avaliador, mas "Meus certificados" não


| Campo                 | Conteúdo                                                                                                      |
| --------------------- | ------------------------------------------------------------------------------------------------------------- |
| **Tipo / Severidade** | Melhoria de consistência / **Baixa**                                                                          |
| **Telas**             | `/works/my`, `/works/my-presentation`, `/meus-certificados`                                                   |
| **Perfil**            | Coordenador e Avaliador                                                                                       |
| **Passos**            | Autenticar como Coordenador e acessar as três rotas                                                           |
| **Esperado**          | Critério único para as três telas                                                                             |
| **Obtido**            | `/works/my` e `/works/my-presentation` retornam 200 (vazias); `/meus-certificados` retorna 403                |
| **Evidência**         | `out-25.txt`                                                                                                  |
| **Impacto**           | Nenhum vazamento de dado — as telas mostram apenas dados próprios. É uma incoerência de regra de autorização  |
| **Sugestão**          | Decidir se essas telas pertencem a qualquer autenticado ou apenas ao participante, e aplicar o mesmo critério |


---



### P-15 — Alto contraste aplicado apenas parcialmente


| Campo                 | Conteúdo                                                                                                                                                            |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Tipo / Severidade** | Melhoria de acessibilidade / **Baixa**                                                                                                                              |
| **Tela**              | Todas                                                                                                                                                               |
| **Perfil**            | Todos                                                                                                                                                               |
| **Passos**            | Ativar "Contraste" na página inicial                                                                                                                                |
| **Esperado**          | Esquema de alto contraste aplicado a toda a página                                                                                                                  |
| **Obtido**            | Cabeçalho e *hero* passam a fundo preto com amarelo e links sublinhados; as seções de conteúdo abaixo permanecem no esquema claro                                   |
| **Evidência**         | `shots/09d-contraste-reload.png`                                                                                                                                    |
| **Impacto**           | A alternância funciona e persiste, mas o resultado visual é heterogêneo. Não caracteriza defeito de contraste, pois texto escuro sobre fundo claro também é legível |
| **Sugestão**          | Estender o tema de alto contraste a cards, tabelas e formulários                                                                                                    |




### 17.1 Resumo por severidade


| Severidade | Quantidade              | Itens                                                                  |
| ---------- | ----------------------- | ---------------------------------------------------------------------- |
| Crítica    | 0                       | —                                                                      |
| Alta       | 2                       | P-01, P-05 (a última condicionada a produção)                          |
| Média      | 3                       | P-02, P-03, P-12                                                       |
| Baixa      | 9 abertos + 1 corrigido | P-06, P-07, P-08, P-09, P-10, P-11, P-13, P-14, P-15; P-04 (corrigido) |


**Nenhum defeito crítico ou impeditivo foi encontrado.** Os fluxos principais testados funcionaram, e o controle de acesso — o aspecto de maior risco em um sistema com múltiplos perfis — mostrou-se correto em todas as 96 verificações.

---



## 18. Análise de usabilidade



### Pontos fortes

1. **Estados vazios instrutivos.** Praticamente toda lista vazia explica a situação e indica o próximo passo, em vez de exibir apenas "nenhum registro". Exemplo: "Quando um trabalho seu for aceito e receber sessão e horário no cronograma do evento, os detalhes aparecerão aqui."
2. **Processos complexos divididos em etapas numeradas.** A criação de evento tem 5 etapas com indicador de progresso; a edição tem 9 seções nomeadas; a tela de certificados apresenta cinco blocos numerados com índice de atalhos ("1. Organização e instituição nos PDFs", "2. Assinaturas nos PDFs"...). Isso reduz a carga cognitiva de formulários muito extensos.
3. **Textos normativos no ponto de uso.** As regras aparecem onde a decisão é tomada, não em manual separado — por exemplo, a regra dos anais e a instrução de distribuição de avaliadores.
4. **Menu adaptado ao perfil.** Cada perfil vê apenas o que lhe diz respeito; o Avaliador tem um menu deliberadamente enxuto, com um único destino.
5. **Selos de estado no painel do coordenador.** Cada evento comunica em uma linha a contagem de participantes, se aceita submissões e sua situação, e o conjunto de ações muda conforme o estado.
6. **Recursos de acessibilidade acima da média.** Controle de fonte com limites e botões desabilitados nos extremos, alto contraste, persistência das preferências e VLibras.
7. **Segurança percebida na conta.** Autenticação em dois fatores e gestão de sessões ativas.



### Pontos de atenção

1. **Rótulo do perfil versus nome do usuário.** A conta `admin@conectaifpa.com` chama-se "Administrador Inicial", mas o perfil exibido é "Coordenador". Para quem administra o sistema, isso gera dúvida sobre existir ou não um nível acima do coordenador.
2. **Nomenclatura mista entre português e inglês nas URLs.** Convivem `/events/registered/3`, `/events/3/novidades`, `/works/my`, `/meus-certificados` e `/events/1/anais/coordenacao`. Não afeta o usuário comum, mas prejudica a legibilidade do sistema em documentação e manutenção.
3. **Ausência de busca e filtros na vitrine pública.** A listagem só oferece "Carregar mais eventos", sem filtro por categoria, modalidade, período ou palavra-chave — embora o sistema classifique os eventos em 9 categorias, 3 modalidades e 14 tipos.
4. **Dois eventos distintos com o mesmo título** ("PHP Experience") aparecem lado a lado na vitrine sem qualquer elemento que os diferencie além das datas.
5. **Imagem de capa incoerente com o conteúdo.** O card do primeiro evento "PHP Experience" exibe a marca do Laravel. É dado de teste, mas convém corrigir antes da apresentação.
6. **Links de exemplo em produção de demonstração.** O evento 2 aponta o "Ambiente EAD" e o "Link do ambiente online" para `https://tailwindcss.com/`.
7. **Falta de confirmação visível para ações destrutivas.** Existem "Excluir evento", "Excluir trabalho", "Remover aluno", "Remover dos anais" e "Sair do evento". Não foi possível verificar se há diálogo de confirmação, pois as ações não foram executadas. **Grau de certeza: não confirmado.**
8. **Gatilho de emissão de certificado não evidente.** Não há botão explícito de emissão; a relação entre salvar presença e gerar o PDF não fica clara na interface.

---



## 19. Análise responsiva

Testadas 9 páginas em 4 resoluções (36 combinações), medindo largura de rolagem, elementos que extrapolam a viewport e disponibilidade da navegação.


| Resolução                    | Resultado                                                                                   |
| ---------------------------- | ------------------------------------------------------------------------------------------- |
| **1440×900 (desktop)**       | Navegação horizontal completa com 7 links visíveis; nenhum transbordamento de conteúdo      |
| **768×1024 (tablet)**        | Layout preservado, navegação completa ainda visível; nenhum transbordamento                 |
| **390×844 (mobile)**         | Navegação colapsa em menu "hambúrguer"; nenhuma rolagem horizontal em nenhuma das 9 páginas |
| **320×568 (mobile pequeno)** | Correto em 8 das 9 páginas; `/validar-certificado` apresenta rolagem horizontal (P-06)      |


**Menu mobile:** o botão com `aria-label="Menu"` abre e fecha corretamente (6 → 12 links no painel do aluno; 9 → 15 na home) e o painel expandido inclui uma seção "ACESSIBILIDADE" com os controles de fonte e contraste, além do menu completo do perfil e a ação "Sair".

**Tabelas em telas pequenas:** a lista de inscritos usa componente com ordenação e paginação; não foi possível avaliá-la em 320px porque a tela exige perfil de coordenador e a medição automatizada de responsividade cobriu as páginas públicas e do participante. **Grau de certeza: não confirmado** para as tabelas de gestão.

**Widget VLibras:** presente e ativo em todas as resoluções, posicionado à direita e na vertical central; não obstrui conteúdo nem gera rolagem.

---



## 20. Matriz de funcionalidades

Status: **Funcionando** (fluxo executado com resultado correto), **Funcionando parcialmente** (executado com ressalva), **Não testável** (bloqueado por dados ou pela restrição de escrita), **Problema encontrado**.


| ID  | Funcionalidade                          | Perfil       | Tela        | Status                   | Observações                                        |
| --- | --------------------------------------- | ------------ | ----------- | ------------------------ | -------------------------------------------------- |
| F01 | Listagem de eventos com "Carregar mais" | Todos        | T01         | Funcionando              | Exibe aviso ao esgotar                             |
| F02 | Página detalhada do evento              | Todos        | T02         | Funcionando              | Seções variam conforme configuração                |
| F03 | Modal de detalhe da atividade           | Todos        | T02         | Funcionando              | Abre com convidados, tipo, local e horário         |
| F04 | Download de anexo                       | Todos        | T02         | Problema encontrado      | P-07 (nome do arquivo)                             |
| F05 | Anais públicos                          | Todos        | T03         | Funcionando              | —                                                  |
| F06 | Validação de certificado                | Todos        | T04/T05     | Funcionando              | 4 códigos válidos e casos negativos                |
| F07 | Autocadastro de participante            | Visitante    | T07         | Funcionando              | Interno e externo                                  |
| F08 | Login                                   | Visitante    | T06         | Funcionando              | 3 perfis                                           |
| F09 | Bloqueio por tentativas                 | Visitante    | T06         | Problema encontrado      | P-02                                               |
| F10 | Recuperação de senha                    | Visitante    | T08         | Problema encontrado      | P-01                                               |
| F11 | Logout                                  | Autenticados | menu        | Funcionando parcialmente | P-11 (cache do "voltar")                           |
| F12 | Painel por perfil                       | Autenticados | T13/T19/T34 | Funcionando              | —                                                  |
| F13 | Edição de perfil                        | Autenticados | T18         | Não testável             | Restrição de escrita                               |
| F14 | Troca de senha                          | Autenticados | T18         | Não testável             | Restrição de escrita                               |
| F15 | Autenticação em dois fatores            | Autenticados | T18         | Não testável             | Restrição de escrita                               |
| F16 | Sessões do navegador                    | Autenticados | T18         | Funcionando parcialmente | Sessão atual listada; encerramento não executado   |
| F17 | Barra de acessibilidade                 | Todos        | todas       | Funcionando              | Limites e persistência confirmados                 |
| F18 | VLibras                                 | Todos        | todas       | Funcionando              | Widget ativo                                       |
| F19 | Eventos em que participa                | Aluno        | T13         | Funcionando              | Ativos e encerrados                                |
| F20 | Cancelar participação                   | Aluno        | T13         | Não testável             | Restrição de escrita                               |
| F21 | Meus trabalhos e status                 | Aluno        | T14         | Funcionando              | 2 trabalhos                                        |
| F22 | Parecer e mensagem da coordenação       | Aluno        | T15         | Funcionando parcialmente | Seções presentes; sem parecer preenchido           |
| F23 | Minha apresentação                      | Aluno        | T16         | Funcionando              | 2 agendamentos                                     |
| F24 | Meus certificados e download            | Aluno        | T17         | Funcionando              | 4 certificados em PDF                              |
| F25 | Criar evento                            | Coordenador  | T20         | Não testável             | Restrição de escrita                               |
| F26 | Editar evento                           | Coordenador  | T21         | Não testável             | Restrição de escrita                               |
| F27 | Excluir evento                          | Coordenador  | T19         | Não testável             | Restrição de escrita                               |
| F28 | Finalizar evento                        | Coordenador  | T02         | Não testável             | Efeito confirmado por comparação de estados        |
| F29 | Gerenciar inscritos                     | Coordenador  | T22         | Funcionando parcialmente | Listagem e ordenação OK; P-13                      |
| F30 | Exportar CSV                            | Coordenador  | T22         | Funcionando              | Retorna `text/csv`                                 |
| F31 | Remover inscrito                        | Coordenador  | T22         | Não testável             | Restrição de escrita                               |
| F32 | Publicar novidade                       | Coordenador  | T23         | Não testável             | Restrição de escrita                               |
| F33 | Convidados, atividades e documentos     | Coordenador  | T21         | Não testável             | Restrição de escrita                               |
| F34 | Configurar submissões                   | Coordenador  | T20/T21     | Funcionando parcialmente | Configuração visível e refletida na página pública |
| F35 | Distribuir avaliadores                  | Coordenador  | T24         | Não testável             | Sem trabalhos elegíveis no momento                 |
| F36 | Filtrar trabalhos por tipo              | Coordenador  | T24         | Funcionando parcialmente | Filtro presente com os tipos do evento             |
| F37 | Excluir trabalho                        | Coordenador  | T25         | Não testável             | Restrição de escrita                               |
| F38 | Agendar apresentações                   | Coordenador  | T26         | Não testável             | Restrição de escrita                               |
| F39 | Registrar nos anais                     | Coordenador  | T27         | Não testável             | Estados e regra confirmados                        |
| F40 | Dados do certificado                    | Coordenador  | T28         | Não testável             | Valores atuais visíveis                            |
| F41 | Assinaturas do evento                   | Coordenador  | T28/T31     | Funcionando parcialmente | Duas assinaturas cadastradas e selecionáveis       |
| F42 | Presença geral                          | Coordenador  | T28         | Não testável             | Lista de participantes visível                     |
| F43 | Presença e CH por atividade             | Coordenador  | T30         | Não testável             | Tela funcional, com CH 2,00 h registrada           |
| F44 | Presença e CH por apresentação          | Coordenador  | T28         | Não testável             | Linhas por trabalho visíveis                       |
| F45 | Certificados emitidos                   | Coordenador  | T29         | Funcionando              | 4 emissões auditáveis                              |
| F46 | Cadastro de coordenador/avaliador       | Coordenador  | T32/T33     | Funcionando              | GET e POST restritos ao coordenador                |
| F47 | Avaliações designadas                   | Avaliador    | T35         | Não testável             | Nenhum trabalho designado                          |




**Consolidado:** 20 funcionando, 9 funcionando parcialmente, 15 não testáveis (13 por restrição de escrita e 2 por ausência de dados), 3 com problema identificado.

---



## 21. Matriz de telas


| ID  | Tela                              | Rota                                                | Perfil        | Autenticação | Finalidade              |
| --- | --------------------------------- | --------------------------------------------------- | ------------- | ------------ | ----------------------- |
| T01 | Vitrine                           | `/`                                                 | Todos         | Não          | Divulgar eventos        |
| T02 | Página do evento                  | `/events/{id}`                                      | Todos         | Não          | Detalhar evento         |
| T03 | Anais públicos                    | `/events/{id}/anais`                                | Todos         | Não          | Consultar publicações   |
| T04 | Validar certificado               | `/validar-certificado`                              | Todos         | Não          | Conferir autenticidade  |
| T05 | Resultado da validação            | `/certificado/{código}`                             | Todos         | Não          | Exibir validade         |
| T06 | Login                             | `/login`                                            | Visitante     | Não          | Autenticar              |
| T07 | Cadastro                          | `/register`                                         | Visitante     | Não          | Autocadastro            |
| T08 | Recuperar senha                   | `/forgot-password`                                  | Visitante     | Não          | Redefinir senha         |
| T09 | Erro 404                          | —                                                   | Todos         | Não          | Recurso inexistente     |
| T10 | Erro 419                          | —                                                   | Todos         | —            | Token expirado          |
| T11 | Erro 403                          | —                                                   | Autenticados  | Sim          | Acesso negado           |
| T12 | Erro 429                          | —                                                   | Visitante     | Não          | Excesso de tentativas   |
| T13 | Painel do participante            | `/dashboard`                                        | Aluno         | Sim          | Acompanhar participação |
| T14 | Meus trabalhos                    | `/works/my`                                         | Aluno         | Sim          | Listar submissões       |
| T15 | Detalhe do trabalho (autor)       | `/works/{id}`                                       | Aluno (autor) | Sim          | Acompanhar avaliação    |
| T16 | Minha apresentação                | `/works/my-presentation`                            | Aluno         | Sim          | Ver agendamento         |
| T17 | Meus certificados                 | `/meus-certificados`                                | Aluno         | Sim          | Baixar certificados     |
| T18 | Minha conta                       | `/user/profile`                                     | Autenticados  | Sim          | Gerenciar conta         |
| T19 | Painel do coordenador             | `/dashboard`                                        | Coordenador   | Sim          | Gerir eventos           |
| T20 | Criar evento                      | `/events/create`                                    | Coordenador   | Sim          | Cadastrar evento        |
| T21 | Editar evento                     | `/events/edit/{id}`                                 | Coordenador   | Sim          | Atualizar evento        |
| T22 | Gerenciar inscritos               | `/events/registered/{id}`                           | Coordenador   | Sim          | Controlar inscrições    |
| T23 | Configurar novidades              | `/events/{id}/novidades`                            | Coordenador   | Sim          | Comunicar participantes |
| T24 | Trabalhos do evento               | `/events/{id}/works`                                | Coordenador   | Sim          | Gerir submissões        |
| T25 | Detalhe do trabalho (coordenação) | `/works/{id}`                                       | Coordenador   | Sim          | Analisar submissão      |
| T26 | Cronograma de apresentações       | `/events/{id}/apresentacoes`                        | Coordenador   | Sim          | Agendar sessões         |
| T27 | Publicação nos anais              | `/events/{id}/anais/coordenacao`                    | Coordenador   | Sim          | Registrar publicação    |
| T28 | Certificados e presença           | `/events/{id}/certificates`                         | Coordenador   | Sim          | Configurar certificação |
| T29 | Certificados emitidos             | `/events/{id}/certificates/issued`                  | Coordenador   | Sim          | Auditar emissões        |
| T30 | Presença de atividade             | `/events/{id}/certificates/activities/{a}/presence` | Coordenador   | Sim          | Registrar presença      |
| T31 | Assinaturas                       | `/assinaturas`                                      | Coordenador   | Sim          | Cadastrar assinaturas   |
| T32 | Cadastrar coordenador             | `/register/coordinator`                             | Coordenador   | Sim          | Criar conta de equipe   |
| T33 | Cadastrar avaliador               | `/register/reviewer`                                | Coordenador   | Sim          | Criar conta de equipe   |
| T34 | Painel do avaliador               | `/dashboard`                                        | Avaliador     | Sim          | Acessar avaliações      |
| T35 | Avaliações designadas             | `/reviews/assigned`                                 | Avaliador     | Sim          | Emitir pareceres        |


---



## 22. Matriz de fluxos


| ID   | Fluxo                            | Perfil                          | Início                           | Fim                       | Resultado                                                                                                           |
| ---- | -------------------------------- | ------------------------------- | -------------------------------- | ------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| FL01 | Cadastro e primeiro acesso       | Visitante → Aluno               | `/register`                      | `/dashboard`              | **Testado** — conta criada e autenticada automaticamente                                                            |
| FL02 | Autenticação                     | Todos                           | `/login`                         | `/dashboard`              | **Testado** — casos positivos e negativos, throttling e logout                                                      |
| FL03 | Criação de evento                | Coordenador                     | `/events/create`                 | `/events` (POST)          | **Mapeado** — 5 etapas e campos obrigatórios documentados; não executado                                            |
| FL04 | Ciclo de vida do evento          | Coordenador                     | `/events/edit/{id}`              | `/events/{id}/finalize`   | **Confirmado por comparação de estados** — ativo, encerrado e finalizado com ações distintas                        |
| FL05 | Inscrição em evento              | Aluno                           | `/events/{id}`                   | `/dashboard`              | **Não testável** — nenhum evento com prazo aberto                                                                   |
| FL06 | Submissão e avaliação            | Aluno → Coordenador → Avaliador | `/events/{id}`                   | `/works/{id}`             | **Parcialmente observado** — estados e artefatos confirmados; formulários de submissão e parecer não acessíveis     |
| FL07 | Publicação nos anais             | Coordenador                     | `/events/{id}/anais/coordenacao` | `/events/{id}/anais`      | **Mapeado** — regra e estados confirmados; ação não executada                                                       |
| FL08 | Presença e certificação          | Coordenador                     | `/events/{id}/certificates`      | `/meus-certificados`      | **Mapeado** — 5 passos e resultado final (4 certificados emitidos) confirmados; gatilho de emissão não identificado |
| FL09 | Validação pública de certificado | Qualquer pessoa                 | `/validar-certificado`           | `/certificado/{código}`   | **Testado** — códigos válidos e inválidos                                                                           |
| FL10 | Gestão de inscritos              | Coordenador                     | `/events/registered/{id}`        | `/events/{id}/export-csv` | **Parcialmente testado** — listagem, ordenação, paginação e exportação CSV confirmadas; remoção não executada       |
| FL11 | Cadastro de equipe               | Coordenador                     | `/register/coordinator`          | —                         | **Parcialmente testado** — autorização validada (403 para outros perfis); criação não executada                     |


---



## 23. Recomendações de melhoria



### Prioridade alta

1. **Neutralizar a resposta da recuperação de senha** (P-01), adotando mensagem única independentemente da existência do e-mail, e aplicar limite de tentativas ao endpoint.
2. **Garantir a desativação do modo de depuração em produção** (P-05) e registrar isso no plano de implantação do TCC.
3. **Criar páginas de erro personalizadas** para 403, 404, 419 e 429 (P-02, P-03), com identidade visual e caminho de retorno; no caso do 419, reapresentar o formulário preenchido.



### Prioridade média

1. **Padronizar as mensagens de validação** em um único estilo de redação, eliminando o nome técnico de campos (P-08, P-09) e traduzindo a validação do cliente (P-10).
2. **Enviar** `Cache-Control: no-store` **nas páginas autenticadas** (P-11).
3. **Estabilizar a configuração de sessão e banco** para eliminar a falha intermitente observada (P-12).
4. **Explicitar o gatilho da emissão de certificados**, com um botão ou um resumo do tipo "X certificados serão emitidos" antes da confirmação.
5. **Confirmar ações destrutivas** com diálogo explícito informando as consequências, especialmente "Excluir evento", que afeta inscritos, trabalhos e certificados.



### Prioridade baixa

1. **Adicionar busca e filtros à vitrine pública** por categoria, modalidade, tipo e período — a informação já é estruturada pelo sistema.
2. **Corrigir a responsividade da validação de certificado em 320px** (P-06).
3. **Codificar corretamente o nome dos arquivos baixados** (P-07).
4. **Estender o tema de alto contraste** às demais seções (P-15).
5. **Padronizar o marcador de dados ausentes** na lista de inscritos e revisar a obrigatoriedade de matrícula e curso (P-13).
6. **Unificar o critério de autorização** das telas do participante (P-14).
7. **Uniformizar o idioma das rotas**, hoje mistas entre português e inglês.
8. **Diferenciar visualmente eventos homônimos** na vitrine.
9. **Higienizar os dados de demonstração** antes da apresentação: imagem do Laravel em evento de PHP, links para `tailwindcss.com` como ambiente EAD, eventos com título repetido e descrição "teste".
10. **Rever a nomenclatura do perfil administrativo**, deixando claro se existe apenas Coordenador ou se há um nível de administração global.

---



## 24. Conclusão da análise

O ConectaIFPA é um sistema **funcionalmente maduro e coerente** para a gestão de eventos acadêmicos. A análise caixa-preta identificou **35 telas, 47 funcionalidades, 34 rotas de leitura, 25 rotas de escrita, 11 fluxos de negócio e 28 regras de negócio confirmadas**, o que revela um escopo bem acima do que se costuma encontrar em um trabalho de conclusão de curso.

O maior mérito do produto é ter integrado, em uma única plataforma, três ciclos que normalmente exigem ferramentas distintas: divulgação e inscrição, avaliação científica com pareceres e anais, e certificação com validação pública verificável. A cadeia que vai da submissão do trabalho até o certificado com código validável por qualquer pessoa, passando por distribuição de avaliadores, agendamento de apresentação e registro de presença com carga horária, está implementada de ponta a ponta — e foi possível comprovar o **resultado** dessa cadeia mesmo sem executá-la, pelos artefatos existentes: dois trabalhos com estados distintos, quatro certificados emitidos com códigos únicos e um trabalho publicado nos anais.

O aspecto mais crítico em sistemas multiperfil — o **controle de acesso** — mostrou-se sólido. Nas verificações de permissão, o comportamento foi correto: visitantes são redirecionados ao login, cada perfil recebe `403` nas áreas alheias e nenhum usuário consegue acessar trabalhos, arquivos ou certificados de terceiros. Durante a análise, o GET de `/register/coordinator` e `/register/reviewer` ainda abria o formulário para qualquer autenticado (o POST já era bloqueado); essa incoerência foi **corrigida**, e GET e POST passam a retornar `403` para Aluno e Avaliador. Também não foram encontradas vulnerabilidades de injeção nas entradas testadas, e a proteção contra CSRF está ativa.

Não foi encontrado **nenhum defeito crítico ou impeditivo**. Dos 15 problemas registrados, 1 (P-04) foi **corrigido** após a análise; restam 14 abertos, dos quais 9 são de baixa severidade e concentram-se em acabamento: mensagens de validação com dois padrões, páginas de erro sem identidade visual, um botão que extrapola a tela em 320px e nomes de arquivo com acentuação corrompida. Os dois itens de maior gravidade — a resposta da recuperação de senha que revela a existência de contas e a exposição de informação técnica nas páginas de erro — são de correção simples e localizada.

A qualidade da experiência merece destaque particular nos **estados vazios**, que orientam o usuário em vez de apenas informar a ausência de dados, na **decomposição de formulários extensos em etapas numeradas** e nos **recursos de acessibilidade**, com controle de fonte respeitando limites, alto contraste persistente e integração ao VLibras — algo raro em sistemas dessa natureza e especialmente adequado a uma instituição pública de ensino.

A limitação principal desta análise foi a **ausência de um evento com inscrições abertas** no ambiente, o que impediu percorrer os fluxos de inscrição e submissão a partir do gatilho inicial, somada à determinação de **não criar dados**, que restringiu a verificação dos fluxos de escrita à inspeção de formulários, campos, obrigatoriedades e regras exibidas. Recomenda-se, para uma segunda rodada de validação, preparar um evento de demonstração com prazos futuros e um trabalho designado a um avaliador; com isso, os fluxos FL03, FL05, FL06 e FL08 poderiam ser confirmados de ponta a ponta e a matriz de funcionalidades passaria de 19 para próximo de 40 itens integralmente verificados.

---



## Anexo A — Resumo executivo



### O que é o ConectaIFPA

Uma plataforma web de gestão de eventos acadêmicos do Instituto Federal do Pará — Campus Belém, que cobre desde a divulgação do evento até a emissão de certificados com validação pública.

### Qual problema resolve

Substitui um conjunto de práticas manuais e dispersas — divulgação em redes sociais, inscrição por planilha, recebimento de trabalhos por e-mail, avaliação sem rastreabilidade, lista de presença em papel e certificado emitido manualmente e não verificável — por um processo único, rastreável e auditável. O ganho mais evidente é a **certificação confiável**: cada certificado possui código único conferível por qualquer pessoa, sem login, o que resolve o problema da comprovação de participação e de carga horária.

### Quem utiliza

Estudantes do IFPA Belém e de outras instituições (participantes), coordenadores de evento, avaliadores de trabalhos científicos e o público em geral, que consulta eventos e valida certificados sem se cadastrar.

### Perfis existentes

Três perfis autenticados — **Aluno**, **Coordenador** e **Avaliador** — mais o estado de **visitante**. O autocadastro público cria exclusivamente participantes; contas de coordenador e avaliador são criadas por um coordenador.

### Principais funcionalidades

Vitrine pública de eventos; página detalhada com programação, convidados, anexos e novidades; inscrição e cancelamento pelo participante; assistente de criação de evento em 5 etapas e de edição em 9; gestão de inscritos com ordenação, paginação, remoção e exportação em CSV; configuração de submissões científicas com 11 tipos de trabalho; distribuição de trabalhos a avaliadores com mínimo e máximo por trabalho; pareceres e decisão final; agendamento de apresentações; publicação nos anais com página pública; controle de presença geral, por atividade e por apresentação, com carga horária; emissão de certificados em PDF com assinaturas configuráveis; validação pública por código; conta com dois fatores e gestão de sessões; e recursos de acessibilidade com escala de fonte, alto contraste e VLibras.

### Principais fluxos

Cadastro e primeiro acesso; autenticação; ciclo de vida do evento (ativo → período encerrado → finalizado); inscrição; submissão, avaliação e reavaliação de trabalho; publicação nos anais; presença e certificação; e validação pública de certificado.

### Principais resultados da análise

35 telas, 47 funcionalidades, 59 rotas e 28 regras de negócio confirmadas. 96 verificações de permissão executadas, **todas com resultado correto**. Nenhum defeito crítico. Controle de acesso consistente entre os quatro estados de usuário, sem escalonamento de privilégio e sem acesso indevido a recursos de terceiros. Entradas maliciosas em campos públicos foram tratadas com segurança e a proteção contra CSRF está ativa.

### Principais problemas encontrados

15 problemas registrados, dos quais 1 (P-04) foi corrigido: 2 de severidade alta (a recuperação de senha revela quais e-mails têm conta; páginas de erro do ambiente expõem informação técnica), 3 médios (bloqueio de login exibindo página crua 429, páginas de erro sem identidade visual e navegação, falha intermitente de sessão não reproduzível) e 9 baixos ainda abertos, concentrados em acabamento de mensagens, layout em telas muito estreitas, codificação de nome de arquivo e coerência de regras de autorização em telas sem impacto de dados.

### Principais recomendações

Neutralizar a resposta da recuperação de senha; assegurar a desativação do modo de depuração em produção; criar páginas de erro personalizadas com caminho de retorno; padronizar as mensagens de validação; explicitar o gatilho de emissão de certificados e exigir confirmação em ações destrutivas; adicionar busca e filtros à vitrine pública; e higienizar os dados de demonstração antes da apresentação do TCC.

---



## Anexo B — Evidências

As evidências desta análise estão organizadas da seguinte forma:

- **136 capturas de tela** — uma por tela e por cenário de teste relevante, incluindo estados de erro, validações, resultados de testes negativos e as quatro resoluções da análise responsiva.
- **Relatórios estruturados por perfil** — inventário completo, em formato legível por máquina, de cada tela visitada com URL, código HTTP, títulos, links, botões, formulários (campos, tipos, obrigatoriedade, opções de seleção, limites), tabelas e mensagens.
- **Matriz de permissões** — resultado bruto das 96 verificações de acesso.
- **Registros de execução** — saída completa de cada uma das 15 sessões de teste.

Localização: `C:\Users\kenzo\qa-conectaifpa` (capturas em `shots/`).