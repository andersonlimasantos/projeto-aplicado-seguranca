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
