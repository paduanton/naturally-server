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
Valores ausentes, ilegíveis ou inválidos impedem o startup. Credenciais locais persistentes
serão geradas em volume no incremento de Compose; os testes usam valores sintéticos efêmeros.

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
no filtro de fontes. Isso não comprova persistência de sessões/cache, startup com
Compose, conectividade real, autenticação, entrega de email em produção ou deployment AKS.
