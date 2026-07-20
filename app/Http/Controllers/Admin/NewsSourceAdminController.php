<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\NewsSource;
use App\Services\News\NewsFeedService;
class NewsSourceAdminController extends Controller
{
    public function index(){return view('admin.news.sources',['sources'=>NewsSource::orderBy('name')->get()]);}
    public function toggle(NewsSource $newsSource){$newsSource->update(['is_active'=>!$newsSource->is_active]);return back()->with('success',$newsSource->name.' ble '.($newsSource->is_active?'aktivert.':'deaktivert.'));}
    public function fetch(NewsSource $newsSource,NewsFeedService $service){if(!$newsSource->is_active)return back()->with('warning','Kilden er deaktivert og ble ikke hentet.');$r=$service->fetch($newsSource);return back()->with($r['error']?'warning':'success',$r['error']?:"Hentet {$r['found']}: {$r['new']} nye, {$r['duplicates']} dubletter.");}
}
