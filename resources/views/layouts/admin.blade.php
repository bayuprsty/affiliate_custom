<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Belajar disini cara mendapatkan penghasilan dari bursa saham Amerika. Anda bisa tahu kapan beli, kapan jual & saham apa yang bagus.">
    <meta name="author" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta property="og:title" content="Belajar Trading Saham Amerika - Akela Trading System">
    <meta property="og:description" content="Belajar disini cara mendapatkan penghasilan dari bursa saham Amerika. Anda bisa tahu kapan beli, kapan jual & saham apa yang bagus.">
    <meta property="og:image" content="https://share.tradingnyantai.com/img/akela-og.jpg">

    <title>
        Belajar Trading Saham Amerika - Akela Trading System
    </title>

    <link href="{{ asset('sbadmin2-theme/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('icheck/skins/all.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="{{ asset('sbadmin2-theme/vendor/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet" type="text/css">
    <!-- Custom styles for this template-->
    <link href="{{ asset('sbadmin2-theme/css/sb-admin-2.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('sbadmin2-theme/css/bootstrap-social.css') }}">
    <link href="{{ asset('sbadmin2-theme/css/select2.min.css') }}" rel="stylesheet">

    @yield('css')
</head>
<body id="page-top">
    <div id="wrapper">
        @include('layouts.module.sidebar')

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                @include('layouts.module.header')
                
                @yield('content')
            </div>
            
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Affiliate System 2020</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fa fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="{{ asset('sbadmin2-theme/vendor/jquery/jquery.min.js')}}"></script>
    <script src="{{ asset('sbadmin2-theme/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>

    <!-- Core plugin JavaScript-->
    <script src="{{ asset('sbadmin2-theme/vendor/jquery-easing/jquery.easing.min.js')}}"></script>
    <script src="{{ asset('icheck/icheck.min.js?v=1.0.3')}}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{ asset('sbadmin2-theme/js/sb-admin-2.min.js')}}"></script>
    <script src="{{ asset('sbadmin2-theme/vendor/datatables/jquery.dataTables.min.js')}}"></script>
    <script src="{{ asset('sbadmin2-theme/vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{ asset('sbadmin2-theme/js/notify.min.js')}}"></script>
    <script src="{{ asset('sbadmin2-theme/js/select2.min.js')}}"></script>
    <script src="{{ asset('sbadmin2-theme/ckeditor/ckeditor.js')}}"></script>
    <!-- <script src="http://cdn.datatables.net/responsive/2.2.6/js/dataTables.responsive.js"></script> -->

    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: "{{ route('ajax.notification') }}",
                method: "GET",
                datatype: "JSON",
                success: function(res) {
                    if (res.count > 0) {
                        $('#notificationCount').html(res.count);
                    }
                    $('#data-notification').html(res.notification);
                }
            });
        });
    </script>
    
    @yield('js')
</body>
</html>