# Autenticação do navegador no BFF

O contrato novo fica em `/api/v1/auth`. O cliente usa cookies de sessão de servidor,
sem tokens bearer, refresh tokens próprios ou credenciais no armazenamento do navegador.
Fortify executa cadastro, login, logout e confirmação de senha; Sanctum identifica a sessão
nas leituras protegidas. As rotas padrão dos pacotes e suas interfaces HTML estão desabilitadas.

## Fluxo e respostas

O navegador e o BFF compartilham a origem configurada em `app.url`, incluindo esquema,
host e porta. No desenvolvimento, a interface deverá encaminhar `/api` por um proxy na
mesma origem. Não habilitar CORS amplo para contornar essa fronteira. A configuração local
continua seguindo `docs/runtime.md`, com JSON público e segredos em volumes, sem `.env`.

Antes de escrever, chamar `GET /api/v1/auth/csrf-cookie` com cookies habilitados.
Enviar o cookie `XSRF-TOKEN`, decodificado, no cabeçalho `X-XSRF-TOKEN` das operações
de escrita. O cookie de sessão é `HttpOnly`, sem `Domain` compartilhado, `SameSite=Lax`
e `Secure` quando a URL configurada usa HTTPS. Produção exige HTTPS. Nunca copiar o
cookie de sessão para JavaScript, logs ou URLs.

Escritas recebem JSON no corpo, sem parâmetros na query. Respostas de autenticação e
erros usam `Cache-Control: no-store`. O contrato legível por ferramentas está em
`runtime/resources/contracts/identity.openapi.json`; seu catálogo é conferido com as rotas reais.

| Método e recurso | Entrada | Resultado |
| --- | --- | --- |
| `GET csrf-cookie` | Sem corpo | 204; inicializa cookies/CSRF |
| `POST register` | `name`, `username`, `email`, `password`, `password_confirmation` | 201 com `data.id`; inicia sessão |
| `POST login` | `password` e um identificador, `email` ou `login` | 204; regenera identificador da sessão |
| `GET me` | Sessão autenticada | 200 com `data`: `id`, `name`, `username`, `email`, `email_verified` |
| `POST logout` | Sessão e CSRF | 204; invalida a sessão atual e regenera CSRF |
| `DELETE sessions/others` | Sessão, CSRF e autenticação recente | 204; encerra outras sessões do mesmo usuário; repetível |
| `POST password/confirm` | `password`, sessão e CSRF | 204; renova a confirmação, sem renovar a validade absoluta da sessão |
| `PUT password` | `password`, `password_confirmation`, sessão, CSRF e autenticação recente | 204; troca a senha, invalida tokens de recuperação e todas as sessões do usuário; exige novo login |
| `GET email/verify/{id}/{hash}` | Sessão do titular; query `expires` e `signature` gerada pelo servidor | 204; verifica o e-mail atual; repetível |
| `POST email/verification-notification` | Sessão e CSRF | 202 vazio; reenvia somente para o próprio e-mail não verificado |
| `POST password/recovery` | `email` e CSRF; sem sessão autenticada | 202 vazio, independentemente da existência ou elegibilidade da conta |
| `POST password/reset` | `email`, `token`, `password`, `password_confirmation` e CSRF; sem sessão autenticada | 204; consome prova uma vez, troca senha e revoga sessões; exige novo login |

`PATCH /api/v1/users/me` altera `name`, `username` ou ambos. A chamada exige sessão,
CSRF e autenticação recente e retorna 204. Campos extras e nomes de usuário indisponíveis
retornam 422. Use `GET /api/v1/auth/me` para ler os dados atualizados.

`PUT /api/v1/users/me/email` aceita apenas email e exige sessão, CSRF e autenticação recente.
A troca é limitada a cinco tentativas por minuto por conta e IP. O retorno é 204.
Se o endereço mudar, as sessões são encerradas e o novo endereço precisa ser verificado.
Reenviar o mesmo endereço mantém a sessão e a verificação.

O autor vem exclusivamente da sessão. Identificadores enviados no corpo ou na query não
selecionam outro usuário. O cadastro rejeita campos protegidos e não aceita remember-me.
Envie um identificador no login. O campo `email` aceita um endereço de e-mail.
O campo `login` aceita nome de usuário ou e-mail. Ambos passam pela normalização do domínio.

E-mail e username são normalizados no domínio. Username mantém 4–20 caracteres ASCII
alfanuméricos, com no máximo um ponto ou underscore interno. A senha nova tem 15–1024
caracteres Unicode válidos, sem composição artificial, e é armazenada com Argon2id.
Contas apenas sociais não recebem senha local implicitamente por essas operações.

