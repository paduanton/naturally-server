---
name: aks-delivery
description: Preparar e revisar infraestrutura, manifests e CI/CD do Naturally no AKS, com identidade federada, segredos montados, migrations e recuperação verificável.
---

# Entrega do Naturally no AKS

Leia AGENTS.md, docs/architecture.md e docs/modernization/status.md. A implantação real
é etapa posterior à estabilização do BFF; rascunhos de runtime e manifests não comprovam
prontidão. Use Bicep para infraestrutura e Kustomize para os recursos da aplicação.

## Artefatos e identidades

- Mantenha desenvolvimento local com Compose independente da Azure. No perfil AKS do plano,
  use ACR, Key Vault, MySQL Flexible Server e Blob Storage; Redis interno é cache descartável.
  Sessões e filas ficam no MySQL. Dados persistentes não ficam no filesystem do pod da API.
- Construa a imagem PHP da aplicação modernizada, usada pela API e pelos workers. Nginx
  fica em container separado no pod da API. Inclua somente runtime aceito, dependências
  de produção e artefatos necessários; exclua legado, plano privado, caches e segredos.
- Promova o mesmo artefato aprovado por digest, com SBOM, análise de vulnerabilidades e
  assinatura/atestação verificada. Não reconstrua a aplicação para inserir configuração
  de cada ambiente. Actions devem ter SHA fixo e permissões mínimas por job.
- CI de PR executa checks sem acesso de deploy ou segredos de produção; não execute código
  não confiável em jobs privilegiados. Separe validação, build/publicação e deploy.
- Use OIDC do GitHub para Azure com confiança restrita ao repositório e branch/ambiente
  autorizados. id-token: write permite emitir o token, não substitui RBAC no Azure.
  Separe identidades de infraestrutura e de deploy; limite seu escopo. Consulte
  [OIDC no GitHub](https://docs.github.com/en/actions/reference/security/oidc).
- Pods usam Workload Identity e Key Vault via CSI, sem credencial de serviço embutida.
  JSON não sensível vem de ConfigMap; segredos vêm de arquivos somente leitura, sem subPath.
  Siga o contrato de runtime-secrets para validação, cache de configuração e rotação.

## Manifests e operação

Configure usuário sem root, filesystem raiz somente leitura, volumes graváveis restritos,
capabilities mínimas, bloqueio de privilege escalation e políticas de rede compatíveis
com o CNI. Declare CPU/memória solicitadas e limites coerentes com medições.

Use probes com finalidades distintas: startup para inicialização, readiness para capacidade
de atender e liveness para detectar processo irrecuperável. Não ligue liveness a uma falha
transitória do banco que reiniciaria todas as réplicas. Justifique cada dependência verificada.
Consulte [probes Kubernetes](https://kubernetes.io/docs/concepts/workloads/pods/probes/).

Mantenha maxUnavailable: 0 no rollout da API, conforme o plano, e capacidade para os pods
adicionais. Configure encerramento gracioso e tempo para concluir requisições/jobs; essa
opção isolada não garante ausência de interrupções. Workers precisam lidar com reentrega,
idempotência e retries limitados. Scheduler usa CronJob com política de concorrência explícita.

A entrada HTTP segue Gateway API com application routing do AKS. Na implementação, confira
versões, região, suporte, TLS, DNS e requisitos atuais do
[add-on](https://learn.microsoft.com/en-us/azure/aks/app-routing-gateway-api).
Não confunda Nginx que serve a aplicação com um controlador ingress-nginx.

## Sequência de deploy e recuperação

Antes da execução, apresente destino, recursos, permissões, artefato por digest, mudanças
de configuração, estimativa de custo e procedimento de recuperação para revisão. Não
crie recursos faturáveis nem publique na Azure sem aprovação concreta conforme AGENTS.md.

Depois da aprovação aplicável: valide fontes de configuração, execute migrations por Job
exclusivo de deploy e só então inicie o rollout da aplicação. Serialize deploys do mesmo
ambiente. Migrations devem ser compatíveis com as versões antiga e nova (expand/contract);
falha ou timeout interrompe a entrega. Não rode migrations destrutivas no startup dos pods.

Confirme rollout e smoke tests por um prazo limitado. Em falha, preserve diagnósticos sem
segredos e aplique apenas recuperação compatível com schema/configuração. Rollback de
imagem não autoriza downgrade destrutivo do banco nem restaura credenciais revogadas.

## Evidências e custo

Valide Bicep, manifests renderizados e workflows com tooling aceito; ao introduzir uma
ferramenta, registre versão e comando reproduzível. Validação estática não comprova RBAC,
conectividade, TLS ou funcionamento do cluster. Registre separadamente testes locais e
resultados reais de deploy, rotação, rollout interrompido, backup e restauração.

Documente ambientes sob demanda, tags, budgets e retenção curta de logs. Estime recursos
que continuam cobrando com o cluster parado e preserve dados necessários na desmontagem.
Não presuma custo zero. Commit, push, deploy e remoção de recursos são ações distintas,
sujeitas ao escopo aprovado. Origem e referências: docs/agents/provenance.md.
