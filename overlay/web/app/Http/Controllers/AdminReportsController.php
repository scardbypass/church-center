<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AdminReportsController extends Controller {
    public function index(Request $request) {
        abort_unless($request->user()->platform_role === 'admin', 403);
        $counts = DB::table('church_automation_jobs')->select('status')
            ->selectRaw('COUNT(*) as total')->groupBy('status')->pluck('total','status');
        $payments = DB::table('payment_requests')->select('status')
            ->selectRaw('COUNT(*) as total')->selectRaw('COALESCE(SUM(amount_idr),0) as amount')
            ->groupBy('status')->get();
        $churches = DB::table('churches')->select('id','name','active')
            ->orderBy('id')->paginate(30);
        $worker = ['status' => 'unavailable'];
        $url = config('services.church_worker.url');
        if (is_string($url) && preg_match('~^http://127\.0\.0\.1:[0-9]{2,5}$~', $url)) {
            try {
                $health = Http::timeout(2)->get($url.'/health');
                if ($health->successful()) $worker = $health->json() ?: $worker;
            } catch (\Throwable $e) {
                // Worker health is informational; reports remain available.
            }
        }
        return view('church-center.admin-reports', compact('counts','payments','churches','worker'));
    }
    public function export(Request $request) {
        abort_unless($request->user()->platform_role === 'admin', 403);
        return response()->streamDownload(function () {
            $out = fopen('php://output','w');
            fputcsv($out, ['church_id','church','status','contacts','messages']);
            DB::table('churches')->orderBy('id')->chunkById(100,function($churches)use($out){
                foreach($churches as $c){
                    fputcsv($out,[$c->id, preg_match('/^[=+@\\-]/', ltrim($c->name)) ? "'".$c->name : $c->name, $c->active?'active':'inactive',
                        DB::table('church_contacts')->where('church_id',$c->id)->count(),
                        DB::table('church_automation_jobs')->where('church_id',$c->id)->count()]);
                }
            });
            fclose($out);
        },'church-center-report.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
