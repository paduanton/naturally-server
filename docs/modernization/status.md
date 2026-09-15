# Progresso da modernização

Base inicial: 4e96fcf. Plano privado: PLANO-MODERNIZACAO.md (não versionado).
Não há consumidores/dados de produção a preservar.

## Protocolo de aprovação (15/09/2026)

Todo commit e push exige resumo e aprovação explícita do usuário para um incremento pequeno, de responsabilidade única, com arquivos exatos e mensagem Conventional Commit informados. Depois do commit/push aprovados, prosseguir automaticamente com o próximo incremento. Não reutilizar a aprovação para incrementos seguintes. Preparar somente os arquivos ou trechos aprovados.

## Incrementos aprovados

- 03b74aa — docs: define incremental commit approval policy. Somente CONTRIBUTING.md;
  commit e push para origin/codex/modernize-naturally aprovados e concluídos.
- b926784 — docs(architecture): define modular BFF boundaries. Somente docs/architecture.md;
  commit e push para origin/codex/modernize-naturally aprovados e concluídos.

## Estado técnico

As entregas acima são documentais. Inventário, skills e runtime possuem rascunhos locais,
mas ainda não constituem entregas aceitas na branch. A cobertura global de 90% não foi
atingida nem demonstrada. Nenhum módulo funcional está concluído.

## Etapas

| Etapa | Estado | Critério |
|---|---|---|
| 1 Inventário e segurança | Em andamento | Todos os arquivos classificados e bugs reproduzidos |
| 2 Convenções, skills e CI | Em andamento | Verificações reproduzíveis |
| 3 Runtime atual sem .env | Pendente | Startup validado e segredos persistentes |
| 4 Schema e módulos | Pendente | Constraints e arquitetura testadas |
| 5 Identity | Pendente | Sessão, recuperação, social e cobertura >=90% |
| 6 Recipes e Media | Pendente | Paridade e cobertura >=90% |
| 7 Community | Pendente | Paridade e cobertura >=90% |
| 8 Cache, filas e observabilidade | Pendente | Falhas e concorrência verificadas |
| 9 Remoção do legado | Pendente | Rastreabilidade completa e cobertura global >=90% |
| 10 AKS e CD | Pendente | Deploy, rotação e recuperação demonstrados |
| 11 Homologação | Pendente | Contrato e documentação de integração concluídos |

A implantação real requer assinatura Azure, domínio e aplicações OAuth configuradas.
Nenhum serviço externo será apresentado como homologado apenas por testes simulados.
