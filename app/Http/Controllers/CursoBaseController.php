<?php

namespace App\Http\Controllers;

use App\Models\CursoBase;
use Illuminate\Http\Request;

class CursoBaseController extends Controller
{
    public function index(Request $request)
    {
        $cursos = CursoBase::withCount('matriculas')
            ->when($request->filled('busca'), function ($q) use ($request) {
                $b = trim($request->busca);
                $q->where(function ($w) use ($b) {
                    $w->where('nome_impressao', 'ilike', "%{$b}%")
                        ->orWhere('nome_reduzido', 'ilike', "%{$b}%")
                        ->orWhere('codigo_curso', 'ilike', "%{$b}%")
                        ->orWhere('modalidade', 'ilike', "%{$b}%");
                });
            })
            ->when($request->filled('modalidade'), fn ($q) => $q->where('modalidade', $request->modalidade))
            ->orderBy('nome_impressao')
            ->paginate(30)->withQueryString();

        $modalidades = CursoBase::whereNotNull('modalidade')
            ->distinct()->orderBy('modalidade')->pluck('modalidade');

        return view('cursos.index', compact('cursos', 'modalidades'));
    }
}
