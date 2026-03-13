# ConectaIFPA – Plataforma de Gerenciamento de Eventos Acadêmicos

## 📌 Sobre o Projeto

O **ConectaIFPA** é uma aplicação web desenvolvida como Trabalho de Conclusão de Curso (TCC) do curso de Tecnologia em Análise e Desenvolvimento de Sistemas do Instituto Federal do Pará (IFPA) – Campus Belém.

O sistema foi criado com o objetivo de centralizar a criação, divulgação e gerenciamento de eventos acadêmicos, substituindo práticas descentralizadas como o uso de plataformas externas e divulgação por cartazes físicos dentro da instituição.

A aplicação encontra-se publicada em ambiente de produção:

🔗 https://conectaifpa.laravel.cloud/

---

## 🎯 Problema

Atualmente, o IFPA Campus Belém não possui um sistema institucional próprio para gerenciamento de eventos acadêmicos. Professores recorrem a plataformas externas ou utilizam meios físicos de divulgação, como cartazes, o que gera:

- Falta de centralização das informações
- Dificuldade no controle de inscritos
- Processos administrativos manuais
- Baixa organização histórica dos eventos

Diante disso, o projeto busca responder:

**Como o desenvolvimento de uma aplicação web pode contribuir para centralizar e otimizar o gerenciamento de eventos acadêmicos no IFPA Campus Belém?**

---

## 🚀 Objetivo

Desenvolver e implantar uma aplicação web capaz de:

- Permitir que professores criem, editem e excluam eventos
- Gerenciar inscrições de alunos
- Controlar níveis de acesso (professor/coordenador e aluno)
- Centralizar informações institucionais
- Disponibilizar a plataforma online em ambiente de nuvem

---

## 👥 Perfis de Usuário

### 👨‍🏫 Professor / Coordenador
- Criar eventos
- Editar e excluir eventos próprios
- Gerenciar inscritos
- Criar outros coordenadores
- Controlar vagas e prazos

### 👨‍🎓 Aluno
- Criar conta
- Realizar login
- Visualizar eventos disponíveis
- Inscrever-se em eventos

---

## 🛠 Tecnologias Utilizadas

### 🔹 Backend
- PHP
- Laravel (arquitetura MVC)

### 🔹 Frontend
- Blade (Template Engine)
- Tailwind CSS
- Bootstrap
- DataTables
- SweetAlert
- Vite

### 🔹 Banco de Dados
- Supabase (PostgreSQL)

### 🔹 Armazenamento de Arquivos
- Supabase Storage (Bucket)
  - Utilizado para armazenar imagens dos eventos
  - Separação entre aplicação e arquivos estáticos
  - Maior organização e segurança

### 🔹 Infraestrutura e Deploy
- Laravel Cloud (Hospedagem em Nuvem)

### 🔹 Controle de Versão
- Git / GitHub

---

## 🏗 Arquitetura do Sistema

O projeto segue o padrão arquitetural **MVC (Model-View-Controller)**:

- **Models:** Representam as entidades do sistema (Usuário, Evento, Inscrição, etc.)
- **Controllers:** Gerenciam regras de negócio e requisições HTTP
- **Views:** Interfaces desenvolvidas com Blade
- **Banco de Dados:** PostgreSQL gerenciado via Supabase
- **Storage:** Imagens armazenadas em bucket externo (Supabase Storage)
- **Deploy:** Aplicação hospedada no Laravel Cloud

Essa arquitetura garante:
- Separação de responsabilidades
- Organização do código
- Escalabilidade
- Manutenção facilitada

---

## 📂 Funcionalidades Principais

- Cadastro e autenticação de usuários
- Controle de permissões por nível de acesso
- Criação, edição e exclusão de eventos
- Upload de imagem para eventos
- Gerenciamento de inscrições
- Interface responsiva (desktop e mobile)
- Publicação em ambiente de produção

---

## ☁ Infraestrutura

O sistema utiliza uma arquitetura baseada em nuvem:

- Aplicação hospedada no Laravel Cloud
- Banco de dados PostgreSQL gerenciado pelo Supabase
- Armazenamento de imagens em bucket seguro no Supabase Storage

Essa abordagem permite:
- Maior disponibilidade
- Melhor organização estrutural
- Separação entre aplicação e armazenamento de mídia
- Escalabilidade futura

---

## 📈 Resultados Esperados

Com a implementação do ConectaIFPA, espera-se:

- Centralizar a gestão de eventos acadêmicos
- Reduzir processos manuais
- Melhorar a organização institucional
- Modernizar a divulgação de eventos
- Facilitar o controle de inscrições

---

## 🎓 Autor

**Kenzo Ribeiro Toda**  
Curso: Tecnologia em Análise e Desenvolvimento de Sistemas  
Instituição: Instituto Federal do Pará – Campus Belém  

---

## 📄 Observação

Este projeto foi desenvolvido como parte do Trabalho de Conclusão de Curso (TCC), com foco em transformação digital e modernização de processos acadêmicos por meio de tecnologias web e infraestrutura em nuvem.
