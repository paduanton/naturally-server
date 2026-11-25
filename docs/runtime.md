# Startup do runtime modernizado

A aplicação em `runtime/` usa o lockfile Laravel/PHP aprovado e a imagem de ferramentas
`naturally-php-development:local`. O setup do README da raiz ainda pertence ao legado.
O bootstrap HTTP/CLI não instala dependências, executa migrations ou gera credenciais.

## Fontes de configuração

Os pontos de entrada exigem três variáveis de localização. Seus valores são caminhos,
nunca segredos; não criar um arquivo `.env` para fornecê-los.

| Variável | Conteúdo do caminho | Acesso da aplicação |
|---|---|---|
| `NATURALLY_CONFIG_PATH` | Arquivo JSON de configuração pública | Leitura |
| `NATURALLY_SECRET_DIRECTORY` | Diretório com `app_key` e `db_password` | Leitura |
| `NATURALLY_RUNTIME_DIRECTORY` | Diretório externo para manifests e arquivos temporários | Leitura/escrita |

O JSON contém somente `environment`, `debug`, `url`, `database`, `redis` e `mail`.
Ambientes aceitos: `local`, `testing` e `production`. `debug` é booleano; em produção
deve ser falso e a URL deve usar HTTPS. `database` contém `host`, `port`, `name` e `user`;
`redis` e `mail` contêm `host` e `port`. Portas são inteiros entre 1 e 65535. Hosts não
aceitam URLs ou caminhos. Campos desconhecidos, inclusive credenciais no JSON, são rejeitados.

`app_key` contém uma chave canônica `base64:` com 32 bytes; `db_password`, uma senha
não vazia. O loader aceita arquivos montados por symlinks e terminações LF/CRLF finais.
Valores ausentes, ilegíveis ou inválidos impedem o startup. O Compose gera credenciais
locais persistentes em volumes Docker; testes do loader usam valores sintéticos efêmeros.

Mantenha o código e as fontes de configuração somente leitura. O diretório de runtime
deve estar fora da árvore pública, ser acessível ao usuário da aplicação e ter acesso
restrito. A aplicação cria seus subdiretórios com permissão 0700. Manifests de serviços
e pacotes ficam nele; não contêm a configuração com credenciais.

## Comportamento e diagnósticos

- `GET /health/live` retorna JSON `{"status":"up"}` e `Cache-Control: no-store`.
  Não consulta MySQL/Redis nem inicia sessão. Não representa readiness dessas dependências.
- Erros HTTP usam `application/problem+json`, com tipo, título e status genéricos.
  Headers como `Allow` e `Retry-After` são preservados. Mensagens, traces e valores
  da exceção não são enviados ao cliente; o reporte padrão registra apenas a classe.
- Falhas no bootstrap retornam HTTP 503 ou código CLI 1, com mensagem genérica.
  O ambiente configurado pelo JSON não pode ser substituído por `--env` divergente.
- `artisan env --no-ansi` e `artisan route:list --json` são diagnósticos sem impressão
  de credenciais, depois que os caminhos e arquivos foram disponibilizados ao processo.
  `route:cache` também usa o volume de runtime; o bootstrap permanece no código.
- `config:show`, `config:cache`, `env:encrypt`, `env:decrypt`, `key:generate`, `optimize`
  e o shell `db` são bloqueados, inclusive por chamadas programáticas de Artisan.
  Esses comandos imprimem segredos, usam arquivos `.env`, geram cache sensível ou
  passam credenciais ao processo externo. Não usar o CLI como mecanismo de rotação.

Cache de configuração preexistente continua rejeitado. A geração de cache no startup
em volume protegido e a rotação operacional ainda serão implementadas. Atualizações de
JSON/segredos exigem reinício/rollout do processo; não há recarregamento automático.

Os comandos PHPUnit de AGENTS.md também executam os testes de bootstrap completo,
HTTP e dos scripts reais em processos separados, sem rede. A cobertura do processo
PHPUnit não agrega automaticamente a execução desses subprocessos; os scripts permanecem
no filtro de fontes. Essa suíte não comprova conectividade real, autenticação,
entrega de email em produção ou deployment AKS.

## Desenvolvimento local com Compose

