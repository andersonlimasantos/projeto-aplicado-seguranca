# Painel de Demandas de TI

Protótipo desenvolvido para a disciplina Projeto Aplicado: Práticas de
Mercado, com o objetivo de integrar desenvolvimento, infraestrutura e
segurança da informação.

## Funcionalidades

- Autenticação com usuário e senha.
- Página interna protegida.
- Cadastro e consulta de demandas de TI.
- Encerramento da sessão por logout.
- Trilha de auditoria para acompanhamento de login, cadastro de demandas e logout.
- Página interna protegida para consulta do histórico de auditoria.

## Tecnologias

- PHP e MySQL.
- Nginx em uma VM Ubuntu na AWS.
- GitHub e GitHub Actions para versionamento e implantação automatizada.

## Organização

- `public/`: páginas e arquivos acessíveis pelo navegador.
- `src/`: conexão ao banco e funções compartilhadas.
- `database/schema.sql`: estrutura das tabelas para novas instalações, sem dados ou credenciais.
- `database/migracao_auditoria.sql`: script de migração da trilha de auditoria para bancos já existentes.

## Medidas de segurança implementadas no código

- Verificação de sessão nas páginas internas com expiração por inatividade de 30 minutos.
- Invalidação segura de sessões legadas sem marcadores temporais.
- Mitigação para reduzir diferenças de tempo na autenticação (timing attacks) via hash de referência fictício.
- Trilha de auditoria em banco de dados para rastreabilidade de eventos críticos.
- Senhas armazenadas como hash.
- Consultas preparadas ao banco.
- Tratamento de textos exibidos nas páginas.
- Tokens de proteção nos formulários.
- Cookies de sessão com as opções Secure, HttpOnly e SameSite.
- Registro de tentativas de login para limitação por IP.
- Prevenção de vazamento acidental de dumps com bloqueio de arquivos `*.sql` no `.gitignore`.

## Infraestrutura

A VM foi configurada com acesso SSH por chave, autenticação por senha
desabilitada, Fail2Ban, Nginx e HTTPS com certificado Let's Encrypt.

O servidor oferece TLS 1.3 e troca de chaves híbrida X25519MLKEM768,
verificada com OpenSSL e no teste da DigiCert. O certificado utiliza
assinatura ECDSA.

## Situação

A aplicação foi publicada na VM e acessada por HTTPS. Foram realizados
testes de login, cadastro e consulta de demanda, logout e acesso à página
interna após o encerramento da sessão.

Uma alteração na tela de login foi enviada por push e publicada pelo
GitHub Actions. Após a publicação, foi verificado que a demanda
cadastrada permaneceu no banco de dados.

## Uso de inteligência artificial

O ChatGPT foi utilizado como assistente para apoiar a elaboração e a
revisão do código, a configuração da infraestrutura e a resolução de
problemas durante a implantação. As configurações e os resultados foram
acompanhados e verificados durante a execução do projeto.

As credenciais e os dados do banco não devem ser publicados no repositório.

## OWASP Top 10:2025

### A01 — Controle de Acesso Quebrado

As páginas internas verificam a sessão no servidor por meio da função
`exigir_login()`, em `src/bootstrap.php`. A sessão é invalidada após
30 minutos de inatividade, e sessões legadas sem marcadores temporais
são descartadas de forma segura. Os formulários de cadastro e logout usam
tokens CSRF. O acesso direto ao painel sem autenticação redireciona para o login.

### A04 — Falhas Criptográficas

As senhas são armazenadas como hash, utilizando `password_hash()` e
verificadas com `password_verify()`. A comunicação com o site usa HTTPS.
As credenciais do MySQL ficam em arquivo fora do repositório e da pasta
pública. O banco de dados não é criptografado por essas medidas.

### A05 — Injeção

