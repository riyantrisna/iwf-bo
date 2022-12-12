<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link href="{{asset('/assets/img/logo/favicon.ico')}}" rel="icon">
    <title>IWF Admin | {{ $title }}</title>
    <link href="{{asset('/assets/vendor/fontawesome-free/css/all.min.css')}}" rel="stylesheet" type="text/css">
    <link href="{{asset('/assets/vendor/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css">
    <link href="{{asset('/assets/css/ruang-admin.min.css')}}" rel="stylesheet">
    <link href="{{asset('/assets/vendor/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
    <!-- Bootstrap DatePicker -->
    <link href="{{asset('/assets/vendor/bootstrap-datepicker/css/bootstrap-datepicker.min.css')}}" rel="stylesheet" >
    <!-- Toastr -->
    <link rel="stylesheet" href="{{asset('/assets/vendor/toastr/toastr.min.css')}}">
    <!-- summernote -->
    <link rel="stylesheet" href="{{asset('/assets/vendor/summernote/summernote-bs4.css')}}">
    <!-- select2 -->
    <link rel="stylesheet" href="{{asset('/assets/vendor/select2/dist/css/select2.min.css')}}">
    <!-- Datetime picker -->
    {{-- <script src="{{asset('/assets/vendor/moment/moment.min.js')}}"></script> --}}
    <link rel="stylesheet" href="{{asset('/assets/vendor/datetimepicker/datetimepicker.css')}}">
</head>

<body id="page-top">
	<div id="wrapper">
		<!-- Sidebar -->
		@include('partials.sidebar')
		<!-- Sidebar -->
		<div id="content-wrapper" class="d-flex flex-column">
			<div id="content">
				<!-- TopBar -->
				@include('partials.navbar')
				<!-- Topbar -->

				<!-- Container Fluid-->
				<div class="container-fluid" id="container-wrapper">
					@yield('container')
				</div>
				<!---Container Fluid-->
			</div>
			<!-- Footer -->
			<footer class="sticky-footer bg-white">
			<div class="container my-auto">
				<div class="copyright text-center my-auto">
				<span>
                    Indonesia Womens Forum &copy; Prana Group <script> document.write(new Date().getFullYear()); </script>
				</span>
				</div>
			</div>
			</footer>
			<!-- Footer -->
		</div>
	</div>

	<!-- Scroll to top -->
	<a class="scroll-to-top rounded" href="#page-top">
		<i class="fas fa-angle-up"></i>
	</a>
</body>
</html>
