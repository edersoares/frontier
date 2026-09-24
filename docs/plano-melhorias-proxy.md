# Plano de melhorias do proxy do Frontier

Origem: análise do uso do Frontier no i-Educar (UI Nuxt do `pulsare` sob `/new`), em 24/09/2026.
Premissa: mudanças pequenas, uma por PR, cada uma com teste em `tests/FrontendProxyControllerTest.php`
e entrega de valor isolada. A ordem abaixo é a ordem de execução, pois cada tarefa usa a anterior.

| # | Tarefa | Valor entregue | Esforço | Depende de |
|---|--------|----------------|---------|------------|
| 0 | Corrigir parsing de `cache` e `methods(...)` nas regras | Regras compostas passam a funcionar em qualquer ordem | 1h | – |
| 1 | Repassar o status HTTP do host | 404/500 do CDN deixam de virar 200 | 2h | 0 |
| 2 | Timeout explícito e resposta 504 em falha de conexão | CDN lento não prende workers do PHP-FPM | 2h a 3h | 1 |
| 3 | Cache via `Cache` facade (Redis) por host+uri com TTL | Cache utilizável em produção, compartilhado entre servidores | 3h | 1 |
| 4 | Fallback para a última cópia boa (stale-on-error) | CDN fora do ar não derruba a UI | 3h | 2, 3 |
| 5 | Host resolvido em tempo de requisição | Permite fixar versão da UI por tenant | 2h | – |

Total estimado: 2 a 3 dias, incluindo docs e release.

Fora do escopo desta rodada: `FrontendHttpController` tem os mesmos problemas de status e cache em
arquivo. Após as tarefas 1 e 3, avaliar se vale alinhar ou depreciar o tipo `http` em favor do `proxy`.

---

## Tarefa 0: corrigir parsing das regras

**Problema.** Em `src/Frontier.php`, dentro do `foreach ($segments as $segment)`, as linhas
`$cache = false;` e `$methods = ['GET'];` rodam a cada iteração. Resultado: `cache` e `methods(...)`
só têm efeito quando são o último segmento da regra. Os testes atuais passam por coincidência,
porque `/with-cache::cache` e `/all-methods::methods(...)` têm um único segmento após a uri.

**Mudança.**
- Mover `$cache = false;` e `$methods = ['GET'];` para fora do loop, junto das outras inicializações.
- Trocar o `if` de `methods(` para sobrescrever `$methods` apenas quando o segmento existir.

**Testes.**
- Nova regra no `beforeEach`: `/cache-first::cache::middleware(web)`. Teste: primeira chamada grava
  cache, segunda chamada não dispara HTTP (`Http::assertSentCount(1)`).
- Nova regra: `/methods-first::methods(get,post)::replace(a,b)`. Teste: `POST` responde 200 e
  `PUT` responde 405.
- Suite atual continua verde.

**Docs.** Nenhuma. É correção de comportamento já documentado.

**Critério de pronto.** Testes verdes, `composer lint` limpo, PR único, patch release.

---

## Tarefa 1: repassar o status HTTP do host

**Problema.** `FrontendProxyController::__invoke` faz `new Response($content, headers: [...])`, então
qualquer resposta do host sai como 200. Um 404 de rota inexistente no CDN ou um 500 do servidor Nuxt
chegam ao navegador e a monitoramento como sucesso.

**Mudança.**
- `new Response($content, $response->status(), ['content-type' => $contentType])`.
- Não gravar cache quando `$response->failed()` (aplica ao cache em arquivo atual e ao da tarefa 3).
- Corrigir a variável `$contextType` para `$contentType` de passagem.

**Testes.**
- `Http::response('Not found', 404)` em `/web/missing` → `assertNotFound()` e corpo repassado.
- `Http::response('Boom', 500)` → `assertStatus(500)`.
- `Http::response('Boom', 500)` em `/with-cache` → arquivo de cache não existe após a chamada.
- Testes existentes de 200 continuam verdes.

**Risco.** Redirecionamentos 3xx são seguidos pelo HTTP client, então o status final é o da última
resposta. Comportamento aceitável e documentado.

**Docs.** README, seção Proxy: "o status HTTP do host é repassado ao cliente".

