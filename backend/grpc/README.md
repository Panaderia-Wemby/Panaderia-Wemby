# gRPC Map

Este directorio documenta el contrato y la división funcional del sistema distribuido.

## Topologia

- Node 1: Catalogo e inventario
- Node 2: Ventas y facturacion
- Node 3: Reportes y analisis
- Edge: React consume un BFF HTTP que a su vez llama a gRPC
- Base de datos: MySQL compartida para persistencia de dominio

## Servicios gRPC

### CatalogService

- `ListCategories`: lista las categorias de productos e insumos
- `ListProducts`: devuelve productos con categorias, proveedores e insumos
- `CreateProduct`: crea un producto nuevo
- `UpdateProductStock`: actualiza stock de un producto
- `ListSupplies`: lista insumos
- `ListSuppliers`: lista proveedores

### SalesService

- `ListSales`: lista ventas con detalle
- `GetInvoice`: construye la factura de una venta
- `CreateSale`: registra una venta, descuenta stock y devuelve si genera factura
- `UpdateSale`: actualiza una venta y recalcula stock

### ReportingService

- `ListSales`: reutiliza el listado de ventas para reportes
- `BuildAnalysis`: emite lineas de analisis de negocio
- `Summary`: devuelve totales, top category, top product y alertas de stock

## Flujo de ejecución

1. React inicia sesion contra `/api/auth/login`.
2. El backend HTTP valida credenciales y emite JWT con rol.
3. React solicita `/api/bootstrap` con el token.
4. El BFF HTTP consulta gRPC para categorias, productos, insumos, proveedores, ventas y resumen.
5. Las operaciones de CRUD van al BFF, que delega a los servicios gRPC.
6. Los servicios gRPC persisten o leen de MySQL.

## Mapa funcional

- Inventario: productos, insumos, proveedores, stock
- Ventas: crear, listar, ver factura, editar
- Reportes: resumen, analisis, datos para graficas
- Autenticacion: login, me, roles por pantalla

## Estado actual

La UI React ya consume el backend propio y el backend ya usa MySQL cuando `MYSQL_HOST` esta configurado. Laravel se mantiene solo como referencia hasta confirmar paridad total funcional en el despliegue completo.