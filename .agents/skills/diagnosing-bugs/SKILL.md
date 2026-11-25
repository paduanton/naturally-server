---
name: diagnosing-bugs
description: Investigar falhas funcionais, de segurança ou desempenho no Naturally, reproduzir o sintoma e corrigir a causa com teste de regressão.
---

# Diagnóstico e regressão

Leia AGENTS.md e docs/modernization/bugs.md. Relacione a falha ao fluxo, aos chamadores
e às evidências existentes antes de alterar comportamento. Diferencie suspeita de inspeção
estática, reprodução executada e correção verificada.

## Investigar

- Identifique entrada, estado inicial, resultado esperado e sintoma observado. Construa
  a menor reprodução que ainda exercite o problema real e execute-a com dados sintéticos.
- Prefira um teste na fronteira adequada. Um script ou requisição local pode ajudar a
  investigar, mas a correção precisa de regressão automatizada que falhe antes do ajuste.
- Confirme que a falha não é apenas ausência de dependência ou erro de configuração.
  Para concorrência, use coordenação controlada e descreva a frequência de reprodução;
  uma execução verde isolada não comprova correção de um problema intermitente.
- Formule hipóteses verificáveis conforme as evidências e varie um fator de cada vez.
  Inspeção estática pode orientar a reprodução; não apresente hipótese como causa confirmada.
- Para desempenho, registre cenário e medição inicial (tempo, queries ou plano de execução)
  e compare após a mudança nas mesmas condições. Evite otimização baseada só em impressão.
- Use instrumentação mínima e temporária. Não capture cookies, tokens, senhas ou dados reais
  em logs, comandos ou artefatos; consuma segredos pelo mecanismo de runtime aprovado.

## Corrigir e registrar

Escreva e observe a regressão falhar pelo defeito, aplique a menor correção da causa e
execute novamente o teste e o cenário original. Reexecute os testes afetados e remova
somente a instrumentação temporária criada para essa investigação.

No registro de bugs, mantenha identificador, evidência, impacto, causa confirmada,
regressão e resultado. Uma regressão de segurança deve exigir rejeição do acesso ou
remoção do vazamento; não deve exigir que a vulnerabilidade continue funcionando.

Se a reprodução estiver bloqueada, registre o que foi tentado e o pré-requisito ausente.
Continue a investigação possível sem declarar a falha corrigida; solicite informação
somente quando não puder obtê-la no repositório. Não use produção para experimentar
nem amplie o refactor sem relação com a causa.
Origem e licença: docs/agents/provenance.md. Aprovações seguem AGENTS.md.
