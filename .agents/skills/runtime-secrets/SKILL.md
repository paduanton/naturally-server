---
name: runtime-secrets
description: Implementar e revisar configuração, carregamento e rotação de segredos do Naturally sem arquivos .env, em desenvolvimento, CI e AKS.
---

# Configuração e segredos em runtime

Leia AGENTS.md, docs/architecture.md e docs/modernization/status.md. As orientações abaixo
são o destino aprovado; confira o que já foi implementado antes de usar comandos de runtime.
Não considere os rascunhos locais uma entrega aceita.

## Fontes e carregamento

- Separe configuração não sensível em JSON de segredos em arquivos montados. JSON público
  significa versionável, não uma rota para expor a configuração interna ao navegador.
- Local: gere credenciais aleatórias uma vez em volume Docker fora do repositório, com
  escrita atômica e permissões restritas. Reinícios devem preservar chaves e dados.
  CI: use valores sintéticos efêmeros por job, com o mesmo contrato de leitura por arquivos.
- AKS: configuração por ConfigMap; segredos no Key Vault, montados pelo Secrets Store CSI
  Driver com Workload Identity e acesso mínimo. Mounts da aplicação são somente leitura,
  sem subPath, conforme o plano. Desenvolvimento local não depende de acesso ao Azure.
- Centralize parsing e validação antes do bootstrap. Valide tipos, campos obrigatórios,
  ambiente e opções incompatíveis, incluindo debug ativo em produção. Configuração inválida
  ou segredo obrigatório ausente impedem o startup, sem fallback para credenciais padrão.
- O código consome configuração tipada/config(); não espalhe env() ou leituras de arquivos.
  Variáveis para localizar arquivos podem conter caminhos, nunca os valores dos segredos.
  Não gere .env, .env.example ou cópias equivalentes no fluxo modernizado.
- Não grave credenciais em código, factories, logs, argumentos, imagens ou artefatos de CI.
  Testes geram seus valores sintéticos em runtime. Evite dumps de configuração completa;
  erros podem identificar o campo inválido sem reproduzir seu valor ou o arquivo original.

## Cache e rotação

Quando houver cache de configuração em produção, gere-o no startup em volume temporário
restrito, depois de validar as fontes; ele também contém dados sensíveis. Não inclua esse
cache na imagem. No desenvolvimento, preserve atualização simples da configuração, sem
exigir reconstruir a imagem. Confira a integração do loader customizado com o
[cache de configuração Laravel](https://laravel.com/docs/13.x/configuration#configuration-caching).

Atualização de um arquivo pelo CSI não prova que um processo já consumiu o novo valor.
O projeto aplica mudanças por rollout controlado de API e workers: valide a versão,
atualize referências, regenere a configuração e verifique saúde e conexões antes de
revogar a credencial anterior. Registre versão e resultado, nunca conteúdo.
Consulte a [rotação do CSI no AKS](https://learn.microsoft.com/en-us/azure/aks/csi-secrets-store-configuration-options).

APP_KEY exige procedimento próprio: não regenere a chave a cada boot. Avalie cookies,
dados criptografados e coexistência de pods antigos/novos antes da troca; planeje leitura
com as chaves necessárias, migração e retirada das antigas. Injete o conjunto de chaves
por arquivos, adaptando a [rotação do Laravel](https://laravel.com/docs/13.x/encryption#gracefully-rotating-encryption-keys)
ao contrato sem .env. Não assuma que rollback da imagem restaura credenciais revogadas.

## Evidências de implementação

Teste startup sem .env, JSON inválido, tipos incorretos, arquivo ausente ou ilegível,
rejeição de configuração insegura e ausência de valores sensíveis em erros. Demonstre
preservação após reinício, leitura consistente depois do rollout e recuperação de uma
rotação que falhe. Use dados sintéticos; testes locais não comprovam uma rotação no AKS.

Documente campos, fontes, obrigatoriedade e aplicação das mudanças no incremento de runtime.
Não execute reset destrutivo, rotação real ou deploy sem a autorização correspondente.
Commits e pushes seguem AGENTS.md. Skill específica do projeto; origem em docs/agents/provenance.md.