**Critério de pronto.** Testes verdes, minor release (mudança de comportamento observável).

---

## Tarefa 2: timeout explícito e 504 em falha de conexão

**Problema.** `Http::withHeaders(...)` usa o timeout padrão do client (30s de request, sem
connect timeout). Cada requisição sob `/new` ocupa um worker do PHP-FPM enquanto espera o CDN.

**Mudança.**
- `config/frontier.php`, chave `proxy`: `'timeout' => env('FRONTIER_PROXY_TIMEOUT', 5)` e
  `'connect_timeout' => env('FRONTIER_PROXY_CONNECT_TIMEOUT', 2)`.
- `Frontier::proxy()` copia `timeout` e `connect_timeout` do `$config` para os defaults da rota.
- Controller: `Http::withHeaders(...)->timeout($config['timeout'])->connectTimeout($config['connect_timeout'])`.
- Envolver o `match` em `try/catch (ConnectionException)` e devolver `new Response('', 504)`.
  Na tarefa 4 esse ponto vira o gatilho do fallback.

**Testes.**
- `Http::fake(fn () => throw new ConnectionException('timeout'))` em `/web` → `assertStatus(504)`.
- Teste unitário de configuração em `tests/Unit/DefaultConfigurationTest.php`: sem env, `timeout`
  é 5 e `connect_timeout` é 2; com `config()` sobrescrito, o valor chega ao default da rota
  (`Route::getRoutes()->match(...)->defaults['config']['timeout']`).

**Risco.** 5s pode ser curto para o primeiro hit num CDN frio. O valor é env, e a tarefa 3
faz o hit ser raro.

**Docs.** README, tabela de env: `FRONTIER_PROXY_TIMEOUT`, `FRONTIER_PROXY_CONNECT_TIMEOUT`.

**Critério de pronto.** Testes verdes, minor release. No i-Educar, definir as duas envs em staging
e observar logs de 504 por uma semana antes de produção.

---

## Tarefa 3: cache via `Cache` facade por host e uri

**Problema.** O cache atual grava `storage/framework/views/frontier-{método}-{host}` em disco.
É por servidor, a chave ignora a uri, então `/new/a` e `/new/b` disputam o mesmo arquivo, e não
há expiração. Produção não o usa.

**Mudança.**
- Config `proxy`: `'cache_store' => env('FRONTIER_PROXY_CACHE_STORE')` (null usa o store padrão)
  e `'cache_ttl' => env('FRONTIER_PROXY_CACHE_TTL', 60)` em segundos.
- `Frontier::proxy()` repassa `cache_store` e `cache_ttl` para os defaults da rota.
- Controller: só cacheia `GET` com resposta 2xx. Chave `frontier:proxy:` + `sha1($url)` onde `$url`
  já inclui host e uri após o rewrite. Usa `Cache::store($config['cache_store'])->put($key, $content, $ttl)`
  e `->get($key)` no início.
- Guardar também o content-type junto do conteúdo (array `['content' => ..., 'content_type' => ...]`),
  para o hit servir o header correto.
- Header `X-Frontier-Cache: hit|miss` na resposta, para verificar em produção com `curl -I`.
- Remover o cache em arquivo. O teste `proxy and do cache` passa a checar a `Cache` em vez do arquivo.

**Testes.** Todos com `config(['cache.default' => 'array'])` no `beforeEach`.
- Duas chamadas a `/with-cache` → `Http::assertSentCount(1)` e segunda resposta com header `hit`.
- `/with-cache/a` e `/with-cache/b` → `Http::assertSentCount(2)` (chave por uri).
- `$this->travel(61)->seconds()` após a primeira chamada → nova chamada dispara HTTP de novo.
- Resposta 500 não grava chave (`Cache::has($key)` falso).
- Content-type do hit igual ao da resposta original.

**Risco.** Quem dependia de `view:clear` para limpar o cache perde esse atalho. Documentar
`cache:clear` ou o uso de um store dedicado (`FRONTIER_PROXY_CACHE_STORE=frontier`) que possa ser
limpo isoladamente.

**Docs.** README: envs novas, semântica da chave, header `X-Frontier-Cache`, como invalidar.

