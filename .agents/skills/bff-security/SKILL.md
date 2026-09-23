---
name: bff-security
description: Implementar e revisar autenticação por sessão, autorização por recurso e login social do BFF Naturally, com testes de abuso e isolamento de usuários.
---

# Segurança de identidade e endpoints

Leia AGENTS.md, docs/architecture.md e docs/modernization/status.md. Confira o fluxo e os
achados de docs/modernization/bugs.md antes de mudar comportamento. Os controles abaixo
são o destino do plano; não presuma que já existam no legado ou nos rascunhos de runtime.

## Sessão do navegador

Use Sanctum em modo SPA, Fortify para credenciais locais e Socialite para provedores.
Frontend e BFF compartilham a origem pública; a sessão fica no MySQL compartilhado entre
réplicas. Não emita bearer/refresh tokens próprios para o navegador nem devolva tokens
externos no login. Consulte [Sanctum SPA](https://laravel.com/docs/13.x/sanctum#spa-authentication).

- Cookie de sessão HttpOnly, Secure em produção, SameSite=Lax e sem Domain compartilhado.
  O cookie CSRF deve poder ser lido pelo cliente para envio do cabeçalho; não o confunda
  com a credencial de sessão. Não armazene autenticação em localStorage ou URLs.
- Preserve o middleware de proteção CSRF nas operações de escrita, login e logout;
  não crie exceções amplas para fazer a integração passar. Configure origens stateful e
  proxies confiáveis explicitamente. CORS não substitui autenticação nem autorização.
- Regenere o identificador após login. No logout, invalide a sessão e renove o token CSRF.
  O plano exige 2 horas de inatividade e 12 horas absolutas: não assuma que um único TTL
  atende aos dois limites. Lembrar-me fica fora da primeira versão modernizada.
- Exija autenticação recente em mudanças de senha, e-mail e vínculos sociais; preveja
  reautenticação adequada também para contas sociais sem senha local. Revogue sessões
  após recuperação de senha, exclusão e restauração da conta, conforme o plano.

## Credenciais e permissões

- Use os serviços de hashing e recuperação do framework. O plano adota Argon2id calibrado,
  mínimo de 15 caracteres sem regras artificiais de composição, senhas longas e recuperação
  com token armazenado por hash, uso único e validade de 30 minutos. Verifique consumo
  concorrente; não suponha que uma sequência ler/validar/excluir seja atômica.
- Verifique e-mail e aplique limites por conta/IP. Mensagens de recuperação não revelam
  existência da conta. Falha do limitador não pode liberar autenticação sem controle.
- A identidade do autor vem da sessão, nunca de campos protegidos do request. Form Requests
  validam entrada; Policies autorizam recursos e relacionamentos aninhados. Carregar um ID
  existente não comprova propriedade, visibilidade ou vínculo com o recurso pai.
- Application recebe a identidade por DTO e coordena a operação; Domain protege invariantes.
  Mantenha Request, sessão e Eloquent fora dos contratos públicos entre módulos. Outros
  chamadores de casos de uso também devem respeitar as permissões, sem depender do controller.
- Resources usam allowlist. Não serialize hashes, remember_token, tokens de recuperação
  ou credenciais sociais; respostas pessoais usam Cache-Control: no-store.

## Provedores sociais

Processe Authorization Code no servidor. Valide state de uso único, com expiração e vínculo
à sessão; use PKCE no X e onde suportado pelos demais provedores conforme o plano. Não
use stateless() para contornar a proteção do fluxo de navegador. Se houver ID token,
valide assinatura, emissor, audiência, expiração e nonce com biblioteca apropriada.
Restrinja retornos a destinos locais autorizados e rejeite callbacks inválidos ou repetidos.

Identifique contas por (provider, provider_user_id), com unicidade na persistência. Não
vincule por coincidência de e-mail. E-mail ausente ou não confiável exige conclusão e
verificação do cadastro. Vinculação é explícita com autenticação recente; impedir remoção
do último método de entrada exige proteção contra concorrência. Descarte tokens externos
após obter a identidade. Adapte os exemplos do [Socialite](https://laravel.com/docs/13.x/socialite)
a estas regras e confira a [segurança OAuth](https://www.rfc-editor.org/rfc/rfc9700.html).

## Evidências exigidas

Teste login/logout, expiração e revogação, fixação de sessão, acesso cruzado entre usuários,
campos protegidos, reutilização de recuperação e vínculos concorrentes. Exercite a proteção
CSRF efetiva, inclusive requisições hostis de outra origem; não conclua segurança com
middleware desabilitado pelo ambiente de testes. Confira a versão instalada e a
[documentação de CSRF](https://laravel.com/docs/13.x/csrf).

Simule provedores externos em CI e teste state inválido/expirado/repetido, indisponibilidade,
redirect indevido e e-mail coincidente. Use MySQL real para sessões e concorrência;
comprove compartilhamento entre réplicas na validação operacional. Não chame homologação
real de concluída apenas com mocks. Não registre valores sensíveis em testes ou logs.
Correções exigem regressão anterior e conclusão exige cobertura conforme AGENTS.md.
Origem: docs/agents/provenance.md. Commits, pushes e ações externas seguem as aprovações do projeto.
