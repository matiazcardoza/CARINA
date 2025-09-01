<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    function indexv0 (Request $request) {
        // $q = Product::query();
        // $q = Product::with('kardexReports');

        $user = Auth::user();
        $roleNames = $user->getRoleNames(); 
        $q = Product::has('kardexReports');

        // $q = Product::doesntHave('kardexReports');
        // Filtros (usa los nombres exactos del front)
        if ($v = $request->query('numero'))   $q->where('numero', 'like', "%{$v}%");
        if ($v = $request->query('anio'))     $q->where('anio', $v);
        if ($v = $request->query('siaf'))     $q->where('siaf', 'like', "%{$v}%");
        if ($v = $request->query('ruc'))      $q->where('ruc', 'like', "%{$v}%");
        if ($v = $request->query('rsocial'))  $q->where('rsocial', 'like', "%{$v}%");
        if ($v = $request->query('item'))     $q->where('item', 'like', "%{$v}%");
        if ($v = $request->query('desmeta'))  $q->where('desmeta', 'like', "%{$v}%");
        if ($v = $request->query('email'))    $q->where('email', 'like', "%{$v}%");

        // Sort
        $sortField = $request->query('sort_field', 'numero');
        $sortOrder = $request->query('sort_order', 'asc') === 'desc' ? 'desc' : 'asc';
        $q->orderBy($sortField, $sortOrder);

        // Paginación
        $perPage = (int) $request->query('per_page', 10);

        // Devuelve la paginación estándar de Laravel
        return $q->paginate($perPage);
    }



    public function index(Request $req)
    {
        // Log::info("entrada");
        $user = Auth::user();
        // $user  = $req->user();
        $roles = $user->getRoleNames()->toArray(); // ej: ['residente de obra']

        // Solo reportes con flujo "in_progress"
        $products = Product::query()
            // ->whereHas('reports.flow', fn($q) => $q->where('status', 'in_progress'))
            ->whereHas('reports.flow') 
            ->with([
                'reports' => function ($q) {
                    $q->select('id','product_id','pdf_path','from_date','to_date','type','status','pdf_page_number','created_at')
                    // ->whereHas('flow', fn($f) => $f->where('status','in_progress'))
                    ->whereHas('flow')
                    // ->with([
                    //     'flow:id,kardex_report_id,current_step,status',
                    //     'flow.steps:id,signature_flow_id,order,role,status,callback_token'
                    // ]);
                    ->with([
                        'flow:id,kardex_report_id,current_step,status',
                        'flow.steps:id,signature_flow_id,order,role,status,callback_token,page,pos_x,pos_y,width,height',
                    ]);
                },
            ])
            ->select('id','name','item','id_order_silucia','id_product_silucia', 'detalles_orden','desmeta', 'fecha') // campos que muestres
            ->orderByDesc('id')
            ->paginate((int)$req->query('per_page', 20));

        // Transformar a la forma que necesita el front
        $products->getCollection()->transform(function ($product) use ($roles) {
            $product->reports = $product->reports->map(function ($report) use ($roles) {
                // obtiene cada flujo del reporte
                $flow = $report->flow;
                // Paso actual (quién tiene el turno)
                $currentStep = $flow->steps->firstWhere('order', (int)$flow->current_step);
                $currentRole = optional($currentStep)->role;

                // Paso del usuario (si tiene varios roles, tomamos el primero que aparezca en el flujo)
                
                $userStep = $flow->steps->first(function ($s) use ($roles) {
                    return in_array($s->role, $roles, true);
                });

                $canSign = false;
                if ($userStep) {
                    $canSign = $userStep->status === 'pending'
                        && (int)$userStep->order === (int)$flow->current_step;
                }

                return [
                    'report_id'   => $report->id,
                    'type'        => $report->type,
                    'period'      => ['from'=>$report->from_date, 'to'=>$report->to_date],
                    'status'      => $report->status,           // normalmente 'in_progress'
                    'flow_id'     => $flow->id,
                    'current_step'=> $flow->current_step,
                    'current_role'=> $currentRole,
                    'user_step'   => $userStep ? [
                                    'id'    => $userStep->id,
                                    'role'  => $userStep->role,
                                    'status'=> $userStep->status,
                                    'order' => $userStep->order,
                                    // obtenemos la cantidad de paginas de pdf generado y lo insertamos en la pagina que cada rol va a fimar, esto no esta en la base de datos
                                    // 'page' => $userStep->page,
                                    'page' => $report->pdf_page_number,
                                    'pos_x' => $userStep->pos_x,
                                    'pos_y' => $userStep->pos_y,
                                    'width' => $userStep->width,
                                    'height' => $userStep->height,
                                    ] : null,
                    'can_sign'    => $canSign,                  // <- para habilitar/deshabilitar botón
                    // 'download_url'=> url("/api/signatures/{$flow->id}/download-current"),
                    'download_url'=> url("/api/files-download?name={$report->pdf_path}"),
                    'sign_callback_url' => $userStep
                        ? url("/api/signatures/callback?flow_id={$flow->id}&step_id={$userStep->id}&token={$userStep->callback_token}")
                        : null,
                ];
            });
            // id','name','id_order_silucia','id_product_silucia', 'detalles_orden','desmeta', 'fecha'
            return [
                'product_id'        => $product->id,
                'name'              => $product->name,
                'item'              => $product->item,
                'id_order_silucia'  => $product->id_order_silucia,
                'id_product_silucia'=> $product->id_product_silucia,
                'reports'           => $product->reports,
                'detalles_orden'    => $product->detalles_orden,
                'desmeta'           => $product->desmeta,
                'fecha'             => $product->fecha,
            ];
        });

        return response()->json($products);
    }

}