Sessões expiram após 120 minutos de inatividade e, independentemente da atividade,
12 horas desde a autenticação. Operações sensíveis exigem confirmação há menos de 15
minutos; timestamps ausentes ou futuros não autorizam a operação. Senha e revogação são
alteradas sob transação MySQL, verificando novamente a sessão atual para impedir uso de
uma sessão que tenha sido revogada durante a requisição.

## Falhas e limitação de tentativas

Erros seguem Problem Details, com `type`, `title`, `status`, `code` e `correlation_id`.
O identificador também aparece em `X-Request-ID`. Validação inclui apenas os nomes dos
campos e mensagens genéricas, sem refletir valores, SQL, senhas ou detalhes internos.

| Status | Significado |
| --- | --- |
| 400 | `invalid_request` em operações de escrita com parâmetros na query |
| 401 | Sessão ausente, inválida ou expirada |
| 403 | Origem/host incompatíveis ou prova de verificação inválida |
| 409 | Cadastro, login ou recuperação de senha solicitados com sessão já autenticada |
| 415 | Escrita sem conteúdo JSON |
| 419 | Falha de CSRF |
| 422 | Entrada ou credencial inválida; conta desconhecida, senha incorreta e conta social têm a mesma forma de erro no login |
| 423 | `reauthentication_required`; confirmar a senha antes de repetir a operação sensível |
| 429 | Limite excedido; respeitar `Retry-After` |
| 503 | Redis indisponível durante a limitação; a autenticação não prossegue |

Redis limita cadastro a 5/minuto por IP. No login, e-mail e nome de usuário da mesma
conta compartilham 5 tentativas por minuto por conta e IP, com limite adicional de 30
tentativas por minuto por IP. A confirmação permite 5 tentativas por minuto por usuário e IP. Reenvio de verificação permite
6/minuto por usuário e 30/minuto por IP; verificação permite 12/minuto por usuário.
Recuperação permite 5/minuto por e-mail/IP e 30/minuto por IP; consumo permite 10/minuto
por IP. O repositório nativo também impede emitir outro token em menos de 60 segundos.
As chaves usam HMAC e não
armazenam o identificador em claro. A política de IP depende de uma configuração revisada
de proxies confiáveis antes do AKS; não confiar em cabeçalhos encaminhados de qualquer origem.

## Verificação e recuperação

O cadastro envia link absoluto assinado, válido por 60 minutos e vinculado ao usuário e
ao e-mail atual. O titular precisa estar autenticado; o hash do e-mail não substitui a
assinatura. A navegação de nível superior a partir de webmail pode abrir essa rota GET
sem Origin, mantendo validação de host, assinatura e sessão. Requisições de outra origem
por JavaScript continuam rejeitadas. Recursos que exigirem e-mail verificado devem usar
a proteção `verified` no servidor; `email_verified` no frontend apenas informa o estado.

A recuperação atende somente contas ativas com senha local. O framework gera a prova e
armazena seu hash; ela expira em 30 minutos. Consumo, troca da senha e revogação são uma
transação MySQL, com locks que impedem duas redefinições concorrentes pela mesma prova.
Token inválido, expirado, reutilizado ou de outra conta tem a mesma resposta 422.

O link de recuperação aponta para `/auth/reset-password` com e-mail e token no fragmento
da URL, não na query. A futura interface deve ler o fragmento em memória, removê-lo com
`history.replaceState`, evitar scripts de terceiros nessa página e enviar os dados em JSON.
Não gravar a prova em armazenamento do navegador, telemetria ou logs. Esta página frontend
ainda não é implementada neste repositório. A API não inicia sessão após redefinir a senha.

Notificações respeitam o commit do banco. Recuperação entrega após a resposta para não
variar o corpo/status conforme o destinatário; falha de transporte gera aviso sem dados
pessoais. Esse callback não é uma fila durável e não oferece repetição garantida após
encerramento do processo. Filas e recuperação operacional permanecem na etapa 8. Os testes
usam transporte em memória e indisponibilidade controlada; remetente, SMTP e domínio de
produção ainda precisam de configuração segura e homologação antes do AKS.

## Limites desta migração

Este contrato cobre credenciais locais, sessões, verificação de e-mail e recuperação HTTP.
Ainda faltam exclusão/restauração, vinculação e login social, autorização dos
demais módulos, agregação da cobertura de subprocessos e aceitação final do AKS.
O cadastro cria conta não verificada; `email_verified` informa esse estado e não deve ser
tratado como autorização para ações que futuramente exigirão verificação. Não há habilitação
de provedores externos, passkeys ou MFA neste incremento.

Os testes usam MySQL e Redis reais, CSRF ativo mesmo no ambiente de testes e um segundo
objeto de aplicação para verificar compartilhamento de sessão. Isso não substitui ensaios
de ingress, TLS, rotação de segredos e múltiplos pods no AKS. O módulo Identity permanece
em implementação; cobertura observada não certifica sua conclusão nem a release global.
