<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Bootstrap CSS -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous"> -->

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<style>
    .bg-image-vertical {
        position: relative;
        overflow: hidden;
        background-repeat: no-repeat;
        background-position: right center;
        background-size: auto 100%;
    }

    @media (min-width: 1025px) {
        .h-custom-2 {
            height: 100%;
        }
    }
</style>

<body>
    <section class="vh-100">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6 text-black">
                    <!-- <div class="px-5 ms-xl-4">
                        <i class="fas fa-crow fa-2x me-3 pt-5 mt-xl-4" style="color: #709085;"></i>
                        <span class="h1 fw-bold mb-0">Panaderia Wemby</span>
                    </div> -->
                    <div class="d-flex align-items-center h-custom-2 px-5 ms-xl-4 mt-5 pt-5 pt-xl-0 mt-xl-n5">
                        <form style="width: 23rem;" method="POST" action="{{ route('login') }}">
                            @csrf
                            <h3 class="fw-normal mb-3 pb-3" style="letter-spacing: 1px;">Iniciar Sesión</h3>
                            <div data-mdb-input-init class="form-outline mb-4">
                                <label class="form-label" for="email">Correo</label>
                                <input type="email" id="email" class="form-control form-control-lg"
                                    @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}"
                                    required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>Las credenciales no son correctas</strong>

                                    </span>
                                @enderror
                            </div>
                            <div data-mdb-input-init class="form-outline mb-4">
                                <label class="form-label" for="password">Contraseña</label>
                                <input type="password" id="password" class="form-control form-control-lg"
                                    @error('password') is-invalid @enderror" name="password" required
                                    autocomplete="current-password">

                                @error('password')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>Las credenciales no son correctas</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="pt-1 mb-4">
                                <button data-mdb-button-init data-mdb-ripple-init class="btn btn-info btn-lg btn-block"
                                    type="submit">Iniciar Sesion</button>
                            </div>
                            @if (Route::has('password.request'))
                                <a class="btn" href="{{ route('password.request') }}">
                                    {{ __('¿Olvidaste tu contraseña?') }}
                                </a><br>
                            @endif
                            <button type="button" class="btn" data-bs-toggle="modal"
                                data-bs-target="#terminos">Terminos y Condiciones</button><br>
                            <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#ayuda">Ayuda
                                al Usuario</button><br>

                            <p>¿No tienes una cuenta? <a href="{{ route('register') }}" class="link-info">Creala
                                    ahora</a></p>

                            


                            <!-- Modal Terminos -->
                            <div class="modal fade" id="terminos" data-bs-backdrop="static" data-bs-keyboard="false"
                                tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="staticBackdropLabel">Terminos y Condiciones</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Bienvenido/a a la Panadería Wemby. Al acceder y utilizar nuestros
                                                servicios, usted acepta cumplir con los siguientes términos y
                                                condiciones. Por favor, lea atentamente este documento antes de realizar
                                                cualquier compra o interactuar con nuestra plataforma.</p>

                                            <h2>1. Generalidades</h2>
                                            <ul>
                                                <li>La Panadería Wemby se compromete a ofrecer productos de alta calidad
                                                    y un servicio al cliente excepcional.</li>
                                                <li>Estos términos y condiciones rigen el uso de nuestra página web,
                                                    aplicaciones, servicios en línea y cualquier transacción realizada
                                                    en nuestra tienda física.</li>
                                                <li>Nos reservamos el derecho de modificar estos términos en cualquier
                                                    momento, y dichas modificaciones serán publicadas en este apartado.
                                                </li>
                                            </ul>

                                            <h2>2. Productos y Servicios</h2>
                                            <ul>
                                                <li>Todos los productos ofrecidos están sujetos a disponibilidad. En
                                                    caso de que un producto no esté disponible, le informaremos
                                                    oportunamente y, si es necesario, gestionaremos el reembolso
                                                    correspondiente.</li>
                                                <li>Las imágenes de los productos son referenciales y pueden diferir
                                                    ligeramente del producto final.</li>
                                            </ul>

                                            <h2>3. Precios y Pagos</h2>
                                            <ul>
                                                <li>Los precios de nuestros productos están expresados en pesos
                                                    colombianos (COP) e incluyen los impuestos aplicables.</li>
                                                <li>Nos reservamos el derecho de modificar los precios en cualquier
                                                    momento sin previo aviso.</li>
                                                <li>Aceptamos los siguientes métodos de pago: efectivo, tarjetas de
                                                    crédito/débito y otros métodos habilitados en nuestro punto de venta
                                                    o plataforma en línea.</li>
                                            </ul>

                                            <h2>4. Pedidos y Reservas</h2>
                                            <ul>
                                                <li>Los pedidos especiales o personalizados deberán ser realizados con
                                                    un mínimo de 48 horas de antelación.</li>
                                                <li>Es responsabilidad del cliente proporcionar información precisa al
                                                    realizar un pedido.</li>
                                                <li>Una vez confirmado el pedido, no se aceptarán cambios ni
                                                    cancelaciones, salvo en situaciones excepcionales.</li>
                                            </ul>

                                            <h2>5. Entregas y Recogidas</h2>
                                            <ul>
                                                <li>Ofrecemos servicio de entrega a domicilio dentro de las zonas
                                                    habilitadas, sujeto a costos adicionales.</li>
                                                <li>Los tiempos de entrega son aproximados y pueden variar según la
                                                    ubicación y la demanda.</li>
                                                <li>Los pedidos también pueden ser recogidos en nuestra tienda en el
                                                    horario establecido.</li>
                                            </ul>

                                            <h2>6. Devoluciones y Reembolsos</h2>
                                            <ul>
                                                <li>Por tratarse de productos perecederos, no se aceptan devoluciones
                                                    una vez entregados. Sin embargo, si un producto presenta defectos de
                                                    calidad comprobables, podrá solicitarse un cambio o reembolso dentro
                                                    de las 24 horas posteriores a la compra.</li>
                                                <li>Los reembolsos serán procesados utilizando el mismo método de pago
                                                    del cliente, en caso de ser aprobados.</li>
                                            </ul>

                                            <h2>7. Uso de Datos Personales</h2>
                                            <ul>
                                                <li>La Panadería Wemby garantiza la protección de sus datos personales
                                                    conforme a las disposiciones de la Ley 1581 de 2012 de Colombia y
                                                    demás normativas aplicables.</li>
                                                <li>La información proporcionada por nuestros clientes será utilizada
                                                    únicamente para gestionar pedidos, mejorar nuestros servicios y
                                                    enviar promociones, en caso de que haya dado su consentimiento.</li>
                                            </ul>

                                            <h2>8. Propiedad Intelectual</h2>
                                            <ul>
                                                <li>Todo el contenido presente en nuestra plataforma, como imágenes,
                                                    textos y logotipos, es propiedad de la Panadería Wemby. Su
                                                    reproducción, distribución o uso no autorizado está prohibido.</li>
                                            </ul>

                                            <h2>9. Responsabilidades</h2>
                                            <ul>
                                                <li>La Panadería Wemby no se hace responsable por retrasos o
                                                    inconvenientes causados por factores externos fuera de nuestro
                                                    control, como condiciones climáticas o fallas en el transporte.</li>
                                                <li>El cliente es responsable de revisar su pedido al momento de la
                                                    entrega o recogida y reportar cualquier inconsistencia de inmediato.
                                                </li>
                                            </ul>

                                            <h2>10. Contacto</h2>
                                            <p>Para cualquier inquietud, sugerencia o reclamo, puede contactarnos a
                                                través de los siguientes medios:</p>
                                            <ul>
                                                <li>Teléfono: 0123456789</li>
                                                <li>Correo electrónico: <a
                                                        href="mailto:correo@panaderiawemby.com">correo@panaderiawemby.com</a>
                                                </li>
                                                <li>Dirección: Universidad Distrital Francisco Jose de Caldas</li>
                                            </ul>

                                            <p>Al utilizar nuestros servicios, usted confirma que ha leído, entendido y
                                                aceptado estos términos y condiciones. Gracias por confiar en la
                                                Panadería Wemby.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Cerrar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-sm-6 px-0 d-none d-sm-block">
                    <img src="{{ asset('assets/images/imagen.jpg') }}" alt="Login image" class="w-100 vh-100"
                        style="object-fit: cover; object-position: left;">
                </div>
            </div>
        </div>
    </section>
    <!-- Option 1: Bootstrap Bundle with Popper -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script> -->
</body>

</html>
