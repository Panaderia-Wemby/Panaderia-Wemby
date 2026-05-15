<?php

namespace App\Http\Middleware;

use App\Grpc\DataSyncClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Middleware para sincronización distribuida
 * 
 * Intercepta cambios (POST, PUT, DELETE) y los sincroniza con otros nodos
 * via gRPC
 */
class SyncDistributed
{
    private $client;
    private $resourceMap = [
        'productos' => 'producto',
        'insumos' => 'insumo',
        'ventas' => 'venta',
        'proveedores' => 'proveedor',
        'categorias' => 'categoria',
    ];

    public function __construct(DataSyncClient $client)
    {
        $this->client = $client;
    }

    /**
     * Procesa el request y sincroniza si es necesario
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Solo sincronizar si está habilitado
        if (!config('distributed.sync.enabled')) {
            return $response;
        }

        // Solo para métodos que modifican datos
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        // Evitar sincronización recursiva
        if ($request->header('X-Sync-Node') === config('distributed.node.id')) {
            return $response;
        }

        try {
            $this->syncChanges($request, $response);
        } catch (\Exception $e) {
            Log::warning("Sync middleware error: " . $e->getMessage());
            // No fallar la request si la sincronización falla
        }

        return $response;
    }

    /**
     * Sincroniza los cambios con otros nodos
     */
    private function syncChanges(Request $request, $response)
    {
        // Solo si la respuesta fue exitosa (2xx, 3xx)
        if ($response->status() < 200 || $response->status() >= 400) {
            return;
        }

        $resource = $this->getResourceType($request->path());
        if (!$resource) {
            return;
        }

        // Determinar la acción
        $action = $this->getAction($request->method(), $request->path());
        if (!$action) {
            return;
        }

        // Preparar datos para sincronización
        $data = $this->prepareData($request, $resource, $action);

        // Sincronizar si está configurado para auto_sync
        if (config('distributed.sync.auto_sync')) {
            Log::info("SD Sync: $action $resource", ['data_keys' => array_keys($data)]);
            $this->client->sync($resource, $action, $data);
        }
    }

    /**
     * Obtiene el tipo de recurso del path
     */
    private function getResourceType($path)
    {
        foreach ($this->resourceMap as $urlSegment => $resourceType) {
            if (str_contains($path, $urlSegment)) {
                return $resourceType;
            }
        }
        return null;
    }

    /**
     * Determina la acción basada en el método HTTP
     */
    private function getAction($method, $path)
    {
        switch ($method) {
            case 'POST':
                return 'create';
            case 'PUT':
            case 'PATCH':
                return 'update';
            case 'DELETE':
                return 'delete';
            default:
                return null;
        }
    }

    /**
     * Prepara los datos para sincronización
     */
    private function prepareData(Request $request, $resource, $action)
    {
        $data = $request->all();

        // No sincronizar tokens o datos sensibles
        $data = collect($data)
            ->except(['_token', '_method', 'password', 'password_confirmation'])
            ->toArray();

        // Agregar el ID si es update o delete
        if (in_array($action, ['update', 'delete'])) {
            // Intentar obtener ID del request
            if ($request->route('id')) {
                $data['id'] = $request->route('id');
            } elseif ($request->route($resource)) {
                $data['id'] = $request->route($resource);
            }
        }

        // Agregar metadatos
        $data['_sync_origin'] = config('distributed.node.id');
        $data['_sync_timestamp'] = now()->timestamp;

        return $data;
    }
}
