<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\NewsArticle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class NewsAdminController extends Controller
{
    public function index(Request $request)
    {
        $status=$request->query('status'); if($status&&!isset(NewsArticle::STATUSES[$status])) return redirect()->route('admin.news.index')->with('warning','Ugyldig statusfilter ble fjernet.');
        $articles=NewsArticle::with('source')->when($status,function($q)use($status){$q->where('status',$status);})->orderByRaw('COALESCE(published_at, fetched_at, created_at) DESC')->paginate(20)->withQueryString();
        $counts=NewsArticle::selectRaw('status,count(*) aggregate')->groupBy('status')->pluck('aggregate','status')->all();
        return view('admin.news.index',['articles'=>$articles,'statuses'=>NewsArticle::STATUSES,'activeStatus'=>$status,'statusCounts'=>$counts,'allStatusCount'=>array_sum($counts)]);
    }
    public function update(Request $request, NewsArticle $newsArticle)
    {
        $data=$request->validate(['edited_title'=>['nullable','string','max:500'],'edited_excerpt'=>['nullable','string','max:2000']]);
        $newsArticle->update(['edited_title'=>$data['edited_title']?:null,'edited_excerpt'=>$data['edited_excerpt']?:null]);
        return back()->with('success','Visningsteksten ble lagret.');
    }
    public function status(Request $request, NewsArticle $newsArticle)
    {
        $data=$request->validate(['status'=>['required',Rule::in(array_keys(NewsArticle::STATUSES))]]); $published=$data['status']===NewsArticle::STATUS_PUBLISHED;
        $newsArticle->update(['status'=>$data['status'],'approved_at'=>$published?($newsArticle->approved_at?:now()):null]);
        return back()->with('success','Status ble endret til '.(NewsArticle::STATUSES[$data['status']]??$data['status']).'.');
    }
}
