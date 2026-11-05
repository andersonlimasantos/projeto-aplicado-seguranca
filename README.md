# Painel de Demandas de TI

Protótipo desenvolvido para a disciplina Projeto Aplicado: Práticas de
Mercado, com o objetivo de integrar desenvolvimento, infraestrutura e
segurança da informação.

## Funcionalidades previstas

- Autenticação com usuário e senha.
- Página interna protegida.
- Cadastro e consulta de demandas de TI.
- Encerramento da sessão por logout.

## Tecnologias

- PHP e MySQL.
- Nginx em uma VM Ubuntu na AWS.
- GitHub e GitHub Actions para versionamento e implantação automatizada.

## Organização

- `public/`: páginas e arquivos acessíveis pelo navegador.
- `src/`: conexão ao banco e funções compartilhadas.
- `database/schema.sql`: estrutura das tabelas, sem dados ou credenciais.

## Medidas de segurança implementadas no código

- Verificação de sessão nas páginas internas.
- Senhas armazenadas como hash.
- Consultas preparadas ao banco.
- Tratamento de textos exibidos nas páginas.
- Tokens de proteção nos formulários.
- Cookies de sessão com as opções Secure, HttpOnly e SameSite.
- Registro de tentativas de login para limitação por IP.

## Infraestrutura

A VM foi configurada com acesso SSH por chave, autenticação por senha
desabilitada, Fail2Ban, Nginx e HTTPS com certificado Let's Encrypt.

O servidor oferece TLS 1.3 e troca de chaves híbrida X25519MLKEM768,
verificada com OpenSSL e no teste da DigiCert. O certificado utiliza
assinatura ECDSA.

## Situação

O código e as configurações ainda serão integrados e testados em produção.
A documentação dos testes, das categorias OWASP e da implantação
automatizada será completada após a verificação do funcionamento.

As credenciais e os dados do banco não devem ser publicados no repositório.

## OWASP Top 10:2025

### A01 — Controle de Acesso Quebrado

As páginas internas verificam a sessão no servidor por meio da função
`exigir_login()`, em `src/bootstrap.php`. Os formulários de cadastro e
logout usam tokens CSRF. O acesso direto ao painel sem autenticação deve
redirecionar para o login.

### A04 — Falhas Criptográficas

As senhas são armazenadas como hash, utilizando `password_hash()` e
verificadas com `password_verify()`. A comunicação com o site usa HTTPS.
As credenciais do MySQL ficam em arquivo fora do repositório e da pasta
pública. O banco de dados não é criptografado por essas medidas.

### A05 — Injeção

O login e o cadastro usam consultas preparadas com PDO. Os textos
apresentados nas páginas são tratados com `htmlspecialchars()` pela
função `escapar()`, reduzindo riscos como SQL Injection e XSS.

## Implantação automatizada

O arquivo `.github/workflows/deploy.yml` é acionado por push na branch
`main`. O workflow verifica a sintaxe dos arquivos PHP e, se a etapa
terminar com sucesso, copia as pastas da aplicação para a VM usando um
runner próprio.

O runner é executado pela conta `painel-deploy`, sem executar workflows
de pull requests. Como o repositório é público, alterações e ações de
terceiros devem ser revisadas antes de serem executadas no runner.

As verificações de sintaxe não substituem testes funcionais ou uma
auditoria completa de segurança.