Requer PowerShell, Docker com engine Linux e Compose com suporte a `up --wait`.
Da raiz, execute `./scripts/dev.ps1 up`. O script prepara a imagem de ferramentas,
gera os segredos uma vez e instala o lockfile em um volume `vendor`, sem plugins ou
scripts Composer. O código permanece somente leitura; o volume evita o custo de ler
milhares de arquivos de dependências por bind mount no Windows. Não executa migrations.

API: `http://localhost:8080/health/live`. Mailpit: `http://localhost:8025`.
As portas ficam no loopback; MySQL/Redis não publicam portas. O servidor PHP embutido
é somente para desenvolvimento. As imagens de serviços estão fixadas por digest.
`runtime/settings/local.json` é configuração versionável; alterar esse JSON não exige
rebuild. Reinicie/recrie os processos para aplicar configurações e segredos.

`./scripts/dev.ps1 status` consulta os serviços. `./scripts/dev.ps1 down` remove os
containers e a rede, preservando banco, segredos e dependências nos volumes. Outro `up`
reutiliza esses volumes. Não use remoção de volumes como parte do ciclo diário.
O script desativa a leitura automática do `.env` pelo Compose e restaura o ambiente
do shell ao terminar. Não fornece credenciais por variáveis ou argumentos.

O inicializador local roda sem rede, com escrita apenas nos volumes e capacidades
para ajustar suas permissões. Diretórios têm modo 0700 e segredos 0400, com leitores
específicos: aplicação UID 33; MySQL UID 999. A aplicação não monta o volume dos segredos
administrativos. Escrita temporária, `fsync` e rename evitam publicar um segredo parcial;
um lock serializa inicializadores concorrentes. Reinícios preservam as credenciais.
Volumes já inicializados com arquivos ausentes, inválidos ou senhas divergentes falham
sem gerar substitutos. Banco existente sem os marcadores dos segredos também exige
recuperação explícita. Não há rotação automática nem alteração da senha de um banco existente.

O adapter `docker/runtime/mysql-entrypoint.sh` reutiliza o inicializador da imagem
MySQL fixada e muda a leitura dos dois arquivos de senha: mantém os valores em memória
do shell, sem exportá-los ao ambiente do MySQL ou passá-los em argumentos. A conta da
aplicação tem acesso somente ao schema local; root fica restrito ao socket/localhost.
Essa adaptação exige revisão ao atualizar a imagem. Consulte o
[entrypoint oficial](https://github.com/docker-library/mysql/blob/master/8.4/docker-entrypoint.sh)
e o [tratamento de ambiente do Compose](https://docs.docker.com/compose/how-tos/environment-variables/envvars/).
Volumes locais não substituem o Key Vault/CSI previsto para produção.

## Testes com serviços reais

`./scripts/dev.ps1 test` usa o projeto `naturally-modern-tests`, com rede e volumes
próprios. Executa os testes do inicializador, a suíte de runtime e a aceitação real
MySQL/Redis de `runtime/phpunit.services.xml`; encerra os containers ao terminar,
inclusive em falhas, preservando os volumes de testes. Não sobe a API ou Mailpit nesse
projeto e não publica portas. `./scripts/dev.ps1 down -TestEnvironment` permite repetir
a limpeza dos containers. Não compartilha o banco, os segredos nem o cache do desenvolvimento.

A aceitação valida autenticação MySQL, Unicode, commit/rollback em tabela temporária,
restrição de acesso administrativo e cache Redis com expiração e locks reais.
Chaves são aleatórias e removidas ao final; não há flush global. A suíte também executa
as migrations de Identity em tabelas com prefixo aleatório por teste, incluindo repetição,
constraints, transações e rollback. A limpeza remove somente essas tabelas; o banco de
desenvolvimento permanece separado.
Os testes de volumes exercitam geração, permissões, reinícios, concorrência e falhas
sem produzir credenciais no repositório. Essa aceitação operacional é separada da
cobertura da aplicação. As tabelas `users` e `sessions` são a fundação de Identity;
guard, endpoints e revogação por exclusão lógica ainda serão implementados. Não há
autenticação completa, readiness de dependências, homologação de email ou entrega AKS.
