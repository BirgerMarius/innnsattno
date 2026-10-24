<?php

namespace App\Services;

/** Local, reviewed tasks. IDs are deliberately stable so print and answer keys match. */
class TidsfordrivPuzzleBank
{
    public function types(): array
    {
        return [
            'binaer' => ['title' => 'Binærpuslespill', 'description' => 'Fyll rutenettet med 0 og 1.', 'sizes' => '6×6 og 8×8'],
            'nonogram' => ['title' => 'Nonogram', 'description' => 'Bruk tallene til å tegne et bilde.', 'sizes' => '5×5 og 10×10'],
            'tallpyramide' => ['title' => 'Tallpyramider', 'description' => 'Hver rute er summen av de to under.', 'sizes' => 'Enkel og krevende'],
            'labyrint' => ['title' => 'Labyrinter', 'description' => 'Finn veien fra start til mål.', 'sizes' => 'Liten og stor'],
            'minikryssord' => ['title' => 'Minikryssord', 'description' => 'Korte, norske ord og ledetråder.', 'sizes' => 'To ulike oppgaver'],
            'prikker-og-streker' => ['title' => 'Prikker og streker', 'description' => 'To spillere lager firkanter.', 'sizes' => 'Spillbrett'],
        ];
    }

    public function get(string $type, string $id = ''): array
    {
        $all = $this->all()[$type] ?? [];
        abort_unless($all, 404);
        return $all[$id] ?? reset($all);
    }

    public function ids(string $type): array { return array_keys($this->all()[$type] ?? []); }

    private function all(): array
    {
        return [
            'binaer' => [
                'lett-6' => ['id'=>'lett-6','difficulty'=>'Lett','size'=>6,'solution'=>[[0,0,1,0,1,1],[0,1,0,1,0,1],[1,0,0,1,1,0],[0,1,1,0,0,1],[1,0,1,0,1,0],[1,1,0,1,0,0]],'given'=>[[null,0,null,null,1,null],[0,null,null,1,null,null],[null,null,0,null,null,0],[null,1,null,null,0,null],[1,null,null,0,null,null],[null,null,0,null,null,0]]],
                'middels-8' => ['id'=>'middels-8','difficulty'=>'Middels','size'=>8,'solution'=>[[0,0,1,0,1,1,0,1],[0,1,0,1,0,0,1,1],[1,0,0,1,1,0,1,0],[0,1,1,0,0,1,0,1],[1,0,1,0,1,0,1,0],[1,1,0,1,0,1,0,0],[0,1,0,0,1,0,1,1],[1,0,1,1,0,1,0,0]],'given'=>[]],
            ],
            'nonogram' => [
                'fem-hjerte' => ['id'=>'fem-hjerte','difficulty'=>'Lett (5×5)','grid'=>[[1,0,1,0,1],[1,1,1,1,1],[0,1,1,1,0],[0,0,1,0,0],[0,0,0,0,0]]],
                'ti-hus' => ['id'=>'ti-hus','difficulty'=>'Middels (10×10)','grid'=>[[0,0,0,1,1,1,1,0,0,0],[0,0,1,1,1,1,1,1,0,0],[0,1,1,0,1,1,0,1,1,0],[1,1,1,1,1,1,1,1,1,1],[0,0,0,1,1,1,1,0,0,0],[0,0,0,1,1,1,1,0,0,0],[0,0,0,1,1,1,1,0,0,0],[0,0,1,1,1,1,1,1,0,0],[0,1,1,1,1,1,1,1,1,0],[1,1,1,1,1,1,1,1,1,1]]],
            ],
            'tallpyramide' => [
                'enkel' => ['id'=>'enkel','difficulty'=>'Lett','solution'=>[[20],[9,11],[4,5,6],[1,3,2,4]],'given'=>[[null],[null,null],[4,null,6],[1,3,null,4]]],
                'utfordring' => ['id'=>'utfordring','difficulty'=>'Krevende','solution'=>[[74],[35,39],[16,19,20],[7,9,10,10],[3,4,5,5,5]],'given'=>[[null],[null,39],[16,null,null],[null,9,null,10],[3,null,5,null,5]]],
            ],
            'labyrint' => [
                'liten' => ['id'=>'liten','difficulty'=>'Lett','grid'=>['#########','#S #    #','#  ### ##','##   #  #','# ### # #','#     #M#','#########']],
                'stor' => ['id'=>'stor','difficulty'=>'Middels','grid'=>['#############','#S    #     #','### # # ### #','#   #   #   #','# ####### ###','#       #   #','##### # ### #','#     #     #','# ### ##### #','#   #      M#','#############']],
            ],
            'minikryssord' => [
                'norsk-1' => ['id'=>'norsk-1','difficulty'=>'Lett','grid'=>['SOL##','##OST','BIL##','##ÅL#','MÅNE#'],'entries'=>[['n'=>1,'word'=>'SOL','clue'=>'Lyser på himmelen','r'=>0,'c'=>0,'d'=>'H'],['n'=>2,'word'=>'OST','clue'=>'Pålegg av melk','r'=>1,'c'=>2,'d'=>'H'],['n'=>3,'word'=>'BIL','clue'=>'Kjører på vei','r'=>2,'c'=>0,'d'=>'H'],['n'=>4,'word'=>'ÅL','clue'=>'Lang fisk','r'=>3,'c'=>2,'d'=>'H'],['n'=>5,'word'=>'MÅNE','clue'=>'Lyser om natten','r'=>4,'c'=>0,'d'=>'H']]],
                'norsk-2' => ['id'=>'norsk-2','difficulty'=>'Lett','grid'=>['HUS##','##ØY#','KAT##','##ÅS#','BÅT##'],'entries'=>[['n'=>1,'word'=>'HUS','clue'=>'Et sted å bo','r'=>0,'c'=>0,'d'=>'H'],['n'=>2,'word'=>'ØY','clue'=>'Land med vann rundt','r'=>1,'c'=>2,'d'=>'H'],['n'=>3,'word'=>'KAT','clue'=>'Mjauer','r'=>2,'c'=>0,'d'=>'H'],['n'=>4,'word'=>'ÅS','clue'=>'Liten høyde','r'=>3,'c'=>2,'d'=>'H'],['n'=>5,'word'=>'BÅT','clue'=>'Flyter på vann','r'=>4,'c'=>0,'d'=>'H']]],
            ],
            'prikker-og-streker' => ['standard'=>['id'=>'standard','difficulty'=>'To spillere','size'=>5]],
        ];
    }
}
