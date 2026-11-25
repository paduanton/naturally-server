# Desenvolvimento

Use Conventional Commits: tipo(escopo): descrição em inglês.
Tipos: feat, fix, refactor, test, docs, build, ci, perf, chore.
Escopos: identity, recipes, community, media, runtime, security, ci, docs, agents,
architecture, inventory, skills. Use um único escopo por commit.
Mudanças incompatíveis usam ! e BREAKING CHANGE. Correções usam fix, reorganizações refactor.
Cada commit deve ser verificável; não misture formatação ampla e alteração de comportamento.

## Validação das mensagens

O job `commits` do workflow Quality usa commitlint com configuração Conventional Commits,
tipos e escopos acima, escopo obrigatório e `!` acompanhado de `BREAKING CHANGE:` para
mudanças incompatíveis. O cabeçalho tem limite de 100 caracteres; as demais regras de
formatação seguem [config-conventional](https://commitlint.js.org/reference/rules.html).
A descrição pode conter siglas como BFF e AKS. Escrita em inglês e coerência do incremento
continuam sob revisão humana; o linter não determina o tamanho adequado de um commit.

Requisitos: Node.js >=22.12 e npm >=10. As dependências ficam isoladas em tools/commitlint,
sem instalar o pacote legado da raiz. Da raiz do repositório:

```sh
npm ci --prefix tools/commitlint --ignore-scripts --no-audit --no-fund
npm --prefix tools/commitlint test
echo "ci(ci): validate commit messages" | npm --prefix tools/commitlint run lint --
git log -1 --format=%B | npm --prefix tools/commitlint run lint --
```

A CI verifica os commits adicionados no intervalo do push ou PR, inclusive merges reais.
Em PRs, usa o head do autor, não a mensagem do merge sintético criado pelo GitHub. Na
criação de uma branch, verifica todo o histórico alcançável que não estava na base aceita.
Commits já alcançáveis de `c4b21e67bdb4b4f254e91e4a8e6210f10ec652c2` são excluídos da
validação para preservar o histórico aprovado. Fixups, mensagens de versão e merges novos
não têm dispensa automática. Falhas de leitura do histórico interrompem o job.

O workflow não configura proteção de branch nem autoriza commit/push. Para tornar o check
obrigatório ao integrar PRs, o repositório precisa de ruleset/proteção correspondente;
essa configuração externa não faz parte desta entrega.

## Aprovação obrigatória por incremento

Antes de TODO commit e push, apresentar resumo das alterações, validações e limitações e aguardar aprovação explícita do usuário para aquele incremento. Aprovações não se estendem aos próximos commits ou pushes. Não executar commit, amend ou push automaticamente. Após concluir o commit e push aprovados, seguir para o próximo incremento sem nova pergunta para retomar o desenvolvimento. Cada interação termina com resumo do progresso e indicação de aprovação pendente, quando houver.

Cada commit deve tratar uma única responsabilidade, com escopo pequeno. Não agrupar inventário, skills, CI e runtime no mesmo commit. Apresentar arquivos exatos e mensagem Conventional Commit antes de solicitar aprovação; adicionar ao staging somente os arquivos ou trechos aprovados.

Todo bug confirmado ganha regressão. A meta por módulo concluído e de release
é 90% de linhas e branches, não uma razão de arquivos que possuem testes.
Credenciais locais são geradas; dados de teste são sintéticos.
O plano privado permanece fora do versionamento. Relatórios de progresso devem distinguir trabalho pendente de entregas verificadas.
