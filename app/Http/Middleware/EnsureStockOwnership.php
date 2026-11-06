<?php

namespace App\Http\Middleware;

use App\Features\Stock\Models\Stock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStockOwnership
{
    /**
     * Alias: owns.stock. Rute tanpa parameter {stock} dilewatkan.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $stock = $request->route('stock');

        if ($stock === null) {
            return $next($request);
        }

        if (! $stock instanceof Stock) {
            $stock = Stock::find($stock);
        }

        abort_if($stock === null, 404, 'Stok tidak ditemukan.');
        abort_if((int) $stock->user_id !== (int) $request->user()?->id, 403, 'Stok ini bukan milik Anda.');

        return $next($request);
    }
}