O login e o cadastro usam consultas preparadas com PDO. Os textos
apresentados nas páginas são tratados com `htmlspecialchars()` pela
função `escapar()`, reduzindo riscos como SQL Injection e XSS.

### A07 — Falhas de Autenticação

Para mitigar a enumeração de usuários por ataques de temporização
(*timing attacks*), o fluxo de login compara tentativas contra um hash
bcrypt fictício quando o usuário não existe no banco. Trata-se de uma
mitigação que reduz as discrepâncias no tempo de resposta em relação a
um usuário existente, sem expor credenciais reais (embora não elimine
completamente variações decorrentes de consultas ao banco ou parâmetros
distintos de custo de hashing).

### A09 — Falhas de Registro e Alerta de Segurança

O sistema implementa uma trilha de auditoria na tabela `auditoria`, registrando
eventos operacionais e de autenticação: login bem-sucedido (`LOGIN_SUCESSO`),
criação de demanda (`CADASTRO_DEMANDA`) e logout voluntário (`LOGOUT`). Cada
registro contém timestamp, identificador e nome do usuário responsável, tipo de
evento, identificador da demanda associada e detalhes contextuais mínimos (como a
prioridade atribuída). Informações sensíveis (senhas, hashes, tokens, cookies e
credenciais), bem como o título e a descrição integral das demandas, não são
gravados na auditoria.

A consulta aos eventos é disponibilizada na página `/auditoria.php`, exibindo
os 100 registros mais recentes ordenados do mais novo para o mais antigo.

No cadastro de demandas, a operação é transacional e atômica (*fail-closed*): caso
a gravação da auditoria falhe, a transação sofre rollback e a demanda não é criada,
garantindo rastreabilidade obrigatória. No login, a auditoria é registrada antes
de promover a sessão; se falhar, o usuário não permanece autenticado e a sessão
é abortada (*fail-closed*). No logout, a rotina é resiliente (*fail-open* para
encerramento): eventuais falhas na gravação da auditoria são logadas no servidor,
mas a sessão é obrigatoriamente destruída para não prender o operador.

#### Limitações conhecidas da trilha de auditoria nesta versão

- **Acesso amplo aos registros:** Todos os usuários autenticados possuem acesso
  à página de consulta da auditoria (`/auditoria.php`), sem restrição por perfis
  de acesso ou controle baseado em papéis (RBAC).
- **Mutabilidade por usuários privilegiados no banco:** Os registros são mantidos
  em tabela relacional convencional. Usuários ou serviços com privilégios de
  administração no MySQL ou acesso direto à VM podem alterar, truncar ou excluir
  registros retroativamente, pois a tabela não conta com armazenamento imutável
  WORM (*Write Once, Read Many*) ou encadeamento criptográfico.

## Implantação automatizada

O arquivo `.github/workflows/deploy.yml` é acionado por push na branch
`main`. O workflow verifica a sintaxe dos arquivos PHP e, se a etapa
terminar com sucesso, copia as pastas da aplicação para a VM usando um
runner próprio.

O runner é executado pela conta `painel-deploy`, sem executar workflows
de pull requests.

### Limitação de segurança: runner self-hosted em repositório público

O uso de um runner próprio (*self-hosted*) em um repositório público é uma
limitação de segurança reconhecida no projeto. O GitHub recomenda o uso de
runners auto-hospedados apenas em repositórios privados, pois em repositórios
públicos modificações não autorizadas ou falhas de configuração em workflows
podem resultar na execução de código arbitrário diretamente no host. Como o
runner deste projeto está co-alocado na própria VM de produção, uma potencial
violação daria acesso imediato ao ambiente do servidor (arquivos locais,
serviços internos e metadados da instância na nuvem). Recomenda-se, como
evolução futura, migrar para runners gerenciados na nuvem (*GitHub-hosted*)
ou isolar o processo em contêineres efêmeros descartáveis.

As verificações de sintaxe não substituem testes funcionais ou uma
auditoria completa de segurança.
