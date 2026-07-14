<?php

namespace App\Services\Jacad;

use App\Models\ApiParametro;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Cliente HTTP para a API de Integração JACAD / SWA (Insted).
 *
 * Fluxo de autenticação:
 *   POST /api/v1/auth/token   (header: token: <chave de API>)  -> retorna access token (JWT) + expiresIn
 *   Demais chamadas          (header: Authorization: <access token>)
 *
 * A configuração (base_url, token) vem da tabela `api_parametros` (nome=jacad),
 * com fallback para config/jacad.php.
 */
class JacadClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $pageSize;
    protected int $timeout;
    protected int $tokenSkew;

    public function __construct(protected string $integracao = 'jacad')
    {
        $param = ApiParametro::porNome($integracao);

        $this->baseUrl = rtrim($param->base_url ?? config('jacad.base_url'), '/');
        $this->apiKey = $param->token ?? config('jacad.token');
        $this->pageSize = $param->page_size ?? config('jacad.page_size', 200);
        $this->timeout = (int) config('jacad.timeout', 60);
        $this->tokenSkew = (int) config('jacad.token_skew', 60);
    }

    public function pageSize(): int
    {
        return $this->pageSize;
    }

    /** Chave de cache do access token (por integração + chave de API). */
    protected function cacheKey(): string
    {
        return 'jacad:access_token:'.$this->integracao.':'.md5($this->apiKey);
    }

    /**
     * Retorna um access token válido, autenticando (e cacheando) se necessário.
     */
    public function accessToken(bool $forcar = false): string
    {
        if (! $forcar && ($tk = Cache::get($this->cacheKey()))) {
            return $tk;
        }

        $resp = Http::timeout($this->timeout)
            ->acceptJson()
            ->withHeaders(['token' => $this->apiKey])
            ->post("{$this->baseUrl}/api/v1/auth/token");

        if ($resp->failed()) {
            throw new JacadException(
                "Falha ao autenticar no JACAD (HTTP {$resp->status()}): ".$resp->body(),
                $resp->status()
            );
        }

        $token = $resp->json('token');
        if (! $token) {
            throw new JacadException('Resposta de autenticação sem campo "token": '.$resp->body());
        }

        // expiresIn costuma vir em segundos (fallback: 30 min).
        $expiresIn = (int) ($resp->json('expiresIn') ?: 1800);
        $ttl = max(60, $expiresIn - $this->tokenSkew);
        Cache::put($this->cacheKey(), $token, $ttl);

        return $token;
    }

    /**
     * GET autenticado. Renova o token uma vez em caso de 401.
     */
    public function get(string $path, array $query = []): Response
    {
        $exec = fn (string $tk) => Http::timeout($this->timeout)
            ->acceptJson()
            ->withHeaders(['Authorization' => $tk])
            ->get($this->baseUrl.'/'.ltrim($path, '/'), $query);

        $resp = $exec($this->accessToken());

        if ($resp->status() === 401) {
            $resp = $exec($this->accessToken(forcar: true));
        }

        if ($resp->failed()) {
            throw new JacadException(
                "Erro na chamada GET {$path} (HTTP {$resp->status()}): ".$resp->body(),
                $resp->status()
            );
        }

        return $resp;
    }

    /**
     * Testa a autenticação sem lançar exceção — usado pelo painel/diagnóstico.
     *
     * @return array{ok: bool, status: int, mensagem: string}
     */
    public function testarConexao(): array
    {
        try {
            $this->accessToken(forcar: true);

            return ['ok' => true, 'status' => 200, 'mensagem' => 'Autenticação bem-sucedida.'];
        } catch (JacadException $e) {
            return ['ok' => false, 'status' => $e->getCode() ?: 0, 'mensagem' => $e->getMessage()];
        }
    }
}
