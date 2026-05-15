<?php

namespace App\Grpc;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio gRPC para sincronización de datos entre nodos del SD
 */
class DataSyncService
{
    protected $nodeId;
    protected $nodeHealth;

    public function __construct()
    {
        $this->nodeId = env('NODE_ID', gethostname());
        $this->nodeHealth = [
            'status' => 'healthy',
            'load' => 0,
            'timestamp' => time(),
            'active_connections' => 0,
        ];
    }

    /**
     * Sincroniza datos entre nodos
     * 
     * @param \Panaderia\Grpc\SyncRequest $request
     * @return \Panaderia\Grpc\SyncResponse
     */
    public function syncData($request)
    {
        try {
            Log::info("gRPC: SyncData recibido", [
                'resource_type' => $request->getResourceType(),
                'action' => $request->getAction(),
                'node_id' => $request->getNodeId(),
            ]);

            $resourceType = $request->getResourceType();
            $action = $request->getAction();
            $data = json_decode($request->getData(), true);

            // Validar origen para evitar sync infinito
            if ($request->getNodeId() === $this->nodeId) {
                Log::warning("gRPC: Sincronización desde el mismo nodo rechazada");
                return new \Panaderia\Grpc\SyncResponse([
                    'success' => false,
                    'message' => 'Same node sync rejected',
                    'node_id' => $this->nodeId,
                ]);
            }

            // Ejecutar acción según tipo
            $result = $this->executeSync($resourceType, $action, $data);

            return new \Panaderia\Grpc\SyncResponse([
                'success' => $result,
                'message' => $result ? 'Sync completed' : 'Sync failed',
                'node_id' => $this->nodeId,
                'sync_timestamp' => time(),
            ]);

        } catch (Exception $e) {
            Log::error("gRPC SyncData Error: " . $e->getMessage());
            return new \Panaderia\Grpc\SyncResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'node_id' => $this->nodeId,
            ]);
        }
    }

    /**
     * Health check del nodo
     * 
     * @param \Panaderia\Grpc\HealthCheckRequest $request
     * @return \Panaderia\Grpc\HealthCheckResponse
     */
    public function healthCheck($request)
    {
        try {
            // Verificar conectividad a BD
            DB::select('SELECT 1');
            
            // Verificar Redis
            Cache::get('health_check_' . $this->nodeId);

            // Calcular carga
            $load = $this->getSystemLoad();

            return new \Panaderia\Grpc\HealthCheckResponse([
                'status' => 'healthy',
                'load' => (int)$load,
                'timestamp' => time(),
                'active_connections' => db_get_connection_count(),
            ]);

        } catch (Exception $e) {
            Log::warning("gRPC HealthCheck: " . $e->getMessage());
            return new \Panaderia\Grpc\HealthCheckResponse([
                'status' => 'unhealthy',
                'load' => 100,
                'timestamp' => time(),
                'active_connections' => 0,
            ]);
        }
    }

    /**
     * Sincronización de consultas a BD
     * 
     * @param \Panaderia\Grpc\DatabaseSyncRequest $request
     * @return \Panaderia\Grpc\DatabaseSyncResponse
     */
    public function querySync($request)
    {
        try {
            $table = $request->getTable();
            $queryType = $request->getQueryType();
            $queryData = json_decode($request->getQueryData(), true);
            $requestingNode = $request->getRequestingNode();

            Log::info("gRPC: QuerySync recibido", [
                'table' => $table,
                'query_type' => $queryType,
                'requesting_node' => $requestingNode,
            ]);

            // Ejecutar según tipo
            $result = $this->executeQuery($table, $queryType, $queryData);

            return new \Panaderia\Grpc\DatabaseSyncResponse([
                'success' => true,
                'result_data' => json_encode($result),
                'message' => 'Query executed successfully',
                'executed_on_node' => $this->nodeId,
            ]);

        } catch (Exception $e) {
            Log::error("gRPC QuerySync Error: " . $e->getMessage());
            return new \Panaderia\Grpc\DatabaseSyncResponse([
                'success' => false,
                'result_data' => '{}',
                'message' => 'Error: ' . $e->getMessage(),
                'executed_on_node' => $this->nodeId,
            ]);
        }
    }

    /**
     * Ejecuta la sincronización según el tipo de recurso
     */
    protected function executeSync($resourceType, $action, $data)
    {
        try {
            switch ($action) {
                case 'create':
                    return $this->createResource($resourceType, $data);
                case 'update':
                    return $this->updateResource($resourceType, $data);
                case 'delete':
                    return $this->deleteResource($resourceType, $data);
                default:
                    Log::warning("Unknown action: $action");
                    return false;
            }
        } catch (Exception $e) {
            Log::error("ExecuteSync Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear recurso en BD
     */
    protected function createResource($resourceType, $data)
    {
        $table = $this->getTableName($resourceType);
        
        DB::table($table)->insert($data);
        
        // Invalidar cache
        Cache::forget("${resourceType}_*");
        
        return true;
    }

    /**
     * Actualizar recurso en BD
     */
    protected function updateResource($resourceType, $data)
    {
        $table = $this->getTableName($resourceType);
        $id = $data['id'] ?? null;

        if (!$id) {
            throw new Exception("No ID provided for update");
        }

        $updateData = collect($data)->except('id')->toArray();
        DB::table($table)->where('id', $id)->update($updateData);
        
        // Invalidar cache
        Cache::forget("${resourceType}_${id}");
        
        return true;
    }

    /**
     * Eliminar recurso en BD
     */
    protected function deleteResource($resourceType, $data)
    {
        $table = $this->getTableName($resourceType);
        $id = $data['id'] ?? null;

        if (!$id) {
            throw new Exception("No ID provided for delete");
        }

        DB::table($table)->where('id', $id)->delete();
        
        // Invalidar cache
        Cache::forget("${resourceType}_${id}");
        
        return true;
    }

    /**
     * Ejecuta una consulta en BD
     */
    protected function executeQuery($table, $queryType, $queryData)
    {
        $builder = DB::table($table);

        switch ($queryType) {
            case 'select':
                if (isset($queryData['where'])) {
                    $builder = $builder->where($queryData['where']);
                }
                return $builder->get()->toArray();

            case 'insert':
                DB::table($table)->insert($queryData['data']);
                return ['inserted' => true];

            case 'update':
                $where = $queryData['where'] ?? ['id' => $queryData['id']];
                $data = $queryData['data'] ?? $queryData;
                DB::table($table)->where($where)->update($data);
                return ['updated' => true];

            case 'delete':
                $where = $queryData['where'] ?? ['id' => $queryData['id']];
                DB::table($table)->where($where)->delete();
                return ['deleted' => true];

            default:
                throw new Exception("Unknown query type: $queryType");
        }
    }

    /**
     * Obtiene el nombre de tabla del tipo de recurso
     */
    protected function getTableName($resourceType)
    {
        $mapping = [
            'producto' => 'productos',
            'insumo' => 'insumos',
            'venta' => 'ventas',
            'proveedor' => 'proveedores',
            'categoria' => 'categorias',
            'detalle_venta' => 'detalle_venta',
        ];

        return $mapping[$resourceType] ?? $resourceType;
    }

    /**
     * Obtiene la carga del sistema
     */
    protected function getSystemLoad()
    {
        if (function_exists('sys_getloadavg')) {
            $loads = sys_getloadavg();
            $load = ($loads[0] * 100) / (int)shell_exec('nproc');
            return min($load, 100);
        }
        return 0;
    }

    /**
     * Obtiene el conteo de conexiones activas
     */
    protected function getConnectionCount()
    {
        try {
            $pdo = DB::connection()->getPdo();
            return substr_count(json_encode(get_object_vars($pdo)), '"');
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Obtiene el ID del nodo
     */
    public function getNodeId()
    {
        return $this->nodeId;
    }
}
