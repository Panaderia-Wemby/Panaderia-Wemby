@extends('layouts.app')

@section('content')
    <div class="wrapper bgded overlay" style="background-image:url('{{asset("assets/images/HomeImage.jpg")}}');">
        <div id="pageintro" class="hoc clear">
            <article>
                <p>Donde el aroma y el sabor se encuentran</p>
                <h3 class="heading">Calidad en cada miga, frescura en cada bocado</h3>
                <footer>
                    <!-- <ul class="nospace inline pushright">
                <li><a class="btn" href="#">Registrar Compra</a></li>
                <li><a class="btn inverse" href="#">Registrar Producto</a></li>
              </ul> -->
                </footer>
            </article>
        </div>
    </div>
    <div class="wrapper row3">
        <main class="hoc container clear">
            <!-- main body -->
            <section id="introblocks">
                <div class="sectiontitle">
                    <h6 class="heading">Tellus malesuada dignissim</h6>
                    <p>Nullam tincidunt ex vel volutpat accumsan ut lobortis metus</p>
                </div>
                <ul class="nospace btmspace-80 group">
                    <li class="one_third first">
                        <article><i class="fa fa-diamond"></i>
                            <h6 class="heading font-x1"><a href="#">Feugiat maximus nisi</a></h6>
                            <p>Sem volutpat massa sed tristique nisi sem sed est cras pulvinar augue libero&hellip;</p>
                        </article>
                    </li>
                    <li class="one_third">
                        <article><i class="fa fa-fort-awesome"></i>
                            <h6 class="heading font-x1"><a href="#">Vel imperdiet libero</a></h6>
                            <p>Condimentum id donec eu eros quis ligula sollicitudin malesuada scelerisque&hellip;</p>
                        </article>
                    </li>
                    <li class="one_third">
                        <article><i class="fa fa-forumbee"></i>
                            <h6 class="heading font-x1"><a href="#">Dui nam sed tristique</a></h6>
                            <p>Nunc sed ligula viverra egestas tincidunt accumsan porta eget lorem maecenas&hellip;</p>
                        </article>
                    </li>
                </ul>
                <p class="center"><a class="btn" href="#">Eleifend dui auctor</a></p>
            </section>
            <!-- / main body -->
        </main>
    </div>
@endsection
