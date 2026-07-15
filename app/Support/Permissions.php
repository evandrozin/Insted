<?php

namespace App\Support;

/**
 * Catálogo central das permissões (funcionalidades) do sistema.
 *
 * Cada constante é a "chave" gravada em users.permissions e usada
 * como habilidade (Gate) nas rotas, controllers e views (@can).
 */
final class Permissions
{
    public const MATRICULAS_VER = 'matriculas.ver';

    public const REMATRICULA_VER = 'rematricula.ver';

    public const DADOS_SINCRONIZAR = 'dados.sincronizar';

    public const PARAMETROS_GERENCIAR = 'parametros.gerenciar';

    public const USUARIOS_GERENCIAR = 'usuarios.gerenciar';

    public const LOGS_ACESSO_VER = 'logs.acesso.ver';

    /**
     * Todas as permissões, agrupadas para exibição no formulário de usuário.
     *
     * @return array<string, array<string, string>> grupo => [chave => rótulo]
     */
    public static function agrupadas(): array
    {
        return [
            'Acadêmico' => [
                self::MATRICULAS_VER => 'Acessar dados de matrícula (e exportar)',
                self::REMATRICULA_VER => 'Acessar dados de rematrícula (e exportar)',
            ],
            'Integração' => [
                self::DADOS_SINCRONIZAR => 'Sincronizar dados com o JACAD',
                self::PARAMETROS_GERENCIAR => 'Gerenciar parâmetros de API',
            ],
            'Administração' => [
                self::USUARIOS_GERENCIAR => 'Gerenciar usuários e permissões',
                self::LOGS_ACESSO_VER => 'Ver logs de acesso (login/logout)',
            ],
        ];
    }

    /**
     * Lista plana de todas as chaves de permissão.
     *
     * @return list<string>
     */
    public static function todas(): array
    {
        $chaves = [];
        foreach (self::agrupadas() as $grupo) {
            foreach ($grupo as $chave => $rotulo) {
                $chaves[] = $chave;
            }
        }

        return $chaves;
    }

    /** Rótulo legível de uma permissão. */
    public static function rotulo(string $chave): string
    {
        foreach (self::agrupadas() as $grupo) {
            if (isset($grupo[$chave])) {
                return $grupo[$chave];
            }
        }

        return $chave;
    }
}
