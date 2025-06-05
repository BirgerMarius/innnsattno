<?php

namespace App\Http\Livewire;

use Livewire\Component;

class Celle extends Component
{

    public $celler = "test";

    protected $listeners = ['avdA' => 'cellerA', 'avdB' => 'cellerB'];

    public function cellerA()
    {
        // $this->celler = ["A-101", "A-102", "A-103", "A-104", "A-105", "A-106", "A-107", "A-108", "A-109"];
        $this->celler = "test2";
    }

    public function cellerB()
    {
        $this->celler = ["B-101", "B-102", "B-103", "B-104", "B-105", "B-106", "B-107", "B-108", "B-109"];
    }

    public function render()
    {
        return view('livewire.celle');
    }
}
