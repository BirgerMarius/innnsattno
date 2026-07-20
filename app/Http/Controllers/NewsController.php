<?php
namespace App\Http\Controllers;
use App\NewsArticle;
use Illuminate\Http\Request;
class NewsController extends Controller
{
    public function index(Request $request)
    {
        $countries=['norge'=>'Norge','sverige'=>'Sverige','danmark'=>'Danmark','internasjonalt'=>'Internasjonalt']; $filter=$request->query('land');
        if($filter && !isset($countries[$filter])) return redirect()->route('news.index');
        $articles=NewsArticle::with('source')->where('status',NewsArticle::STATUS_PUBLISHED)
            ->when($filter,function($q)use($countries,$filter){$q->whereHas('source',function($s)use($countries,$filter){$s->where('country',$countries[$filter]);});})
            ->orderByRaw('COALESCE(published_at, fetched_at, created_at) DESC')->paginate(12)->withQueryString();
        return view('news.index',compact('articles','countries','filter'));
    }
}
