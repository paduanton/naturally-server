# Auditoria inicial de segredos

Data: 01/10/2026. Base examinada: `3ac5dff450c51adbc0227eb05614b71456cc15cb`.
Relacionado a SEC-001 em [bugs.md](bugs.md). Nenhuma credencial foi usada para autenticar,
consultar provedores ou verificar validade. Este relatório não contém os valores encontrados.

## Método e reprodução

Gitleaks 8.30.1, binário Windows x64 da
[release oficial](https://github.com/gitleaks/gitleaks/releases/tag/v8.30.1), conferido antes
da execução pelo SHA-256 `d29144deff3a68aa93ced33dddf84b7fdc26070add4aa0f4513094c8332afc4e`.
O scanner foi executado localmente, sem enviar o conteúdo do repositório a serviços externos.

Foram examinados separadamente:

- Uma exportação de `git archive` da base acima, sem rascunhos, dependências locais ou plano privado.
- O histórico disponível nas referências locais, com `--log-opts="--all --full-history"`.
  Gitleaks informou 232 commits examinados. Isso não cobre forks, referências remotas
  ausentes do clone, objetos inacessíveis ou cópias externas do repositório.

Usou-se configuração explícita contendo somente `[extend]` e `useDefault = true`, sem
baseline de supressão. O caminho de ignore apontou para um arquivo inexistente e comentários
`gitleaks:allow` foram desativados. Relatórios JSON locais foram gerados com `--redact=100`;
não devem ser adicionados ao Git ou anexados a issues. Divulgar somente os metadados revisados.

Com o binário verificado no PATH, uma configuração equivalente em caminho temporário e
diretórios de saída fora do versionamento, os comandos correspondentes são:

```sh
git archive --format=zip --output=<arquivo-temporario.zip> 3ac5dff450c51adbc0227eb05614b71456cc15cb
# Extrair o arquivo para <arvore-exportada> antes de usar gitleaks dir.
gitleaks dir <arvore-exportada> --config <config-temporaria.toml> --gitleaks-ignore-path <caminho-inexistente> --ignore-gitleaks-allow --redact=100 --no-banner --no-color --report-format json --report-path <relatorio-arvore.json>
gitleaks git . --log-opts="--all --full-history" --config <config-temporaria.toml> --gitleaks-ignore-path <caminho-inexistente> --ignore-gitleaks-allow --redact=100 --no-banner --no-color --report-format json --report-path <relatorio-historico.json>
```

Os itens entre `<...>` são marcadores de caminhos, não argumentos para copiar literalmente.
Código de saída 1 indica achados neste ensaio; falhas operacionais também precisam de análise.
Configuração, redação e limites seguem a [documentação do scanner](https://github.com/gitleaks/gitleaks/blob/v8.30.1/README.md).

## Resultados anteriores à correção

Na árvore exportada: quatro ocorrências `generic-api-key`, todas no README.md da base:

| Linha | Campo |
|---|---|
| 123 | access_token do provedor |
| 124 | access_token_secret do provedor |
| 144 | access_token da resposta |
| 147 | remember_token da resposta |

No histórico: dez ocorrências (não necessariamente dez credenciais distintas):

| Commit | Arquivo e linhas | Regra |
|---|---|---|
| b8e2fcaa674cc5386d63be20364d1b9a07807e0d | README.md:121, 122, 142 | generic-api-key |
| d0f5d6c6684353171e74f71875e6f762f7f72d07 | README.md:122 | generic-api-key |
| d169d95e45a442542035b9ae252cc29a6f1e3ff0 | README.md:122 | generic-api-key |
| 32f3189db2958bf480953e39dc9bb3d6c4693eb9 | README.md:121, 122, 144 | generic-api-key |
| 32f3189db2958bf480953e39dc9bb3d6c4693eb9 | README.md:141 | jwt |
| 853c9cc7973946734506ba4824e76b48b2246b2b | README.md:116 | generic-api-key |

As regras padrão não apontaram todas as senhas literais identificadas por inspeção, incluindo
a senha de banco no Compose legado e senhas de exemplos. Ausência de achados não certifica
ausência de segredos. Não se verificou se os tokens são exemplos, estão expirados ou são válidos.

## Correção deste incremento e limites

O README substitui oito valores em campos de senha/token e um valor Bearer por marcadores.
Identifica os contratos de autenticação como referência histórica, sem apresentá-los como
orientação para o BFF modernizado. A repetição da varredura com o README corrigido passou
de quatro achados (saída 1) para zero (saída 0), usando as mesmas regras.
A exportação da árvore, atualizada somente com os quatro arquivos deste incremento,
também retornou zero ocorrências. Uma conferência adicional dos campos de senha/token
identificou oito valores sem marcadores antes e zero depois; o Bearer literal também
foi substituído. O inventário permanece válido com 210 arquivos e zero concluídos.

O histórico continua com dez ocorrências: não foi reescrito. Nenhuma revogação foi realizada.
Se forem identificadas credenciais reais, sua revogação/rotação deve preceder eventual limpeza
coordenada do histórico. Remover exemplos atuais não elimina cópias anteriores.

SEC-001 permanece aberto: a senha do Compose e a fixture previsível da factory ainda exigem
tratamento na configuração/runtime. O bloqueio de novos segredos no CI será um incremento
separado, com testes de detecção/redação e uma política explícita para o legado. Esta auditoria
não cria allowlist ou baseline para silenciar as ocorrências históricas.
