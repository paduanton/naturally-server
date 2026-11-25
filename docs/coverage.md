# Cobertura durante a modernização

`runtime/phpunit.xml` valida runtime e arquitetura sem precisar conectar ao banco.
`runtime/phpunit.services.xml` executa a aceitação MySQL/Redis. A suíte conjunta
`runtime/phpunit.foundation.xml` registra a união desses testes, sem duplicações.
Os filtros incluem app, config, bootstrap, routes, public e artisan, inclusive arquivos
nunca executados. Supressões de cobertura estão desabilitadas. Testes verificam esses
contratos para evitar perder fontes ou cenários ao adicionar incrementos.

Depois de preparar os serviços isolados com o fluxo de desenvolvimento aprovado:

```powershell
docker compose -p naturally-modern-tests -f compose.modern.yml run --rm -e NATURALLY_CONFIG_PATH=/app/settings/testing.json -e XDEBUG_MODE=coverage tools php -d memory_limit=512M vendor/bin/phpunit --configuration phpunit.foundation.xml --coverage-text=/run/runtime/coverage/report.txt --coverage-html=/run/runtime/coverage/html --coverage-clover=/run/runtime/coverage/clover.xml --coverage-jsonl=/run/runtime/coverage/branches
docker run --rm --network none --read-only --mount type=volume,source=naturally-modern-tests_app-runtime,target=/run/runtime,readonly --mount type=bind,source=./tools/coverage,target=/coverage-tools,readonly naturally-php-development:local php /coverage-tools/check.php /run/runtime/coverage/report.txt
```

Os relatórios ficam no volume de runtime do projeto de testes, fora do checkout.
O CI gera HTML, Clover e JSONL com dados de branches e conserva os relatórios por sete
dias após uma varredura de segredos. Não exporta o volume inteiro nem os segredos.
Os formatos publicados não são evidência de uma implantação Azure.

O verificador exige 90% de linhas e 90% dos branches disponíveis no relatório combinado.
Usa os contadores reais, sem confiar nos percentuais arredondados. Seu JSON identifica
explicitamente arquivos sem dados de branches e sempre informa que não certifica a
release global. `--require-complete-branches` também rejeita qualquer lacuna de coleta;
essa aceitação estrita continua pendente enquanto as entradas executadas em subprocessos
não forem agregadas. Não tratar o gate de métricas observadas como certificação final.

O legado ainda não está coberto por essa medição. A conclusão exige o gate global e
evidências de cada módulo previstos no plano, sem ocultar o código restante. Migrations
têm aceitação real; branch metadata ausente não será inventada a partir dessas assertivas.
Ferramentas de desenvolvimento e do verificador são medidas separadamente, sem somar
sua cobertura à aplicação. Shell/PowerShell e execução em processos filhos não estão
agregados à cobertura PHP dessas ferramentas.

Testes isolados do verificador, sem rede ou segredos montados:

```powershell
docker run --rm --network none --read-only --tmpfs /tmp:mode=1777 --mount type=volume,source=naturally-modern-tests_vendor,target=/app/vendor,readonly --mount type=bind,source=./tools/coverage,target=/coverage-tools,readonly naturally-php-development:local php vendor/bin/phpunit --configuration /coverage-tools/phpunit.xml --no-coverage
```

Status do CLI: 0 atende ao gate solicitado; 1 está abaixo do limiar ou falha na coleta
estrita; 2 é relatório/argumento inválido. Erros não imprimem o conteúdo do relatório.
Use `./scripts/dev.ps1 down -TestEnvironment` ao encerrar; os volumes são preservados.
