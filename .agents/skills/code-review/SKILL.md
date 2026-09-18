---
name: code-review
description: Revisar incrementos, branches e alterações locais do Naturally quanto ao comportamento solicitado, segurança, arquitetura e evidências de testes.
---

# Revisão de incrementos

Leia AGENTS.md, CONTRIBUTING.md, docs/architecture.md e docs/modernization/status.md.
Recupere o requisito da conversa e da documentação existente antes de pedir esclarecimentos.
Use a revisão para encontrar problemas acionáveis; preferências de estilo não são bugs.

## Delimitar a comparação

- Para um incremento local, examine separadamente alterações staged, unstaged e os arquivos
  novos do escopo. Não confunda rascunhos alheios ao incremento com a entrega proposta.
- Para uma branch, resolva a referência base e o merge-base com HEAD e registre os SHAs
  comparados. Para um commit isolado, compare com seu pai quando essa for a intenção.
  Se a base não estiver disponível ou o diff estiver vazio, relate isso explicitamente.
- Use a base fornecida pelo usuário ou identificável pelo contexto da entrega. Peça a
  referência apenas quando a ambiguidade mudar o conteúdo da revisão.

## Avaliar

Revise dois aspectos: conformidade com os padrões do projeto e atendimento ao comportamento
solicitado. Leia os chamadores e testes relevantes, além do diff, para comprovar o impacto.

Conforme o fluxo alterado, verifique autorização por recurso, campos protegidos, exposição
de segredos, validação de entrada, transações, efeitos após commit e invalidação de cache.
Confira direção das dependências, contratos sem Eloquent/HTTP e abstrações com finalidade
real. Trate duplicação, acoplamento e classes que só encaminham como indícios a investigar,
não como violações automáticas que exijam criar novas camadas.

Confira se testes exercitam o risco e se os resultados correspondem à versão revisada.
Use comandos aceitos e adequados ao escopo. Relatórios de cobertura devem incluir arquivos
não executados e branches; ausência de evidência não autoriza concluir os 90%.

## Relatar

Para cada achado, informe prioridade proporcional ao impacto, arquivo/linha, condição que
provoca o problema, consequência e regra ou requisito afetado. Diferencie defeito confirmado,
risco ainda não verificado e sugestão opcional. Não invente achados para preencher categorias.

Informe também o escopo examinado, validações executadas e limitações. Se não houver achados,
diga isso sem transformar a revisão em garantia de ausência de bugs. A revisão é local;
publicação de comentários, commits e pushes dependem das autorizações aplicáveis.
Origem e licença: docs/agents/provenance.md. Não há exigência de trackers ou subagentes.
