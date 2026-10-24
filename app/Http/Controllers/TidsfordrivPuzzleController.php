<?php
namespace App\Http\Controllers;
use App\Services\TidsfordrivPuzzleBank;
use Illuminate\Http\Request;
class TidsfordrivPuzzleController extends Controller {
 public function show(string $type, Request $request, TidsfordrivPuzzleBank $bank) { $p=$bank->get($type,(string)$request->query('oppgave')); return view('tidsfordriv.puzzle',['type'=>$type,'meta'=>$bank->types()[$type], 'puzzle'=>$p,'ids'=>$bank->ids($type)]); }
 public function print(string $type, Request $request, TidsfordrivPuzzleBank $bank) { $p=$bank->get($type,(string)$request->query('oppgave')); return view('tidsfordriv.puzzle-print',['type'=>$type,'meta'=>$bank->types()[$type], 'puzzle'=>$p,'answer'=>$request->boolean('fasit')]); }
}
