# ⬡ DevTrack

**DevTrack** é uma ferramenta de gerenciamento de tarefas e atividades de desenvolvimento, construída com PHP + SQLite. Oferece um quadro Kanban interativo, relatórios, controle de tempo e organização por projetos, tipos e tags — tudo sem dependências externas complexas, rodando diretamente no seu servidor local (XAMPP, Laragon, etc.).

---

## Sumário

- [Funcionalidades](#funcionalidades)
- [Tecnologias](#tecnologias)
- [Requisitos](#requisitos)
- [Instalação](#instalação)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [Como Usar](#como-usar)
- [API Interna](#api-interna)
- [Banco de Dados](#banco-de-dados)
- [Configurações](#configurações)
- [Capturas de Tela](#capturas-de-tela)
- [Contribuindo](#contribuindo)
- [Licença](#licença)

---

## Funcionalidades

### Quadro Kanban
- Colunas: **Backlog · Em Andamento · Em Revisão · Concluído · Bloqueado**
- Drag-and-drop para mover cards entre colunas
- Limite de WIP (Work In Progress) configurável por coluna com alerta visual
- Contador de cards por coluna atualizado em tempo real
- Criação rápida de card direto na coluna desejada

### Gestão de Atividades
- Título, descrição e solução técnica
- Tipo (Bug, Feature, Hotfix, Refactor, Deploy, Reunião, Documentação, Outro — personalizável)
- Prioridade: Urgente, Alta, Média, Baixa
- Projeto associado com cor identificadora
- Tags múltiplas com cores customizáveis
- Link externo (PR, ticket, repositório)
- Datas de início e fim
- Tempo gasto (formato `2h30m` ou `45m`)
- Campo "Solicitado por"
- Histórico completo de movimentações da atividade

### Visualizações
- **Board** — quadro Kanban com cards visuais e drag-and-drop
- **Tabela** — listagem compacta com ordenação e todas as informações

### Filtros e Busca
- Busca textual por título em tempo real
- Filtro por projeto e tipo na barra de navegação
- Painel de filtros avançados: prioridade, intervalo de datas
- Indicador visual de filtros ativos com botão para limpar tudo

### Relatórios
- Período configurável (de / até)
- Filtro por projeto, tipo e coluna
- Resumo total: quantidade de atividades e tempo gasto
- Distribuição por tipo com badges coloridos
- Listagem detalhada exportável

### Configurações
- Gerenciamento de **Projetos** (nome + cor)
- Gerenciamento de **Tipos** (nome + cor do texto + cor de fundo)
- Gerenciamento de **Tags** (nome + cor)
- Definição de limites **WIP** por coluna

---

## Tecnologias

| Camada      | Tecnologia                                      |
|-------------|-------------------------------------------------|
| Backend     | PHP 8.1+ (sem frameworks)                       |
| Banco       | SQLite 3 via PDO                                |
| Frontend    | HTML5 + JavaScript (Vanilla ES2020+)            |
| Estilização | Tailwind CSS (Play CDN) + CSS customizado       |
| Fontes      | Inter + Fira Code (Google Fonts)                |

> Não há dependências de Composer, npm ou build tools. O projeto roda "as-is".

---

## Requisitos

- PHP **8.1** ou superior
- Extensão **PDO** habilitada
- Extensão **pdo_sqlite** habilitada
- Servidor web local: **Laragon**, XAMPP, WAMP ou similar
- Acesso à internet apenas para carregar Tailwind CDN e Google Fonts (opcional em ambientes offline — ver [configuração offline](#configurações))

---

## Instalação

### 1. Clone o repositório

```bash
git clone https://github.com/AlexCamposDosSantos/DevTrack.git
cd DevTrack
```

### 2. Configure o servidor

Coloque a pasta `DevTrack` dentro do diretório raiz do seu servidor web:

- **Laragon:** `C:\laragon\www\DevTrack`
- **XAMPP:** `C:\xampp\htdocs\DevTrack`

### 3. Primeiro acesso

Acesse no navegador:

```
http://localhost/DevTrack
```

O sistema detecta automaticamente se o banco ainda não foi criado e inicializa todas as tabelas com os dados padrão. Não é necessário executar nenhum script SQL manualmente.

> **Alternativa:** acesse `http://localhost/DevTrack/install.php` para forçar a inicialização com diagnóstico visual.

### Resetar o banco (voltar ao estado inicial)

Simplesmente delete o arquivo `data/devtrack.sqlite`. Na próxima requisição o banco será recriado do zero.

```bash
rm data/devtrack.sqlite
```

---

## Estrutura do Projeto

```
DevTrack/
├── index.php          # Página principal — quadro Kanban
├── api.php            # API REST interna (JSON)
├── config.php         # Página de configurações
├── relatorio.php      # Página de relatórios
├── install.php        # Instalação / diagnóstico
├── db.php             # Camada de acesso ao banco (PDO + migrations)
├── _tailwind.php      # Include do Tailwind CDN + tema + componentes CSS
├── data/
│   └── devtrack.sqlite    # Banco SQLite (gerado automaticamente)
└── assets/
    ├── css/
    │   └── app.css        # Estilos complementares
    └── js/
        └── kanban.js      # Toda a lógica frontend (drag, modal, API calls)
```

---

## Como Usar

### Criando uma atividade

1. Clique em **+ Nova** na barra de navegação ou no botão **+** de qualquer coluna
2. Preencha o título (obrigatório) e os campos desejados
3. Pressione **Salvar** ou use o atalho **Ctrl + Enter**

### Movendo cards

- **Arraste** o card para a coluna desejada
- Ou abra o card e altere o campo **Coluna** no formulário

### Acompanhando o histórico

Abra qualquer card e clique na aba **Histórico** para ver todas as movimentações com data, hora e detalhes.

### Atalhos de teclado

| Atalho      | Ação                         |
|-------------|------------------------------|
| `Ctrl+Enter`| Salvar card no modal         |
| `Esc`       | Fechar modal                 |

---

## API Interna

Todos os dados são manipulados via `api.php` com requisições `GET` (leitura) e `POST` (escrita). Parâmetro de roteamento: `?action=<ação>`.

### Atividades

| Action           | Método | Descrição                              |
|------------------|--------|----------------------------------------|
| `listar`         | GET    | Lista todas as atividades com filtros  |
| `criar`          | POST   | Cria nova atividade                    |
| `atualizar`      | POST   | Atualiza campos de uma atividade       |
| `mover`          | POST   | Move para outra coluna (registra histórico) |
| `reordenar`      | POST   | Reordena cards dentro de uma coluna    |
| `deletar`        | POST   | Remove atividade e seu histórico       |
| `historico`      | GET    | Retorna histórico de uma atividade     |

### Projetos / Tipos / Tags

| Action              | Método | Descrição                  |
|---------------------|--------|----------------------------|
| `projetos`          | GET    | Lista projetos             |
| `criar_projeto`     | POST   | Cria projeto               |
| `atualizar_projeto` | POST   | Edita projeto              |
| `deletar_projeto`   | POST   | Remove projeto             |
| `tipos`             | GET    | Lista tipos                |
| `criar_tipo`        | POST   | Cria tipo                  |
| `atualizar_tipo`    | POST   | Edita tipo                 |
| `deletar_tipo`      | POST   | Remove tipo                |
| `tags`              | GET    | Lista tags                 |
| `criar_tag`         | POST   | Cria tag                   |
| `atualizar_tag`     | POST   | Edita tag                  |
| `deletar_tag`       | POST   | Remove tag                 |

### Configurações

| Action       | Método | Descrição                          |
|--------------|--------|------------------------------------|
| `config_get` | GET    | Retorna valor de uma configuração  |
| `config_set` | POST   | Define valor de uma configuração   |

---

## Banco de Dados

O banco SQLite é criado automaticamente em `data/devtrack.sqlite`. O schema é versionado via `PRAGMA user_version` com migrations incrementais aplicadas em `db.php`.

### Tabelas

| Tabela         | Descrição                                         |
|----------------|---------------------------------------------------|
| `atividades`   | Cards do Kanban com todos os seus campos          |
| `projetos`     | Projetos para categorização das atividades        |
| `tipos`        | Tipos de atividade com configuração de cores      |
| `tags`         | Tags livres para classificação adicional          |
| `historico`    | Log de movimentações e alterações dos cards       |
| `configuracoes`| Pares chave-valor para configurações do sistema   |

### Dados padrão (seed)

Na primeira inicialização, o sistema insere automaticamente:

- **Projetos:** Geral, Backend, Frontend, DevOps, Mobile
- **Tipos:** Bug, Feature, Hotfix, Refactor, Deploy, Reunião, Documentação, Outro
- **Tags:** backend, frontend, banco, api, segurança, urgente, infra

---

## Configurações

Acesse `http://localhost/DevTrack/config.php` para:

- Criar, editar e remover **projetos** com cor personalizada
- Criar, editar e remover **tipos** com cor do texto e do fundo
- Criar, editar e remover **tags** com cor personalizada
- Definir **limites WIP** por coluna (deixe vazio para sem limite)

Quando uma coluna ultrapassa o limite WIP, o badge da coluna pisca em vermelho como alerta visual.

---

## Contribuindo

Contribuições são bem-vindas! Para contribuir:

1. Fork o repositório
2. Crie uma branch para sua feature: `git checkout -b feature/minha-feature`
3. Commit suas alterações: `git commit -m 'feat: adiciona minha feature'`
4. Push para a branch: `git push origin feature/minha-feature`
5. Abra um **Pull Request**

### Padrão de commits

Utilize o padrão [Conventional Commits](https://www.conventionalcommits.org/):

```
feat:     nova funcionalidade
fix:      correção de bug
refactor: refatoração sem mudança de comportamento
docs:     alterações na documentação
style:    formatação, sem mudança de lógica
chore:    tarefas de manutenção
```

---

## Licença

Distribuído sob a licença **MIT**. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

---

<div align="center">
  Feito com ♥ por <a href="https://github.com/AlexCamposDosSantos">Alex Campos</a>
</div>
