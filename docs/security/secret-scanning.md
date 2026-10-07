# Detecção de segredos durante a modernização

O job `secrets` do workflow Quality usa Gitleaks 8.30.1 com as regras padrão, definidas
explicitamente em tools/gitleaks/config.toml. A versão e o SHA-256 do arquivo Linux x64
estão fixados no workflow. O download falha se a verificação de integridade não passar.
O checkout mantém histórico completo, permissões de leitura e credenciais não persistidas.

## Escopo e limite de adoção

Cada push/PR executa dois bloqueios independentes:

1. Varredura de uma exportação da árvore Git do head, incluindo arquivos versionados.
   Dependências instaladas, rascunhos e o plano privado não entram na exportação.
2. Varredura dos diffs dos commits alcançáveis do head e não alcançáveis de
   `4fc7496b28e007d19f9a0ca2e3994d7b8334f45c`, com histórico completo e diffs de merges.
   Isso também permite detectar um valor adicionado e removido entre commits.

Em PRs, usa-se o head do autor. Referências inválidas/ausentes falham antes da varredura.
O intervalo é cumulativo desde a adoção, não apenas o último push, inclusive em branches
novas. Um achado ou erro operacional bloqueia o job; não há `continue-on-error`.

A base de adoção é a correção dos exemplos do README. As dez ocorrências anteriores
continuam no [relatório da auditoria](../modernization/secrets-audit.md), fora do intervalo
que bloqueia novos commits. Esse recorte histórico não suprime valores na árvore atual:
reintroduzir um valor detectável também falha na primeira varredura. Não há baseline JSON
de supressão nem allowlist própria. As regras padrão têm seus próprios limites e exceções.

Comentários `gitleaks:allow` são ignorados pelo comando. O job bloqueia a execução se houver
`.gitleaksignore` em qualquer origem de varredura, mesmo vazio ou como symlink. O Gitleaks
carrega esse arquivo da origem independentemente do caminho passado por flag, comportamento
reproduzido no teste de regressão e confirmado no [código da versão fixada](https://github.com/gitleaks/gitleaks/blob/v8.30.1/cmd/root.go).
O caminho de ignore passado por flag é inexistente dentro de um diretório temporário novo.
Alterações nas
regras ou comandos exigem revisão; a CI não substitui a revisão de seu próprio workflow.

## Relatórios e revisão de achados

Os comandos usam `--redact=100`. Relatórios JSON permanecem no diretório temporário do
runner e não são publicados como artifacts. Logs de resumo não devem receber valores
de credenciais; compartilhar somente regra, arquivo, linha e referência revisados.
Os testes geram valores sintéticos em memória e verificam a redação dos logs e relatórios.

Ao encontrar um valor suspeito: interromper o envio, identificar sua origem, substituir
por configuração segura ou marcador documental e repetir a verificação. Se houver
credencial real, revogar/rotacionar com o responsável; eventual limpeza do histórico é
uma ação separada. Apagar o valor do último commit não resolve cópias anteriores.
Falsos positivos exigem justificativa e revisão específica, sem dispensa automática.

## Uso local

Requisitos: Git, Node.js 22+ e Gitleaks 8.30.1. Instalar o scanner pela
[release oficial](https://github.com/gitleaks/gitleaks/releases/tag/v8.30.1), conferindo
o SHA-256 correspondente. Linux x64 usa o checksum fixado no workflow; Windows x64 usa
`d29144deff3a68aa93ced33dddf84b7fdc26070add4aa0f4513094c8332afc4e`.
Colocar o executável no PATH. Para os testes, `GITLEAKS_BIN` aceita seu caminho absoluto.

Da raiz do repositório:

```sh
node --test scripts/tests/secrets.test.mjs
node scripts/check-secret-ignores.mjs .
gitleaks git . --pre-commit --config tools/gitleaks/config.toml --gitleaks-ignore-path tools/gitleaks/no-ignore --ignore-gitleaks-allow --redact=100 --no-banner --no-color
gitleaks git . --pre-commit --staged --config tools/gitleaks/config.toml --gitleaks-ignore-path tools/gitleaks/no-ignore --ignore-gitleaks-allow --redact=100 --no-banner --no-color
```

Interromper se a verificação de origens falhar; não executar o scanner sem ela. O caminho
`tools/gitleaks/no-ignore` deve permanecer inexistente. A primeira varredura
local examina alterações não staged; a segunda examina o staging. Arquivos untracked
ainda não adicionados ao Git precisam de revisão própria. Esses comandos não criam commits
nem substituem o escopo completo do job. Nunca adicionar arquivos apenas para ampliar uma
varredura: o staging continua limitado aos arquivos aprovados pelo usuário.

## Limitações

As regras são baseadas em padrões e não detectam todo tipo de segredo. A auditoria mostrou
senhas curtas e fixtures previsíveis do legado que não foram detectadas; SEC-001 permanece
aberto. Revisão de configuração, factories e fluxo de segredos do runtime é obrigatória.
Binários e arquivos internos de archives não têm cobertura completa por este comando;
não usar essas formas para armazenar credenciais. Segredos externos ao Git também ficam
fora do escopo. A configuração de proteção de branch/rulesets ainda é uma etapa externa.

Este job não comprova revogação, limpeza histórica, segurança da aplicação ou cobertura
Laravel. Consulte a [documentação do Gitleaks](https://github.com/gitleaks/gitleaks/blob/v8.30.1/README.md)
para o comportamento da ferramenta e a auditoria para as ocorrências conhecidas.
