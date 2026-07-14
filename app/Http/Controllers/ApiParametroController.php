<?php

namespace App\Http\Controllers;

use App\Models\ApiParametro;
use App\Services\Jacad\JacadClient;
use Illuminate\Http\Request;

class ApiParametroController extends Controller
{
    public function index()
    {
        $parametros = ApiParametro::orderBy('nome')->get();

        return view('parametros.index', compact('parametros'));
    }

    public function edit(ApiParametro $parametro)
    {
        return view('parametros.edit', compact('parametro'));
    }

    public function update(Request $request, ApiParametro $parametro)
    {
        $dados = $request->validate([
            'descricao' => ['nullable', 'string', 'max:255'],
            'base_url' => ['required', 'url', 'max:255'],
            'usuario' => ['nullable', 'string', 'max:255'],
            'token' => ['nullable', 'string', 'max:1000'],
            'page_size' => ['required', 'integer', 'min:1', 'max:1000'],
            'ativo' => ['nullable', 'boolean'],
        ]);
        $dados['ativo'] = $request->boolean('ativo');

        $parametro->update($dados);

        return redirect()->route('parametros.index')
            ->with('sucesso', "Parâmetros de '{$parametro->nome}' atualizados.");
    }

    /** Testa a conexão/autenticação com a API. */
    public function testar(ApiParametro $parametro)
    {
        $resultado = (new JacadClient($parametro->nome))->testarConexao();

        return $resultado['ok']
            ? back()->with('sucesso', 'Conexão OK: '.$resultado['mensagem'])
            : back()->with('erro', "Falha na conexão (HTTP {$resultado['status']}): ".$resultado['mensagem']);
    }
}
