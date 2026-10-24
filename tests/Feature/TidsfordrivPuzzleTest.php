<?php
namespace Tests\Feature;
use App\Services\TidsfordrivPuzzleBank;
use Tests\TestCase;
class TidsfordrivPuzzleTest extends TestCase {
 public function test_overview_lists_every_pastime_and_old_links_remain_available(): void {
  $this->get('/tidsfordriv')->assertOk()->assertSee('Binærpuslespill')->assertSee('Nonogram')->assertSee('Tallpyramider')->assertSee('Labyrinter')->assertSee('Minikryssord')->assertSee('Prikker og streker')->assertSee('Ordjakt')->assertSee('Sudoku')->assertSee('Spillforslag');
  $this->get('/ordjakt')->assertOk();
 }
 public function test_local_tasks_have_stable_printable_answer_keys(): void {
  foreach (['binaer','nonogram','tallpyramide','labyrint','minikryssord','prikker-og-streker'] as $type) {
   $puzzle=app(TidsfordrivPuzzleBank::class)->get($type);
   $this->get('/tidsfordriv/'.$type.'?oppgave='.$puzzle['id'])->assertOk()->assertSee($puzzle['id'],false);
   $this->get('/tidsfordriv/'.$type.'/utskrift?oppgave='.$puzzle['id'])->assertOk()->assertDontSee(' – fasit');
   $this->get('/tidsfordriv/'.$type.'/utskrift?oppgave='.$puzzle['id'].'&fasit=1')->assertOk()->assertSee(' – fasit');
  }
 }
 public function test_binary_solution_obeys_core_rules(): void {
  foreach (app(TidsfordrivPuzzleBank::class)->ids('binaer') as $id) {
   $grid=app(TidsfordrivPuzzleBank::class)->get('binaer',$id)['solution'];
   foreach (array_merge($grid,array_map(null,...$grid)) as $line) {
    $this->assertSame(count($line)/2,array_sum($line));
    foreach (array_chunk($line,3) as $triple) $this->assertNotSame(3,count(array_unique($triple)));
   }
  }
 }
}
