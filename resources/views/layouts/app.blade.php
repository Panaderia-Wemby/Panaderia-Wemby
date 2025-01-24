<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Panaderia Wemby</title>

    <!-- Fonts -->
    <link href="{{ asset('assets/css/layout.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/font-awesome.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/framework.css') }}" rel="stylesheet" />
    @yield('head')
    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
@yield('style')

<body id="top">
    <div class="wrapper row1">
        <header id="header" class="hoc clear">
            <div id="logo" class="fl_left">
                <h1><a href="index.php">Panaderia Wemby</a></h1>
            </div>
            <nav id="mainav" class="fl_right">
                <ul class="clear">
                    <li class="active"><a href="{{ route('welcome') }}">Inicio</a></li>
                    @guest
                        @if (Route::has('login'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                            </li>
                        @endif
                    @else
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                {{ Auth::user()->name }}
                            </a>

                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                    onclick="event.preventDefault();
                                             document.getElementById('logout-form').submit();">
                                    {{ __('Logout') }}
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </li>
                    @endguest
                    @if (Auth::user() == null)
                        <li><a class="drop">Inventarios</a>
                            <ul>
                                <li><a href="{{ route('insumos.index') }}">Inventarios de insumos</a></li>
                                <li><a href="{{ route('productos.index') }}">Inventarios de productos</a></li>
                            </ul>
                        </li>
                    @elseif (Auth::user()->rol == 2 || Auth::user()->rol == 3)
                        <li><a class="drop">Inventarios</a>
                            <ul>
                                <li><a href="{{ route('insumos.index') }}">Inventarios de insumos</a></li>
                                <li><a href="{{ route('productos.index') }}">Inventarios de productos</a></li>
                            </ul>
                        </li>
                    @endif
                    @if (Auth::user() == null)
                        <li><a href="{{ route('ventas.index') }}">Ventas</a></li>
                    @elseif (Auth::user()->rol == 1)
                        <li><a href="{{ route('ventas.index') }}">Ventas</a></li>
                    @endif
                    @if (Auth::user() == null)
                        <li><a>Analisis y Reportes</a>
                            <ul>
                                <li><a href="{{ route('graficos.ventas') }}">Generar Reporte</a></li>
                                <li><a href="{{ route('analisis.form') }}">Generar Analisis</a></li>
                                <li><a href="{{ route('form') }}">Generar Graficas</a></li>
                            </ul>
                        </li>
                    @elseif (Auth::user()->rol == 3)
                        <li><a>Analisis y Reportes</a>
                            <ul>
                                <li><a href="{{ route('graficos.ventas') }}">Generar Reporte</a></li>
                                <li><a href="{{ route('analisis.form') }}">Generar Analisis</a></li>
                                <li><a href="{{ route('form') }}">Generar Graficas</a></li>
                            </ul>
                        </li>
                    @endif

                </ul>
            </nav>
        </header>
    </div>

    {{-- <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav> --}}

    <main class="py-4">
        @yield('content')
    </main>
    <!-- Footer Background Image Wrapper -->
    <div class="bgded overlay" style="background-image:url({{ asset('assets/images/footer.jpg') }});">
        <div class="wrapper row4">
            <footer id="footer" class="hoc clear">
                <div class="one_quarter first">
                    <h6 class="heading">Aliquet ut auctor</h6>
                    <ul class="nospace btmspace-30 linklist contact">
                        <li><i class="fa fa-map-marker"></i>
                            <address>
                                Street Name &amp; Number, Town, Postcode/Zip
                            </address>
                        </li>
                        <li><i class="fa fa-phone"></i> +00 (123) 456 7890</li>
                        <li><i class="fa fa-envelope-o"></i> info@domain.com</li>
                    </ul>
                    <ul class="faico clear">
                        <li><a class="faicon-facebook" href="#"><i class="fa fa-facebook"></i></a></li>
                        <li><a class="faicon-twitter" href="#"><i class="fa fa-twitter"></i></a></li>
                        <li><a class="faicon-dribble" href="#"><i class="fa fa-dribbble"></i></a></li>
                        <li><a class="faicon-linkedin" href="#"><i class="fa fa-linkedin"></i></a></li>
                    </ul>
                </div>
                <div class="one_quarter">
                    <h6 class="heading">Augue sociis natoque</h6>
                    <ul class="nospace linklist">
                        <li><a href="#">Porta lacinia est velit</a></li>
                        <li><a href="#">Sollicitudin nisl eu</a></li>
                        <li><a href="#">Tincidunt turpis ante</a></li>
                        <li><a href="#">Lobortis nisl fusce</a></li>
                        <li><a href="#">Purus nisi faucibus et est</a></li>
                    </ul>
                </div>
                <div class="one_quarter">
                    <h6 class="heading">Penatibus et magnis</h6>
                    <ul class="nospace linklist">
                        <li><a href="#">Quis blandit dignissim</a></li>
                        <li><a href="#">Lacus vestibulum porta</a></li>
                        <li><a href="#">Quam nec mollis vulputate</a></li>
                        <li><a href="#">Mauris aliquet cursus</a></li>
                        <li><a href="#">Nisl duis sed rhoncus</a></li>
                    </ul>
                </div>
                <div class="one_quarter">
                    <h6 class="heading">Dis parturient montes</h6>
                    <p class="nospace btmspace-15">Nascetur ridiculus mus elit aliquam erat volutpat proin scelerisque
                        mi viverra.</p>
                    <form method="post" action="#">
                        <fieldset>
                            <legend>Newsletter:</legend>
                            <input class="btmspace-15" type="text" value="" placeholder="Name">
                            <input class="btmspace-15" type="text" value="" placeholder="Email">
                            <button type="submit" value="submit">Submit</button>
                        </fieldset>
                    </form>
                </div>
            </footer>
        </div>
        <div class="wrapper row5">
            <div id="copyright" class="hoc clear">
                <p class="fl_left">Copyright &copy; 2018 - All Rights Reserved - <a href="#">Domain Name</a></p>
                <p class="fl_right">Template by <a target="_blank" href="https://www.os-templates.com/"
                        title="Free Website Templates">OS Templates</a></p>
            </div>
        </div>
    </div>
    <!-- End Footer Background Image Wrapper -->
    <a id="backtotop" href="#top"><i class="fa fa-chevron-up"></i></a>
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.backtotop.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.mobilemenu.js') }}"></script>
    @yield('scripts')
</body>

</html>