**Critério de pronto.** Testes verdes, minor release. No i-Educar, ativar `::cache` na regra de `/new`
em staging com TTL de 60s e confirmar `hit` no header.

---

## Tarefa 4: fallback para a última cópia boa

**Problema.** Com as tarefas 2 e 3, uma falha do CDN vira 504 ou 5xx para o usuário. A SPA tem um
único shell HTML, então a última cópia boa serve perfeitamente enquanto o CDN não volta.

**Mudança.**
- Ao gravar o cache fresco (tarefa 3), gravar também uma cópia "stale" com chave
  `frontier:proxy:stale:` + `sha1($url)` e TTL longo, `'cache_stale_ttl' => env('FRONTIER_PROXY_CACHE_STALE_TTL', 86400)`.
- No `catch (ConnectionException)` e quando `$response->serverError()`: se a cópia stale existir,
  devolver 200 com o conteúdo dela e header `X-Frontier-Cache: stale`. Se não existir, manter o
  comportamento das tarefas 1 e 2 (repassar 5xx ou 504).
- 4xx do host não aciona fallback. É resposta legítima.

**Testes.**
- Primeira chamada 200 grava fresco e stale. `travel(61s)`. `Http::fake` passa a devolver 500 →
  `assertOk()`, conteúdo antigo, header `stale`.
- Mesmo cenário com `ConnectionException` → `assertOk()` com header `stale`.
- Sem cache prévio, 500 → `assertStatus(500)`; `ConnectionException` → `assertStatus(504)`.
- 404 do host com stale existente → `assertNotFound()` (sem fallback).

**Risco.** Servir HTML antigo que referencia assets já removidos do CDN. Mitigado porque o CDN
mantém builds anteriores e o TTL stale de 24h é curto o bastante para não sobreviver a um ciclo
normal de publicação. Ajustável por env.

**Docs.** README: env nova e semântica dos três valores do header.

**Critério de pronto.** Testes verdes, minor release. Validar em staging derrubando o host de proxy
propositalmente e conferindo que `/new` continua abrindo.

---

## Tarefa 5: host resolvido em tempo de requisição

**Problema.** Publicar no CDN troca a UI de todos os tenants de uma vez, enquanto a API muda tenant
a tenant. O `host` da regra é uma string fixa lida no boot, então não há como o app apontar cada
tenant para uma versão da UI.

**Mudança.** A menor que abre a porta sem a lib conhecer tenant:
- `Frontier::resolveUrlUsing(?Closure $resolver)`: recebe `(string $url, Request $request, array $config)`
  e devolve a URL final. Guardado em propriedade estática. Padrão null.
- Controller: após montar `$url` e aplicar `rewrite`, chama o resolver se existir.
- A chave de cache já usa `$url` final, então o cache separa versões automaticamente.
- O i-Educar registra o resolver em um provider, lendo a versão da UI da tabela `settings` do
  tenant e trocando um placeholder ou prefixo de versão na URL. Isso fica do lado do app.

**Testes.**
- Registrar resolver que troca `frontier.test` por `v2.frontier.test`; `Http::fake` só para `v2.*`;
  chamada a `/web` → `assertOk()` e `Http::assertSent` com a URL `v2`.
- Sem resolver, comportamento inalterado (suite atual).
- Resolver é limpo entre testes (`Frontier::resolveUrlUsing(null)` em `afterEach`).

**Docs.** README: seção "Resolving the proxy URL at runtime" com exemplo de versão por tenant.

**Critério de pronto.** Testes verdes, minor release. Sem dependência das tarefas anteriores, pode
ser feita em paralelo.

---

## Sequência sugerida de PRs e releases

1. PR "Fix rule parsing for cache and methods" → patch.
2. PR "Forward upstream status code" → minor.
3. PR "Add proxy timeouts" → minor.
4. PR "Cache proxy responses via Cache facade" → minor. Aqui o i-Educar passa a usar `::cache` em staging.
5. PR "Serve stale copy when upstream fails" → minor.
6. PR "Allow resolving proxy URL at runtime" → minor, independente.

Cada PR atualiza o README na mesma mudança e roda `composer test` e `composer lint`.
