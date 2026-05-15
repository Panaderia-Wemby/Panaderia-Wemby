# Panaderia-Wemby

Proyecto de Ingeniería de Software
<b>Integrantes de grupo:</b>
# Jhon Mario Serrano Gordillo
# Santiago Riaño Vargas
# Joel Andrés Sayas Carmona
# Andrey Felipe Orozco Montoya

## Ejecutar con Docker

La aplicación queda expuesta en `http://localhost:8080`.

1. Construye e inicia los contenedores:

```bash
docker compose up --build
```

2. En una segunda terminal, crea la clave y migra la base de datos:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

3. Si necesitas volver a empezar desde cero:

```bash
docker compose down -v
```

El contenedor de la app corre en el puerto `8080` y MySQL queda accesible en `localhost:3307` desde tu máquina.
