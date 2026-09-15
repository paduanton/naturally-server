# Desenvolvimento

Use Conventional Commits: tipo(escopo): descrição em inglês.
Tipos: feat, fix, refactor, test, docs, build, ci, perf, chore.
Escopos: identity, recipes, community, media, runtime, security, ci, docs, agents.
Mudanças incompatíveis usam ! e BREAKING CHANGE. Correções usam fix, reorganizações refactor.
Cada commit deve ser verificável; não misture formatação ampla e alteração de comportamento.

## Aprovação obrigatória por incremento

Antes de TODO commit e push, apresentar resumo das alterações, validações e limitações e aguardar aprovação explícita do usuário para aquele incremento. Aprovações não se estendem aos próximos commits ou pushes. Não executar commit, amend ou push automaticamente. Após concluir o commit e push aprovados, seguir para o próximo incremento sem nova pergunta para retomar o desenvolvimento. Cada interação termina com resumo do progresso e indicação de aprovação pendente, quando houver.

Cada commit deve tratar uma única responsabilidade, com escopo pequeno. Não agrupar inventário, skills, CI e runtime no mesmo commit. Apresentar arquivos exatos e mensagem Conventional Commit antes de solicitar aprovação; adicionar ao staging somente os arquivos ou trechos aprovados.

Todo bug confirmado ganha regressão. A meta por módulo concluído e de release
é 90% de linhas e branches, não uma razão de arquivos que possuem testes.
Credenciais locais são geradas; dados de teste são sintéticos.
O plano privado permanece fora do versionamento. Relatórios de progresso devem distinguir trabalho pendente de entregas verificadas.
